<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** tools/phase1-budget.php: AP per bot rule and Rg budget up to the Phase-1 end, per opening. */
class Phase1BudgetToolTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/p1budget_'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
    }

    private function report(string $opening, int $seed, int $p2, array $log, array $perSol, int $start = 300): void
    {
        file_put_contents("{$this->dir}/default.utility-{$opening}-{$seed}-20261016_100000.json", json_encode([
            'seed' => $seed, 'profile' => 'default', 'opening' => $opening, 'engine' => 'utility',
            'outcome' => ['status' => 'active', 'fail_reason' => null],
            'phase2_start_sol' => $p2,
            'log' => $log,
            'regolith_ledger' => ['start_stock' => $start, 'per_sol' => $perSol],
        ]));
    }

    private function run_tool(string $extra = ''): array
    {
        $out = shell_exec('php '.escapeshellarg(dirname(__DIR__, 2).'/tools/phase1-budget.php')
            .' --dir='.escapeshellarg($this->dir).' --min=100 --json '.$extra.' 2>/dev/null');

        return json_decode((string) $out, true) ?? [];
    }

    public function test_sums_ap_per_rule_only_until_phase1_end_and_ignores_failed_actions(): void
    {
        $log = [
            ['sol' => 1, 'rule' => 'explore_tile', 'ok' => true, 'ap_before' => 8, 'ap_after' => 6],
            ['sol' => 2, 'rule' => 'invest_cc', 'ok' => true, 'ap_before' => 8, 'ap_after' => 0],
            ['sol' => 2, 'rule' => 'explore_tile', 'ok' => false, 'ap_before' => 0, 'ap_after' => 0],
            ['sol' => 5, 'rule' => 'explore_tile', 'ok' => true, 'ap_before' => 8, 'ap_after' => 5], // after Phase 1 end
        ];
        $perSol = [['sol' => 1, 'in' => [], 'out' => []], ['sol' => 2, 'in' => ['harvester_base' => 50], 'out' => ['build_place' => 90]]];
        $this->report('labor', 1, 3, $log, $perSol);

        $res = $this->run_tool();

        $this->assertSame(2.0, (float) $res['labor']['ap_by_rule']['explore_tile']);
        $this->assertSame(8.0, (float) $res['labor']['ap_by_rule']['invest_cc']);
        $this->assertSame(1, $res['labor']['runs']);
    }

    public function test_rg_slack_is_available_minus_given_minimum(): void
    {
        $perSol = [
            ['sol' => 1, 'in' => ['harvester_base' => 40], 'out' => ['build_place' => 20]],
            ['sol' => 2, 'in' => ['harvester_base' => 60], 'out' => []],
            ['sol' => 9, 'in' => ['harvester_base' => 999], 'out' => []], // after Phase 1 end, ignored
        ];
        $this->report('hangar', 1, 3, [], $perSol, 300);

        $res = $this->run_tool();

        $this->assertSame(400.0, (float) $res['hangar']['rg_available']['median']); // 300 + 40 + 60
        $this->assertSame(300.0, (float) $res['hangar']['rg_slack']['median']);     // 400 - min 100
        $this->assertSame(['build_place' => 20.0], array_map('floatval', $res['hangar']['rg_out_by_sink']));
    }

    public function test_runs_without_phase2_start_are_counted_as_deadline_fail(): void
    {
        $this->report('cantina', 1, 0, [], []);
        $res = $this->run_tool();

        $this->assertSame(1, $res['cantina']['fails']);
        $this->assertSame(0, $res['cantina']['runs']);
    }

    public function test_since_filter_uses_utc_timestamp_from_file_name(): void
    {
        $this->report('labor', 1, 3, [], []);
        $res = $this->run_tool('--since=20261016_110000');

        $this->assertSame([], $res);
    }
}
