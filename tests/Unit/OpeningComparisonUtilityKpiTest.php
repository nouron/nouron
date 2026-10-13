<?php

namespace Tests\Unit;

use App\Support\OpeningComparison;
use PHPUnit\Framework\TestCase;

/** Spec 2.11 + P0: K18-K20 and K23 from synthetic RunReport::build() arrays. */
class OpeningComparisonUtilityKpiTest extends TestCase
{
    /**
     * @param  array<int, int>  $pathLevel  sol => level of the opening's path building (absent = not placed)
     * @param  array<int, int>  $cc  sol => cc_level (default 2)
     */
    private function report(string $opening, int $seed, array $pathLevel, array $cc = [], ?array $summary = null): array
    {
        $id = ['labor' => 31, 'hangar' => 44, 'cantina' => 52][$opening];
        $sols = [];
        for ($s = 1; $s <= 15; $s++) {
            $sols[] = [
                'sol' => $s, 'trust' => 2, 'cc_level' => $cc[$s] ?? 2, 'ap_unspent' => 2, 'ap' => ['inflow' => 16],
                'regolith_sources' => ['harvester' => 16, 'mission' => 0, 'trade' => 0, 'find' => 0, 'find_flight' => 0, 'event' => 0],
                'buildings' => isset($pathLevel[$s]) ? ["{$id}:1" => ['level' => $pathLevel[$s], 'ap_spend' => 0]] : [],
                'researches' => [],
            ];
        }
        $report = ['seed' => $seed, 'profile' => 'default', 'opening' => $opening, 'phase2_start_sol' => 16,
            'outcome' => ['status' => 'active', 'sols' => 16], 'truncated_at_sol' => 16, 'log' => [], 'sols' => $sols];
        if ($summary !== null) {
            $report['utility_summary'] = $summary;
        }

        return $report;
    }

    public function test_k19_reads_the_sols_of_the_own_path_building_levels(): void
    {
        $levels = [];
        foreach (range(4, 15) as $s) {
            $levels[$s] = $s >= 14 ? 3 : ($s >= 9 ? 2 : 1);
        }

        $m = OpeningComparison::metrics($this->report('hangar', 1, $levels));

        $this->assertSame([9, 14, 5], [$m['k19_lv2'], $m['k19_lv3'], $m['k19_lv1_sols']]);
    }

    public function test_k19_is_null_when_a_level_is_never_reached(): void
    {
        $m = OpeningComparison::metrics($this->report('cantina', 1, array_fill(4, 12, 1)));

        $this->assertSame([null, null, null], [$m['k19_lv2'], $m['k19_lv3'], $m['k19_lv1_sols']]);
    }

    public function test_k23_is_the_first_sol_with_cc_level_4(): void
    {
        $this->assertSame(12, OpeningComparison::metrics($this->report('labor', 1, [], [12 => 4, 13 => 4]))['k23']);
        $this->assertNull(OpeningComparison::metrics($this->report('labor', 1, []))['k23']);
    }

    public function test_k18_and_k20_come_from_the_utility_summary(): void
    {
        $m = OpeningComparison::metrics($this->report('labor', 1, [], [], ['legacy_share' => 87.5, 'median_decisions_per_sol' => 6.0]));

        $this->assertSame([87.5, 6.0], [$m['k18'], $m['k20']]);
    }

    public function test_legacy_report_without_utility_fields_yields_null_kpis(): void
    {
        $result = OpeningComparison::fromReports([$this->report('labor', 1, []), $this->report('hangar', 1, [])], ['labor', 'hangar'])->compare();

        $this->assertNull($result['metrics']['labor'][1]['k18']);
        $this->assertSame(1, $result['kpis']['k18']['labor']['missing']);
        $this->assertSame('info', $result['checks']['K18']['status']);
        $this->assertSame('info', $result['checks']['K19']['status']);
        $this->assertSame('info', $result['checks']['K23']['status']);
    }
}
