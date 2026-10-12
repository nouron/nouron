<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.3/§13.4: with a hangar the bot flies finds, never opens self-salvage, repairs ships, keeps scanning. */
class BotStrategyFindFlightTest extends TestCase
{
    use RefreshDatabase;

    private function bootWithHangar(int $level): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1, 'level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)->update(['amount' => 50]);
        $this->giveAp($bot, 10);

        return $bot;
    }

    private function giveAp(BotSession $bot, int $ap): void
    {
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - $ap);
    }

    private function makeFind(BotSession $bot, string $type, bool $scanned = true, int $spent = 0, int $ring = 2): object
    {
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->whereNull('event_type')
            ->where('is_colony_zone', 0)->where('ring', $ring)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['event_type' => $type, 'is_explored' => 1,
            'is_deep_scanned' => $scanned ? 1 : 0, 'salvage_ap_spent' => $spent, 'salvage_tick' => null]);

        return DB::table('colony_tiles')->where('id', $tile->id)->first();
    }

    private function rule(string $name): array
    {
        return collect(BotStrategy::default())->firstWhere('name', $name) ?? $this->fail("rule {$name} missing");
    }

    public function test_flies_the_largest_carryable_find_with_the_smallest_class(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_small');
        $medium = $this->makeFind($bot, 'find_medium');
        $this->makeFind($bot, 'find_large'); // locked at level 1

        $c = $this->rule('dispatch_find_flight')['when']($bot);

        $this->assertSame([0, (int) $medium->q, (int) $medium->r], [$c['bay'], $c['q'], $c['r']]);
        $this->assertTrue($this->rule('dispatch_find_flight')['do']($bot, $c)['ok']);
    }

    public function test_locked_large_find_alone_gives_no_candidate_and_no_error(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_large');

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));
        $this->assertEmpty($this->rule('invest_find')['when']($bot), 'no self-salvage with a hangar');
    }

    public function test_grandfathered_project_is_continued_after_the_hangar_stands(): void
    {
        $bot = $this->bootWithHangar(1);
        $tile = $this->makeFind($bot, 'find_small', spent: 4);

        $c = $this->rule('invest_find')['when']($bot);

        $this->assertSame([(int) $tile->q, (int) $tile->r], [$c->q, $c->r]);
    }

    public function test_hangar_colony_with_only_unscanned_signals_scans_first(): void
    {
        $bot = $this->bootWithHangar(1);
        $tile = $this->makeFind($bot, 'find_small', scanned: false);

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));
        $this->assertSame((int) $tile->id, (int) $this->rule('deep_scan_signal_tile')['when']($bot)->id);
    }

    public function test_worn_gift_drone_gets_repaired_then_flies(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_small'); // ring 2: drone wear 3
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 2]);
        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot));

        $c = $this->rule('repair_ship_for_flight')['when']($bot);
        $this->assertSame(0, $c['bay']);
        $this->assertTrue($this->rule('repair_ship_for_flight')['do']($bot, $c)['ok']);

        $this->assertSame(4.0, (float) DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->value('status_points'));
        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot), 'ready now — no further repair');
        $this->assertNotEmpty($this->rule('dispatch_find_flight')['when']($bot));
    }

    public function test_bot_repairs_before_a_ring_3_flight_instead_of_starting(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_medium', ring: 3); // drone wear 6
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 5]);

        $this->assertEmpty($this->rule('dispatch_find_flight')['when']($bot), 'old 25 % floor would have started and aborted');
        $this->assertSame(0, $this->rule('repair_ship_for_flight')['when']($bot)['bay']);
    }

    public function test_no_repair_when_the_following_flight_is_not_payable(): void
    {
        $bot = $this->bootWithHangar(1);
        $this->makeFind($bot, 'find_medium', ring: 3); // drone wear 6
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 5]);

        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)->update(['amount' => 1]);
        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot), 'Organika too low for the ring-3 flight');

        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)->update(['amount' => 50]);
        $this->giveAp($bot, 1);
        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot), 'repair AP + nav AP exceed available AP');

        $this->giveAp($bot, 10);
        $this->assertSame(0, $this->rule('repair_ship_for_flight')['when']($bot)['bay'], 'payable: repair chosen');
    }

    public function test_no_repair_without_a_waiting_find(): void
    {
        $bot = $this->bootWithHangar(1);
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->where('bay', 0)->update(['status_points' => 2]);

        $this->assertEmpty($this->rule('repair_ship_for_flight')['when']($bot));
    }

    public function test_gift_drone_suppresses_the_bootstrap_drone_purchase(): void
    {
        $bot = $this->bootWithHangar(1);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 5000]);

        $this->assertEmpty($this->rule('request_ship')['when']($bot), 'no bootstrap drone: the gift drone is there (advisors < 3)');
    }

    public function test_rule_order(): void
    {
        $names = array_column(BotStrategy::default(), 'name');
        $this->assertNotContains('dispatch_regolith_mission', $names);
        $this->assertSame(array_search('dispatch_find_flight', $names, true) - 1, array_search('repair_ship_for_flight', $names, true), 'directly before');
        $this->assertLessThan(array_search('dispatch_credit_mission', $names, true), array_search('dispatch_find_flight', $names, true));
    }
}
