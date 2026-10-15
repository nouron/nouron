<?php

namespace Tests\Unit;

use App\Support\OpeningComparison;
use PHPUnit\Framework\TestCase;

/**
 * Regolith ledger KPIs (budget spec E.1/E.2, step 1): mean Rg inflow per Sol
 * (Sols 1-12 / 13-25, from the regolith_sources buckets), the Harvester share
 * of that inflow (Sols 13-25) and the number of resource_limit rejections.
 */
class OpeningComparisonRegolithKpiTest extends TestCase
{
    /** @param  callable(int): array<string, int>  $sources  sol => regolith_sources */
    private function report(callable $sources, array $log = [], int $lastSol = 25): array
    {
        $sols = [];
        for ($s = 1; $s <= $lastSol; $s++) {
            $sols[] = ['sol' => $s, 'trust' => 2, 'cc_level' => 2, 'ap_unspent' => 2, 'ap' => ['inflow' => 16],
                'regolith_sources' => $sources($s), 'buildings' => [], 'researches' => []];
        }

        return ['seed' => 1, 'profile' => 'default', 'opening' => 'labor', 'phase2_start_sol' => 16,
            'outcome' => ['status' => 'failed', 'sols' => $lastSol], 'log' => $log, 'sols' => $sols];
    }

    public function test_inflow_per_sol_is_the_mean_of_all_source_buckets_per_window(): void
    {
        $m = OpeningComparison::metrics($this->report(fn (int $s) => $s <= 12
            ? ['harvester' => 10, 'trade' => 2]
            : ['harvester' => 15, 'geology' => 3, 'import' => 2]));

        $this->assertSame([12.0, 20.0], [$m['rg_in_1_12'], $m['rg_in_13_25']]);
        $this->assertSame(75.0, $m['rg_harvester_share']);
    }

    public function test_windows_without_snapshots_or_inflow_are_null(): void
    {
        $m = OpeningComparison::metrics($this->report(fn () => ['harvester' => 0], lastSol: 12));

        $this->assertSame(0.0, $m['rg_in_1_12']);
        $this->assertNull($m['rg_in_13_25']);
        $this->assertNull($m['rg_harvester_share']);
    }

    public function test_counts_resource_limit_rejections(): void
    {
        $log = [
            ['sol' => 3, 'rule' => 'place_building', 'ok' => false, 'error' => 'resource_limit'],
            ['sol' => 4, 'rule' => 'invest_cc', 'ok' => false, 'error' => 'resource_limit'],
            ['sol' => 4, 'rule' => 'invest_cc', 'ok' => false, 'error' => 'ap_limit'],
            ['sol' => 5, 'rule' => 'invest_cc', 'ok' => true, 'error' => null],
        ];

        $this->assertSame(2, OpeningComparison::metrics($this->report(fn () => ['harvester' => 5], $log))['rg_resource_limit']);
    }

    public function test_compare_reports_the_kpis_and_an_info_check(): void
    {
        $result = OpeningComparison::fromReports([$this->report(fn () => ['harvester' => 8])], ['labor'])->compare();

        $this->assertSame(8.0, $result['kpis']['rg_in_1_12']['labor']['median']);
        $this->assertSame('info', $result['checks']['RG']['status']);
        $this->assertStringContainsString('labor 8 / 8', $result['checks']['RG']['detail']);
    }
}
