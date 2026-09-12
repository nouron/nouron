<?php

namespace Tests\Feature\Playtest;

/**
 * A31/B2a: RunReport::build() derives project-completion metrics (2-4) from
 * the B1b building snapshots (level + ap_spend per instance, per Sol) —
 * no new game-code reads, pure aggregation over what snapshot() already
 * captured.
 *
 *   project_durations       — Sole von "erster ap_spend>0" bis "level steigt",
 *                              je abgeschlossenem Projekt (jede Instanz kann
 *                              mehrere Zyklen über den Run haben)
 *   median_concurrent_projects — Median, über alle Sole, der Instanzen mit
 *                              ap_spend>0 an diesem Sol ("aktive Baustelle")
 *   last_completion_sol     — höchster Sol, an dem irgendeine Instanz ihr
 *                              Level erhöht hat
 *
 * Simulates a Sol sequence directly (manipulating colony_buildings + calling
 * snapshot() repeatedly with $bot->sol advanced by hand) rather than running
 * real bot actions — cheaper and exact, same style as RunReportApBreakdownTest.
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunReportProjectMetricsTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private function setBuilding(int $buildingId, int $instanceId, int $level, int $apSpend): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => $instanceId],
            ['level' => $level, 'ap_spend' => $apSpend, 'status_points' => 20, 'tile_x' => 5, 'tile_y' => 5]
        );
    }

    public function test_project_duration_measured_from_first_spend_to_level_increase(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        // Sol 1: nothing invested yet.
        $bot->sol = 1;
        $this->setBuilding(46, 1, level: 0, apSpend: 0);
        $report->snapshot($bot);

        // Sol 2: investment begins (ap_spend>0), level still 0.
        $bot->sol = 2;
        $this->setBuilding(46, 1, level: 0, apSpend: 10);
        $report->snapshot($bot);

        // Sol 3: still investing.
        $bot->sol = 3;
        $this->setBuilding(46, 1, level: 0, apSpend: 20);
        $report->snapshot($bot);

        // Sol 4: project completes — level increases, ap_spend resets.
        $bot->sol = 4;
        $this->setBuilding(46, 1, level: 1, apSpend: 0);
        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertContains(2, $data['project_metrics']['project_durations'],
            'Duration must be completion_sol(4) - start_sol(2) = 2');
        $this->assertSame(4, $data['project_metrics']['last_completion_sol']);
    }

    public function test_two_independent_instances_each_get_their_own_duration(): void
    {
        $bot = BotSession::boot($this, 2);
        $report = new RunReport(2);

        $bot->sol = 1;
        $this->setBuilding(46, 1, level: 0, apSpend: 5);
        $this->setBuilding(31, 1, level: 2, apSpend: 0);
        $report->snapshot($bot);

        $bot->sol = 2;
        $this->setBuilding(46, 1, level: 1, apSpend: 0); // completes after 1 Sol
        $this->setBuilding(31, 1, level: 2, apSpend: 8); // starts investing
        $report->snapshot($bot);

        $bot->sol = 5;
        $this->setBuilding(46, 1, level: 1, apSpend: 0);
        $this->setBuilding(31, 1, level: 3, apSpend: 0); // completes after 3 Sole (sol2->sol5)
        $report->snapshot($bot);

        $data = $report->build($bot);

        sort($data['project_metrics']['project_durations']);
        $this->assertSame([1, 3], $data['project_metrics']['project_durations']);
        $this->assertSame(5, $data['project_metrics']['last_completion_sol']);
    }

    public function test_median_concurrent_projects_counts_active_ap_spend_per_sol(): void
    {
        $bot = BotSession::boot($this, 3);
        $report = new RunReport(3);

        $bot->sol = 1;
        $this->setBuilding(46, 1, level: 0, apSpend: 5);  // 1 active
        $this->setBuilding(31, 1, level: 0, apSpend: 0);
        $report->snapshot($bot);

        $bot->sol = 2;
        $this->setBuilding(46, 1, level: 0, apSpend: 10); // 2 active
        $this->setBuilding(31, 1, level: 0, apSpend: 3);
        $report->snapshot($bot);

        $bot->sol = 3;
        $this->setBuilding(46, 1, level: 1, apSpend: 0);  // 0 active
        $this->setBuilding(31, 1, level: 0, apSpend: 0);
        $report->snapshot($bot);

        $data = $report->build($bot);

        // Sols: [1, 2, 0] -> median 1.
        $this->assertEqualsWithDelta(1.0, $data['project_metrics']['median_concurrent_projects'], 0.001);
    }

    public function test_no_projects_yields_empty_metrics_not_an_error(): void
    {
        $bot = BotSession::boot($this, 4);
        $report = new RunReport(4);

        $bot->sol = 1;
        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertSame([], $data['project_metrics']['project_durations']);
        $this->assertNull($data['project_metrics']['last_completion_sol']);
        $this->assertSame(0.0, $data['project_metrics']['median_concurrent_projects']);
    }
}
