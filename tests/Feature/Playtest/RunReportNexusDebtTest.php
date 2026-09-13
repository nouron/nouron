<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A34 prep: each Sol snapshot must report the run's current nexus_debt, and
 * the build() outcome its final value — without this, the
 * nexus_debt_fail_threshold (config/game.php) can't be recalibrated from real
 * playtest data, only from the pass/fail outcome at the very end.
 */
class RunReportNexusDebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_reports_current_nexus_debt(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        DB::table('runs')->where('id', $bot->runId)->update(['nexus_debt' => 4500]);

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);
        $snapshot = $report->build($bot)['sols'][0];

        $this->assertSame(4500, $snapshot['nexus_debt']);
    }

    public function test_snapshot_nexus_debt_reflects_changes_across_sols(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        DB::table('runs')->where('id', $bot->runId)->update(['nexus_debt' => 3000]);
        $report->snapshot($bot);

        DB::table('runs')->where('id', $bot->runId)->update(['nexus_debt' => 6000]);
        $report->snapshot($bot);

        $sols = $report->build($bot)['sols'];
        $this->assertSame(3000, $sols[0]['nexus_debt']);
        $this->assertSame(6000, $sols[1]['nexus_debt']);
    }

    public function test_build_reports_final_nexus_debt_in_outcome(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        DB::table('runs')->where('id', $bot->runId)->update(['nexus_debt' => 7777]);

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);
        $built = $report->build($bot);

        $this->assertSame(7777, $built['outcome']['nexus_debt_final']);
    }
}
