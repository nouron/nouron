<?php

namespace Tests\Feature\Playtest;

use App\Services\AdvisorService;
use App\Services\TickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T30 Pool v1: rule invest_find puts leftover AP into salvage projects on
 * deep-scanned find tiles — best yield (rg per total AP) first, cap and
 * remainder respected, never on find_false / unscanned tiles, one deposit
 * per tile and Sol, at most max_open_projects projects.
 */
class BotStrategyInvestFindTest extends TestCase
{
    use RefreshDatabase;

    public function test_picks_the_find_with_the_best_rg_per_ap(): void
    {
        $bot = BotSession::boot($this, 1);
        $small = $this->makeFind($bot, 'find_small');   // 4/12 = 0.33
        $large = $this->makeFind($bot, 'find_large');   // 18/22 = 0.82
        $medium = $this->makeFind($bot, 'find_medium'); // 10/16 = 0.63
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

    public function test_deposit_respects_cap_remainder_and_available_ap(): void
    {
        $bot = BotSession::boot($this, 1);
        $t = $this->makeFind($bot, 'find_small'); // 12 AP total
        $cap = (int) config('game.finds.salvage_cap_per_sol');

        $this->giveAp($bot, 12);
        $this->assertSame($cap, (int) $this->rule()['when']($bot)->ap);

        $this->giveAp($bot, 2);
        $this->assertSame(2, (int) $this->rule()['when']($bot)->ap);

        $this->giveAp($bot, 12);
        DB::table('colony_tiles')->where('id', $t->id)->update(['salvage_ap_spent' => 10, 'salvage_tick' => 1]);
        app(TickService::class)->setTickCount(5);
        $this->assertSame(2, (int) $this->rule()['when']($bot)->ap);

        $this->giveAp($bot, 0);
        $this->assertEmpty($this->rule()['when']($bot));
    }

    public function test_tile_already_paid_this_tick_is_not_a_candidate(): void
    {
        $bot = BotSession::boot($this, 1);
        $tick = app(TickService::class)->getTickCount();
        $this->makeFind($bot, 'find_large', spent: 4, salvageTick: $tick);
        $this->giveAp($bot, 10);

        $this->assertEmpty($this->rule()['when']($bot));
    }

    public function test_no_new_project_when_open_project_limit_is_reached(): void
    {
        $bot = BotSession::boot($this, 1);
        $tick = app(TickService::class)->getTickCount();
        for ($i = 0; $i < (int) config('game.finds.max_open_projects'); $i++) {
            $this->makeFind($bot, 'find_small', spent: 4, salvageTick: $tick - 1);
        }
        $fresh = $this->makeFind($bot, 'find_large');
        $this->giveAp($bot, 10);

        $c = $this->rule()['when']($bot);

        // Only the started projects qualify, not the untouched (better) large find.
        $this->assertNotEmpty($c);
        $this->assertNotSame([$fresh->q, $fresh->r], [(int) $c->q, (int) $c->r]);
    }

    public function test_started_project_wins_a_yield_tie(): void
    {
        $bot = BotSession::boot($this, 1);
        $tick = app(TickService::class)->getTickCount();
        $this->makeFind($bot, 'find_small');
        $started = $this->makeFind($bot, 'find_small', spent: 4, salvageTick: $tick - 1);
        $this->giveAp($bot, 10);

        $c = $this->rule()['when']($bot);

        $this->assertSame([$started->q, $started->r], [(int) $c->q, (int) $c->r]);
    }

    public function test_scan_and_invest_find_are_the_last_two_rules(): void
    {
        $names = array_column(BotStrategy::default(), 'name');

        $this->assertSame('invest_find', end($names));
        $this->assertSame('deep_scan_signal_tile', $names[count($names) - 2]);
        foreach (['hire_advisor', 'explore_tile', 'place_building', 'invest_production', 'research_knowledge', 'request_ship', 'dispatch_salvage_mission'] as $before) {
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
        $t = $this->makeFind($bot, 'find_small', spent: 8);
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

    private function makeFind(BotSession $bot, string $type, bool $scanned = true, int $spent = 0, ?int $salvageTick = null): object
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
            'salvage_ap_spent' => $spent, 'salvage_tick' => $salvageTick,
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
