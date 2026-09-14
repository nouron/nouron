<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A37-Rest investigation (2026-09-14): no report ever captured ship
 * ownership, only harvester positions (B1e). Needed to check whether
 * dispatch_compounds_mission's candidate never fires because the colony
 * never actually owns a freighter/corvette in the first place.
 */
class RunReportShipSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_reports_ship_counts_by_id(): void
    {
        $bot = BotSession::boot($this, seed: 1);

        DB::table('colony_ships')->insert([
            ['colony_id' => $bot->colonyId, 'ship_id' => 47, 'ship_state' => 'docked', 'status_points' => 20],
            ['colony_id' => $bot->colonyId, 'ship_id' => 47, 'ship_state' => 'docked', 'status_points' => 20],
            ['colony_id' => $bot->colonyId, 'ship_id' => 85, 'ship_state' => 'docked', 'status_points' => 20],
        ]);

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);
        $sol = $report->build($bot)['sols'][0];

        $this->assertArrayHasKey('ships', $sol);
        $this->assertSame(2, $sol['ships'][47]);
        $this->assertSame(1, $sol['ships'][85]);
    }
}
