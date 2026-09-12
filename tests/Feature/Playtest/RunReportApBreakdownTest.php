<?php

namespace Tests\Feature\Playtest;

/**
 * A30/B1a: RunReport::snapshot() must categorize this Sol's AP-spend by rule
 * name — repair / project / action — using the ap_before/ap_after each log
 * entry carries (BotSession::act(), see BotSessionApTrackingTest). Isolated
 * from the real bot rules by injecting synthetic log entries directly, since
 * BotSession::$log is public and boot() gives a real, cheap Sol-1 fixture.
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunReportApBreakdownTest extends TestCase
{
    use RefreshDatabase;

    private function pushEntry(BotSession $bot, string $rule, int $apBefore, int $apAfter, bool $ok = true): void
    {
        $bot->log[] = [
            'sol' => $bot->sol,
            'rule' => $rule,
            'url' => '',
            'status' => $ok ? 200 : 422,
            'ok' => $ok,
            'error' => $ok ? null : 'rejected',
            'ap_before' => $apBefore,
            'ap_after' => $apAfter,
            'regolith_before' => 0,
            'regolith_after' => 0,
            'organics_before' => 0,
            'organics_after' => 0,
        ];
    }

    public function test_snapshot_categorizes_ap_spend_by_rule_name(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        $this->pushEntry($bot, 'repair_critical', 20, 19);
        $this->pushEntry($bot, 'invest_cc', 19, 15);
        $this->pushEntry($bot, 'explore_tile', 15, 14);
        // Rejected action: DB unchanged, delta 0, must not count anywhere.
        $this->pushEntry($bot, 'invest_production', 14, 14, ok: false);
        // Unmapped rule (e.g. hire_advisor, Credits-only): must not count.
        $this->pushEntry($bot, 'hire_advisor', 14, 14);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $ap = $data['sols'][0]['ap'];
        $this->assertSame(1, $ap['repair_spent'], 'repair_critical delta (20-19) must land in repair_spent');
        $this->assertSame(4, $ap['project_spent'], 'invest_cc delta (19-15) must land in project_spent');
        $this->assertSame(1, $ap['action_spent'], 'explore_tile delta (15-14) must land in action_spent');
        $this->assertArrayHasKey('inflow', $ap, 'ap.inflow (total pool this tick) must be present');
        $this->assertArrayHasKey('unspent', $ap, 'ap.unspent must be present');
    }

    public function test_snapshot_only_aggregates_current_sol_entries(): void
    {
        $bot = BotSession::boot($this, 2);
        $report = new RunReport(2);

        // A stale entry from a PREVIOUS Sol must not leak into this Sol's breakdown.
        $bot->sol = 1;
        $this->pushEntry($bot, 'repair_critical', 20, 10);

        $bot->sol = 2;
        $this->pushEntry($bot, 'repair_critical', 9, 8);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(1, $data['sols'][0]['ap']['repair_spent'],
            'Only the current Sol\'s log entries may be aggregated, not the whole run\'s history');
    }
}
