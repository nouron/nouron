<?php

namespace Tests\Feature\Playtest;

use App\Services\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Rule invest_find self-salvages deep-scanned find tiles (no hangar) with leftover
 * AP — best yield (Rg per AP) first, only what the AP cover, never on
 * find_false / unscanned tiles, never with a hangar (flights take over).
 */
class BotStrategyInvestFindTest extends TestCase
{
    use RefreshDatabase;

    public function test_picks_the_find_with_the_best_rg_per_ap(): void
    {
        $bot = BotSession::boot($this, 1);
        $small = $this->makeFind($bot, 'find_small');   // 4/3 = 1.33
        $large = $this->makeFind($bot, 'find_large');   // 18/6 = 3.0
        $medium = $this->makeFind($bot, 'find_medium'); // 10/4 = 2.5
        $this->giveAp($bot, 10);

        $c = $this->rule()['when']($bot);

        $this->assertSame([$large->q, $large->r], [(int) $c->q, (int) $c->r]);
        unset($small, $medium);
    }

    public function test_ignores_find_false_and_unscanned_tiles(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->makeFind($bot, 'find_false');
        $this->makeFind($bot, 'find_large', scanned: false);
        $this->giveAp($bot, 10);

        $this->assertEmpty($this->rule()['when']($bot));
    }

    public function test_only_finds_payable_with_the_available_ap_qualify(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->makeFind($bot, 'find_large'); // 6 AP
        $small = $this->makeFind($bot, 'find_small'); // 3 AP

        $this->giveAp($bot, 5);
        $c = $this->rule()['when']($bot);
        $this->assertSame([$small->q, $small->r], [(int) $c->q, (int) $c->r], 'large find (6 AP) is out of reach');

        $this->giveAp($bot, 2);
        $this->assertEmpty($this->rule()['when']($bot));
    }

    public function test_no_candidate_with_a_hangar(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->makeFind($bot, 'find_small');
        $this->giveAp($bot, 10);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]
        );

        $this->assertEmpty($this->rule()['when']($bot), 'with a hangar finds go by flight');
    }

    public function test_scan_and_invest_find_are_the_last_two_rules(): void
    {
        $names = array_column(BotStrategy::default(), 'name');

        $this->assertSame('invest_find', end($names));
        $this->assertSame('deep_scan_signal_tile', $names[count($names) - 2]);
        foreach (['hire_advisor', 'explore_tile', 'place_building', 'invest_production', 'research_knowledge', 'request_ship'] as $before) {
            $pos = array_search($before, $names, true);
            $this->assertNotFalse($pos, "{$before} missing");
            $this->assertLessThan(count($names) - 2, $pos, "{$before} must precede the scan");
        }
    }

    public function test_deep_scan_candidate_uses_config_cost_with_and_without_uplink(): void
    {
        $bot = BotSession::boot($this, 1);
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereNull('event_type')->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['event_type' => 'find_small', 'is_explored' => 1, 'is_deep_scanned' => 0]);
        $scan = collect(BotStrategy::default())->firstWhere('name', 'deep_scan_signal_tile');
        $cost = (int) config('game.finds.scan_ap');
        $cheap = (int) config('game.finds.scan_ap_uplink');
        $this->assertGreaterThan($cheap, $cost);

        $this->giveAp($bot, $cost - 1);
        $this->assertEmpty($scan['when']($bot));
        $this->giveAp($bot, $cost);
        $this->assertNotEmpty($scan['when']($bot));

        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => (int) config('buildings.uplinkStation.id', 54),
            'instance_id' => 1, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5,
        ]);
        $this->giveAp($bot, $cheap);
        $this->assertNotEmpty($scan['when']($bot));
        $this->giveAp($bot, $cheap - 1);
        $this->assertEmpty($scan['when']($bot));
    }

    public function test_do_posts_to_the_salvage_endpoint_and_credits_regolith(): void
    {
        $bot = BotSession::boot($this, 1);
        $t = $this->makeFind($bot, 'find_small');
        $this->giveAp($bot, 10);
        $rule = $this->rule();
        $c = $rule['when']($bot);

        $res = $rule['do']($bot, $c);

        $this->assertTrue($res['ok']);
        $last = end($bot->log);
        $this->assertSame('invest_find', $last['rule']);
        $this->assertGreaterThan($last['regolith_before'], $last['regolith_after']);
        $this->assertNull(DB::table('colony_tiles')->where('id', $t->id)->value('event_type'));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    /** Sets the Sol's available AP to exactly $ap (or the colony's total if that is lower). */
    private function giveAp(BotSession $bot, int $ap): void
    {
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $total = $service->getTotalActionPoints($bot->colonyId);
        $this->assertGreaterThanOrEqual($ap, $total, 'fixture colony has too few AP');
        $service->lockActionPoints($bot->colonyId, $total - $ap);
    }

    private function makeFind(BotSession $bot, string $type, bool $scanned = true): object
    {
        // pick one not yet used by an earlier makeFind call in this test
        $tile = DB::table('colony_tiles')
            ->where('colony_id', $bot->colonyId)
            ->whereNull('event_type')
            ->where('is_colony_zone', 0)
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update([
            'event_type' => $type, 'is_explored' => 1, 'is_deep_scanned' => $scanned ? 1 : 0,
        ]);

        return DB::table('colony_tiles')->where('id', $tile->id)->first();
    }

    /** @return array{name:string, when:callable, do:callable} */
    private function rule(): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === 'invest_find') {
                return $rule;
            }
        }

        $this->fail('Rule invest_find not found');
    }
}
