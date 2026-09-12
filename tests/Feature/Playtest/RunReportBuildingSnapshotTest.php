<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B1b of the playtest instrumentation plan (docs/playtest-instrumentation-plan.md):
 * per-Sol snapshot of colony_buildings level + ap_spend, keyed by
 * "<building_id>:<instance_id>" — raw material for the completion-timing
 * metrics (Sole bis Fertigstellung, gleichzeitige Baustellen, letzte Fertigstellung).
 */
class RunReportBuildingSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_captures_level_and_ap_spend_per_instance(): void
    {
        $bot = BotSession::boot($this, seed: 1);

        // Force a known state on one instance so the snapshot's values can be
        // asserted against something other than whatever fixtures happen to seed.
        DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', 25)
            ->where('instance_id', 1)
            ->update(['level' => 0, 'ap_spend' => 5]);

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);

        $data = $report->build($bot);
        $buildings = $data['sols'][0]['buildings'];

        $this->assertIsArray($buildings);
        $this->assertNotEmpty($buildings);

        $rows = DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->get();
        $this->assertSame($rows->count(), count($buildings));

        foreach ($rows as $row) {
            $key = "{$row->building_id}:{$row->instance_id}";
            $this->assertArrayHasKey($key, $buildings);
            $this->assertSame((int) $row->level, $buildings[$key]['level']);
            $this->assertSame((int) $row->ap_spend, $buildings[$key]['ap_spend']);
            $this->assertGreaterThanOrEqual(0, $buildings[$key]['ap_spend']);
        }

        $this->assertSame(0, $buildings['25:1']['level']);
        $this->assertSame(5, $buildings['25:1']['ap_spend']);
    }
}
