<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T30 Pool v1: Regolith credited by a completed salvage (rule invest_find) is
 * its own source 'find' in regolith_sources — not 'harvester', not 'trade'.
 */
class RunReportFindSourceTest extends TestCase
{
    use RefreshDatabase;

    private function pushEntry(BotSession $bot, string $rule, int $before, int $after, bool $ok = true): void
    {
        $bot->log[] = [
            'sol' => $bot->sol, 'rule' => $rule, 'url' => '', 'status' => $ok ? 200 : 422, 'ok' => $ok,
            'error' => $ok ? null : 'rejected', 'ap_before' => 10, 'ap_after' => 10,
            'regolith_before' => $before, 'regolith_after' => $after, 'organics_before' => 0, 'organics_after' => 0,
        ];
    }

    private function sources(BotSession $bot): array
    {
        $report = new RunReport(1);
        $report->snapshot($bot);

        return $report->build($bot)['sols'][0]['regolith_sources'];
    }

    public function test_completed_salvage_delta_counts_as_find(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 110);
        $this->pushEntry($bot, 'invest_find', 110, 110);

        $sources = $this->sources($bot);

        $this->assertSame(10, $sources['find']);
        $this->assertSame(0, $sources['harvester']);
        $this->assertSame(0, $sources['trade']);
    }

    public function test_find_defaults_to_zero_and_ignores_rejected_entries(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 118, ok: false);

        $this->assertSame(0, $this->sources($bot)['find']);
    }

    public function test_find_does_not_leak_into_the_path_attribution(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->pushEntry($bot, 'invest_find', 100, 104);
        $report = new RunReport(1);
        $report->snapshot($bot);

        $attr = $report->build($bot)['regolith_path_attribution'] ?? null;

        $this->assertNotNull($attr);
        $this->assertSame(0, array_sum($attr));
    }

    public function test_find_flight_comes_from_the_mission_event_and_leaves_the_harvester_share(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        DB::table('colony_log')->insert(['user' => $bot->userId, 'tick' => 1, 'event' => 'hangar.mission_completed',
            'area' => 'colony', 'parameters' => json_encode(['rewards' => ['find_type' => 'find_medium', 'find_regolith' => 15, 'flight_bonus' => 5]]),
            'is_read' => true, 'created_at' => now()]);
        $this->pushEntry($bot, 'sol_next', 100, 140);

        $report = new RunReport(1);
        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(15, $data['sols'][0]['regolith_sources']['find_flight']);
        $this->assertSame(25, $data['sols'][0]['regolith_sources']['harvester']);
        $this->assertSame(15, $data['regolith_path_attribution']['pfad_b_frachter']);
    }

    public function test_snapshot_counts_find_waits_by_reason(): void
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => 44,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
            ->whereNull('event_type')->where('q', '!=', 0)->first();
        DB::table('colony_tiles')->where('id', $tile->id)
            ->update(['event_type' => 'find_large', 'is_explored' => 1, 'is_deep_scanned' => 1]);

        $report = new RunReport(1);
        $report->snapshot($bot);

        $this->assertSame(['needs_level' => 1, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 1],
            $report->build($bot)['sols'][0]['find_waits']);
    }

    public function test_find_waits_counts_an_inactive_carrier_as_needs_level(): void
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1, 'level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        DB::table('colony_ships')->insert(['colony_id' => $bot->colonyId, 'ship_id' => 47, 'bay' => 3,
            'ship_state' => 'docked', 'status_points' => 20, 'ap_spend' => 0, 'level' => 0, 'deliver_at_tick' => null]);
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 0)
            ->whereNull('event_type')->first();
        DB::table('colony_tiles')->where('id', $tile->id)
            ->update(['event_type' => 'find_large', 'is_explored' => 1, 'is_deep_scanned' => 1]);

        $report = new RunReport(1);
        $report->snapshot($bot);

        $this->assertSame(['needs_level' => 1, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 0],
            $report->build($bot)['sols'][0]['find_waits']);
    }

    public function test_a_flight_to_a_vanished_find_pays_nothing_and_leaves_the_delta_to_the_harvester(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        DB::table('colony_log')->insert(['user' => $bot->userId, 'tick' => 1, 'event' => 'hangar.mission_failed',
            'area' => 'colony', 'parameters' => json_encode(['mission_key' => 'mission_find_salvage', 'find_missing' => true]),
            'is_read' => true, 'created_at' => now()]);
        $this->pushEntry($bot, 'sol_next', 100, 116);

        $sources = $this->sources($bot);

        $this->assertSame(0, $sources['find_flight']);
        $this->assertSame(16, $sources['harvester']);
    }

    public function test_flight_payout_counts_even_when_the_sol_net_delta_is_not_positive(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        DB::table('colony_log')->insert(['user' => $bot->userId, 'tick' => 1, 'event' => 'hangar.mission_completed',
            'area' => 'colony', 'parameters' => json_encode(['rewards' => ['find_regolith' => 15, 'regolith' => 7]]),
            'is_read' => true, 'created_at' => now()]);
        $this->pushEntry($bot, 'sol_next', 100, 95); // payout 22 offset by spend elsewhere in the Sol

        $sources = $this->sources($bot);

        $this->assertSame(15, $sources['find_flight']);
        $this->assertSame(7, $sources['mission']);
        $this->assertSame(0, $sources['harvester']);
    }
}
