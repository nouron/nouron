<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T30 Pool v1: Regolith credited by a completed self-salvage (ledger tag find_self)
 * is its own source 'find' in regolith_sources — not 'harvester', not 'trade';
 * a salvage flight (find_flight) has its own bucket and counts for Pfad B.
 */
class RunReportFindSourceTest extends TestCase
{
    use RefreshDatabase;

    private function sources(BotSession $bot): array
    {
        $report = new RunReport(1);
        $report->snapshot($bot);

        return $report->build($bot)['sols'][0]['regolith_sources'];
    }

    public function test_self_salvage_booking_counts_as_find(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->regolithLedger[$bot->sol]['in'] = ['find_self' => 10];

        $sources = $this->sources($bot);

        $this->assertSame(10, $sources['find']);
        $this->assertSame(0, $sources['harvester']);
        $this->assertSame(0, $sources['trade']);
    }

    public function test_find_defaults_to_zero(): void
    {
        $bot = BotSession::boot($this, 1);

        $this->assertSame(0, $this->sources($bot)['find']);
    }

    public function test_find_does_not_leak_into_the_path_attribution(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->regolithLedger[$bot->sol]['in'] = ['find_self' => 4];
        $report = new RunReport(1);
        $report->snapshot($bot);

        $attr = $report->build($bot)['regolith_path_attribution'] ?? null;

        $this->assertNotNull($attr);
        $this->assertSame(0, array_sum($attr));
    }

    public function test_find_flight_booking_is_its_own_source_and_counts_for_path_b(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        $bot->regolithLedger[1]['in'] = ['find_flight' => 15, 'harvester_base' => 25];

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

    public function test_flight_payout_counts_even_when_the_sol_net_change_is_negative(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->sol = 1;
        // Payout 22 offset by spend in the same Sol: inflows stay inflows.
        $bot->regolithLedger[1] = ['in' => ['find_flight' => 15, 'mission_ore' => 7], 'out' => ['build_place' => 27]];

        $sources = $this->sources($bot);

        $this->assertSame(15, $sources['find_flight']);
        $this->assertSame(7, $sources['mission']);
        $this->assertSame(0, $sources['harvester']);
    }
}
