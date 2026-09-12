<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B1e: RunReport::snapshot() must report harvester tile positions per Sol,
 * keyed by instance_id, so the dashboard can plot relocate_harvester moves
 * against the actual DB state (playtest-instrumentation-plan.md).
 */
class RunReportHarvesterPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_reports_placed_harvester_instance_position(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        $report->snapshot($bot);

        $data = $report->build($bot);

        $position = DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('instance_id', 1)
            ->whereNotNull('tile_x')
            ->whereNotNull('tile_y')
            ->first();

        $this->assertNotNull($position, 'Sol-1 fixture is expected to have a placed Harvester instance 1.');

        $this->assertArrayHasKey('harvester_positions', $data['sols'][0]);
        $this->assertSame(
            [(int) $position->tile_x, (int) $position->tile_y],
            $data['sols'][0]['harvester_positions'][1]
        );
    }

    public function test_snapshot_reflects_relocated_position_on_next_snapshot(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        $report->snapshot($bot);

        // Simulate a relocate_harvester move landing on a new tile.
        DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('instance_id', 1)
            ->update(['tile_x' => 9, 'tile_y' => 7]);

        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertSame([9, 7], $data['sols'][1]['harvester_positions'][1]);
    }

    public function test_snapshot_omits_unplaced_harvester_instance(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        // Sol-1 fixture only seeds instance_id 1 — insert an unplaced 2nd
        // instance (as it exists after the mid-run second-instance unlock,
        // before the player relocates it) to verify it's excluded.
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Harvester->value,
            'instance_id' => 2,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => null,
            'tile_y' => null,
        ]);

        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertArrayHasKey(1, $data['sols'][0]['harvester_positions']);
        $this->assertArrayNotHasKey(2, $data['sols'][0]['harvester_positions']);
    }
}
