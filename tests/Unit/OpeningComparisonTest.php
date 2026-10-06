<?php

namespace Tests\Unit;

use App\Support\OpeningComparison;
use PHPUnit\Framework\TestCase;

/**
 * T30 step 0: equivalence criteria K1-K7 (spec 2026-10-05 section 2.1) computed
 * from synthetic RunReport::build() arrays. Every expected value is derived by
 * hand from the report built in the test.
 */
class OpeningComparisonTest extends TestCase
{
    private const PATH_BUILDING = ['labor' => 31, 'hangar' => 44, 'cantina' => 52];

    /**
     * Builds a report in RunReport::build() shape with Sols 1..$lastSol.
     *
     * Options:
     *  - lv1: Sol from which the opening's path building has level 1 (placed at level 0 one Sol earlier)
     *  - log: list of [sol, rule, ok]
     *  - unspent: [sol => ap_unspent] (default 2), inflow is always 16
     *  - research_lv1: Sol from which a knowledge has level 1
     *  - mission: [sol => regolith from missions], trade: [sol => regolith from trade]
     *  - regolith: [sol => regolith] (default 50)
     *  - sol_next: [sol => regolith_after] of the ok 'sol_next' log entry (start-of-Sol balance)
     *  - trust: [sol => trust] (default 2)
     *  - building: path building id override, also: [building_id => lv1 Sol] extra buildings
     *  - p2, status, sols, last
     */
    private function report(string $opening, int $seed, array $o = []): array
    {
        $last = $o['last'] ?? 15;
        $lv1 = $o['lv1'] ?? null;
        $building = self::PATH_BUILDING[$opening] ?? 31;

        $sols = [];
        for ($s = 1; $s <= $last; $s++) {
            $buildings = ['25:1' => ['level' => 2, 'ap_spend' => 0]];
            foreach (($lv1 !== null ? [($o['building'] ?? $building) => $lv1] : []) + ($o['also'] ?? []) as $id => $at) {
                if ($s >= $at - 1) {
                    $buildings["{$id}:1"] = ['level' => $s >= $at ? 1 : 0, 'ap_spend' => 0];
                }
            }
            $researches = [];
            if (isset($o['research_lv1'])) {
                $researches[90] = ['level' => $s >= $o['research_lv1'] ? 1 : 0, 'ap_spend' => 0];
            }
            $unspent = $o['unspent'][$s] ?? 2;
            $sols[] = [
                'sol' => $s,
                'regolith' => $o['regolith'][$s] ?? 50,
                'trust' => $o['trust'][$s] ?? 2,
                'credits' => 2000,
                'ap' => ['total' => $unspent, 'inflow' => 16, 'repair_spent' => 0, 'project_spent' => 0, 'action_spent' => 16 - $unspent, 'unspent' => $unspent],
                'ap_unspent' => $unspent,
                'cc_level' => 2,
                'advisors' => 1,
                'regolith_sources' => [
                    'harvester' => 16,
                    'mission' => $o['mission'][$s] ?? 0,
                    'trade' => $o['trade'][$s] ?? 0,
                    'event' => 0,
                ],
                'buildings' => $buildings,
                'researches' => $researches,
                'ships' => [],
                'ship_states' => [],
            ];
        }

        $log = [];
        foreach ($o['log'] ?? [] as [$sol, $rule, $ok]) {
            $log[] = ['sol' => $sol, 'rule' => $rule, 'ok' => $ok, 'error' => $ok ? null : 'rejected'];
        }
        foreach ($o['sol_next'] ?? [] as $sol => $regolithAfter) {
            $log[] = ['sol' => $sol, 'rule' => 'sol_next', 'ok' => true, 'error' => null, 'regolith_before' => $regolithAfter - 16, 'regolith_after' => $regolithAfter];
        }

        return [
            'seed' => $seed,
            'profile' => 'default',
            'opening' => $opening,
            'outcome' => [
                'status' => $o['status'] ?? 'failed',
                'fail_reason' => ($o['status'] ?? 'failed') === 'failed' ? 'time_limit' : null,
                'sols' => $o['sols'] ?? $last,
                'score' => 0,
            ],
            'phase2_start_sol' => array_key_exists('p2', $o) ? $o['p2'] : 16,
            'objectives' => [],
            'log' => $log,
            'sols' => $sols,
        ];
    }

    // --- K1 -------------------------------------------------------------

    public function test_k1_labor_is_first_research_sol_minus_sciencelab_level1_sol(): void
    {
        // Sciencelab Lv1 at Sol 3, first successful research_knowledge at Sol 3 -> 0.
        $r = $this->report('labor', 1, ['lv1' => 3, 'log' => [[2, 'explore_tile', true], [3, 'research_knowledge', true], [4, 'research_knowledge', true]]]);

        $this->assertSame(0, OpeningComparison::metrics($r)['k1']);
    }

    public function test_k1_hangar_ignores_rejected_actions_and_counts_ship_purchase(): void
    {
        // Hangar Lv1 at Sol 3; request_ship rejected at Sol 4, succeeds at Sol 5 -> 5 - 3 = 2.
        $r = $this->report('hangar', 1, ['lv1' => 3, 'log' => [[4, 'request_ship', false], [5, 'request_ship', true], [7, 'dispatch_mission', true]]]);

        $this->assertSame(2, OpeningComparison::metrics($r)['k1']);
    }

    public function test_k1_cantina_counts_first_bar_action_and_ignores_other_paths(): void
    {
        // Bar Lv1 at Sol 4; research at Sol 4 is not a cantina action; accept_bar_offer at Sol 5 -> 1.
        $r = $this->report('cantina', 1, ['lv1' => 4, 'log' => [[4, 'research_knowledge', true], [5, 'accept_bar_offer', true]]]);

        $this->assertSame(1, OpeningComparison::metrics($r)['k1']);
    }

    public function test_k1_is_null_when_path_building_or_action_never_happens(): void
    {
        $noBuilding = $this->report('hangar', 1, ['log' => [[5, 'request_ship', true]]]);
        $noAction = $this->report('hangar', 1, ['lv1' => 3]);

        $this->assertNull(OpeningComparison::metrics($noBuilding)['k1']);
        $this->assertNull(OpeningComparison::metrics($noAction)['k1']);
    }

    public function test_auto_uses_the_path_building_that_reaches_level1_first(): void
    {
        // auto, Hangar Lv1 at Sol 3, Sciencelab only at Sol 6: hangar path -> request_ship Sol 4 -> 1.
        $r = $this->report('auto', 1, ['lv1' => 3, 'building' => 44, 'also' => [31 => 6], 'log' => [[4, 'request_ship', true], [6, 'research_knowledge', true]]]);

        $this->assertSame(1, OpeningComparison::metrics($r)['k1']);
    }

    public function test_auto_tie_in_same_sol_resolves_to_labor(): void
    {
        // Hangar and Sciencelab both Lv1 at Sol 3: labor wins by constant order -> research Sol 5 -> 2.
        $r = $this->report('auto', 1, ['lv1' => 3, 'building' => 44, 'also' => [31 => 3], 'log' => [[3, 'request_ship', true], [5, 'research_knowledge', true]]]);

        $this->assertSame(2, OpeningComparison::metrics($r)['k1']);
    }

    // --- K2 -------------------------------------------------------------

    public function test_k2_labor_is_first_knowledge_level_minus_level1_sol(): void
    {
        // Sciencelab Lv1 at Sol 3, first knowledge at level 1 in the Sol-5 snapshot -> 2.
        $r = $this->report('labor', 1, ['lv1' => 3, 'research_lv1' => 5]);

        $this->assertSame(2, OpeningComparison::metrics($r)['k2']);
    }

    public function test_k2_hangar_is_first_mission_regolith_and_cantina_first_trade_regolith(): void
    {
        // Hangar Lv1 Sol 3, mission regolith first at Sol 9 (trade regolith at Sol 4 does not count) -> 6.
        $hangar = $this->report('hangar', 1, ['lv1' => 3, 'mission' => [9 => 30], 'trade' => [4 => 10]]);
        // Bar Lv1 Sol 4, trade regolith first at Sol 6 -> 2.
        $cantina = $this->report('cantina', 1, ['lv1' => 4, 'trade' => [6 => 5], 'mission' => [5 => 30]]);

        $this->assertSame(6, OpeningComparison::metrics($hangar)['k2']);
        $this->assertSame(2, OpeningComparison::metrics($cantina)['k2']);
    }

    public function test_k2_cantina_counts_trust_rise_in_a_sol_with_bar_action(): void
    {
        // Bar Lv1 Sol 4. Sol 5: trust rises (2 -> 3) without a bar action -> no.
        // Sol 6: bar action without trust rise (3 -> 3) -> no.
        // Sol 7: accept_bar_encounter and trust 3 -> 5 -> yield, 7 - 4 = 3.
        $r = $this->report('cantina', 1, [
            'lv1' => 4,
            'trust' => [5 => 3, 6 => 3, 7 => 5, 8 => 5, 9 => 5, 10 => 5, 11 => 5, 12 => 5, 13 => 5, 14 => 5, 15 => 5],
            'log' => [[6, 'accept_bar_offer', true], [7, 'accept_bar_encounter', true]],
        ]);

        $this->assertSame(3, OpeningComparison::metrics($r)['k2']);
    }

    public function test_k2_trust_rise_does_not_count_for_hangar(): void
    {
        $r = $this->report('hangar', 1, ['lv1' => 3, 'trust' => [5 => 6], 'log' => [[5, 'request_ship', true]]]);

        $this->assertNull(OpeningComparison::metrics($r)['k2']);
    }

    public function test_k2_is_null_when_no_path_yield_ever(): void
    {
        $r = $this->report('hangar', 1, ['lv1' => 3]);

        $this->assertNull(OpeningComparison::metrics($r)['k2']);
    }

    // --- K3 -------------------------------------------------------------

    public function test_k3_is_phase2_start_sol(): void
    {
        $this->assertSame(17, OpeningComparison::metrics($this->report('labor', 1, ['p2' => 17]))['k3']);
        $this->assertNull(OpeningComparison::metrics($this->report('labor', 1, ['p2' => null]))['k3']);
    }

    // --- K4 -------------------------------------------------------------

    public function test_k4_sums_unspent_ap_over_sols_4_to_12_only(): void
    {
        // Sols 4..12 unspent: 4:10, 5:0, 6:16, 7..12 default 2 (6 Sols) = 10+0+16+12 = 38.
        // Sol 3 (16) and Sol 13 (16) lie outside the window.
        $r = $this->report('labor', 1, ['unspent' => [3 => 16, 4 => 10, 5 => 0, 6 => 16, 13 => 16]]);

        $this->assertSame(38.0, OpeningComparison::metrics($r)['k4']);
    }

    // --- K5 -------------------------------------------------------------

    public function test_k5_counts_sols_with_half_ap_unspent_and_no_build_or_path_action(): void
    {
        $r = $this->report('labor', 1, [
            'unspent' => [
                3 => 16,  // outside window
                5 => 10,  // empty: 10 >= 8, only repair
                6 => 8,   // empty: exactly 50 %
                7 => 10,  // not empty: invest_production
                8 => 7,   // not empty: < 50 %
                9 => 12,  // empty: place_building was rejected
                10 => 12, // not empty: accept_bar_offer is a path action (any path)
            ],
            'log' => [
                [5, 'repair_maintenance', true],
                [6, 'explore_tile', true],
                [7, 'invest_production', true],
                [9, 'place_building', false],
                [10, 'accept_bar_offer', true],
            ],
        ]);

        $this->assertSame(3, OpeningComparison::metrics($r)['k5']);
    }

    public function test_k5_window_includes_sol_15_but_not_sol_16(): void
    {
        $r = $this->report('labor', 1, ['last' => 17, 'unspent' => [15 => 10, 16 => 10]]);

        $this->assertSame(1, OpeningComparison::metrics($r)['k5']);
    }

    // --- K6 -------------------------------------------------------------

    public function test_k6_is_regolith_at_start_of_sol_10_from_sol_next_entry(): void
    {
        // sol_next into Sol 10 leaves 63 Rg; a build placed during Sol 10 drops the
        // Sol-10 snapshot to 20 — K6 must report the start-of-Sol value 63.
        $r = $this->report('labor', 1, [
            'sol_next' => [9 => 47, 10 => 63, 11 => 40],
            'regolith' => [9 => 47, 10 => 20, 11 => 40],
            'log' => [[10, 'place_building', true]],
        ]);

        $this->assertSame(63, OpeningComparison::metrics($r)['k6']);
    }

    public function test_k6_is_null_not_zero_when_run_ended_before_sol_10(): void
    {
        $r = $this->report('labor', 1, ['last' => 8, 'sols' => 8, 'sol_next' => [7 => 40, 8 => 56]]);

        $this->assertNull(OpeningComparison::metrics($r)['k6']);
    }

    // --- K7 -------------------------------------------------------------

    public function test_k7_won_and_sols(): void
    {
        $won = $this->report('labor', 1, ['status' => 'completed', 'sols' => 42]);
        $lost = $this->report('labor', 1, ['status' => 'failed', 'sols' => 100]);

        $this->assertSame(['won' => true, 'sols' => 42], OpeningComparison::metrics($won)['k7']);
        $this->assertSame(['won' => false, 'sols' => 100], OpeningComparison::metrics($lost)['k7']);
    }

    // --- Comparison -------------------------------------------------------

    /**
     * Seeds 1 and 2 are finished in all three openings. Seed 3 lacks cantina,
     * seed 4 has an unfinished (status active) cantina run.
     */
    private function batch(array $p2 = ['labor' => [16, 18], 'hangar' => [19, 21], 'cantina' => [17, 19]], array $extra = []): array
    {
        $reports = [];
        foreach (['labor', 'hangar', 'cantina'] as $opening) {
            foreach ([1, 2] as $i => $seed) {
                $reports[] = $this->report($opening, $seed, ($extra[$opening] ?? []) + [
                    'p2' => $p2[$opening][$i],
                    'sol_next' => [10 => ['labor' => [100, 80], 'hangar' => [70, 60], 'cantina' => [95, 85]][$opening][$i]],
                    'status' => $opening === 'labor' && $seed === 1 ? 'completed' : 'failed',
                    'sols' => $opening === 'labor' && $seed === 1 ? 40 : 100,
                ]);
            }
        }
        $reports[] = $this->report('labor', 3);
        $reports[] = $this->report('hangar', 3);
        $reports[] = $this->report('labor', 4);
        $reports[] = $this->report('hangar', 4);
        $reports[] = $this->report('cantina', 4, ['status' => 'active']);

        return $reports;
    }

    public function test_seeds_not_finished_in_all_openings_are_skipped(): void
    {
        $result = OpeningComparison::fromReports($this->batch())->compare();

        $this->assertSame([1, 2], $result['paired_seeds']);
        $this->assertSame([3, 4], array_keys($result['skipped_seeds']));
        $this->assertSame(['labor', 'hangar', 'cantina'], $result['openings']);
        // The unfinished run is not counted as 0 anywhere: only 2 runs per opening.
        $this->assertSame(2, $result['kpis']['k3']['cantina']['n']);
    }

    public function test_median_range_and_delta_to_labor(): void
    {
        $kpis = OpeningComparison::fromReports($this->batch())->compare()['kpis'];

        // K3 labor 16/18, hangar 19/21 (deltas +3/+3), cantina 17/19 (deltas +1/+1).
        $this->assertEquals(17, $kpis['k3']['labor']['median']);
        $this->assertSame(16, $kpis['k3']['labor']['min']);
        $this->assertSame(18, $kpis['k3']['labor']['max']);
        $this->assertNull($kpis['k3']['labor']['delta']);
        $this->assertEquals(20, $kpis['k3']['hangar']['median']);
        $this->assertEquals(3, $kpis['k3']['hangar']['delta']['median']);
        $this->assertEquals(1, $kpis['k3']['cantina']['delta']['median']);

        // K6 labor 100/80, hangar 70/60 (deltas -30/-20), cantina 95/85 (deltas -5/+5).
        $this->assertEquals(-25, $kpis['k6']['hangar']['delta']['median']);
        $this->assertSame(-30, $kpis['k6']['hangar']['delta']['min']);
        $this->assertSame(-20, $kpis['k6']['hangar']['delta']['max']);
        $this->assertEquals(0, $kpis['k6']['cantina']['delta']['median']);
    }

    public function test_k7_win_rate_per_opening(): void
    {
        $k7 = OpeningComparison::fromReports($this->batch())->compare()['k7'];

        $this->assertEquals(50.0, $k7['labor']['win_rate']);
        $this->assertEquals(40, $k7['labor']['median_win_sol']);
        $this->assertEquals(0.0, $k7['hangar']['win_rate']);
        $this->assertNull($k7['hangar']['median_win_sol']);
    }

    public function test_threshold_check_flags_violated_criteria(): void
    {
        $checks = OpeningComparison::fromReports($this->batch())->compare()['checks'];

        // K3 medians 17/20/18 -> spread 3 > 2.
        $this->assertSame('verfehlt', $checks['K3']['status']);
        // K6 hangar delta median -25 is within 25, cantina 0.
        $this->assertSame('ok', $checks['K6']['status']);
        // K7 labor 50 % vs hangar 0 % -> 50 pp > 10.
        $this->assertSame('verfehlt', $checks['K7']['status']);
        // K1/K2 never reached (no path building in these reports) -> verfehlt, not ok.
        $this->assertSame('verfehlt', $checks['K1']['status']);
        // K5: default 2 of 16 unspent -> no empty Sols anywhere.
        $this->assertSame('ok', $checks['K5']['status']);
    }

    public function test_threshold_check_passes_when_within_corridor(): void
    {
        $checks = OpeningComparison::fromReports($this->batch(['labor' => [16, 18], 'hangar' => [17, 19], 'cantina' => [16, 19]]))->compare()['checks'];

        // Medians 17 / 18 / 17.5, all in 15-20, spread 1.
        $this->assertSame('ok', $checks['K3']['status']);
    }

    public function test_k4_check_flags_delta_above_20_ap(): void
    {
        $this->assertSame('ok', OpeningComparison::fromReports($this->batch())->compare()['checks']['K4']['status']);

        // Hangar leaves 30 instead of 2 AP unspent in Sol 4 -> delta +28 per seed > 20.
        $checks = OpeningComparison::fromReports($this->batch(extra: ['hangar' => ['unspent' => [4 => 30]]]))->compare()['checks'];
        $this->assertSame('verfehlt', $checks['K4']['status']);
    }

    public function test_k2_check_states_counted_yields_and_passes_within_3_sols(): void
    {
        $failed = OpeningComparison::fromReports($this->batch())->compare()['checks']['K2'];
        $this->assertSame('verfehlt', $failed['status']);
        $this->assertStringContainsString('labor: first knowledge level>=1', $failed['detail']);
        $this->assertStringContainsString('hangar: first mission Rg', $failed['detail']);
        $this->assertStringContainsString('cantina: trade Rg or trust rise', $failed['detail']);

        $checks = OpeningComparison::fromReports($this->batch(extra: [
            'labor' => ['lv1' => 3, 'research_lv1' => 5],
            'hangar' => ['lv1' => 3, 'mission' => [6 => 20]],
            'cantina' => ['lv1' => 3, 'trade' => [4 => 5]],
        ]))->compare()['checks'];
        $this->assertSame('ok', $checks['K2']['status']);
    }

    // --- Truncated runs (--until-sol, Task 5) ---

    public function test_truncated_runs_are_paired_and_k7_is_not_measured(): void
    {
        $reports = [];
        foreach (['labor', 'hangar'] as $opening) {
            $r = $this->report($opening, 1, ['status' => 'active', 'sols' => 20, 'last' => 19, 'p2' => 16, 'sol_next' => [10 => 80]]);
            $r['truncated_at_sol'] = 20;
            $reports[] = $r;
        }
        $reports[] = $this->report('labor', 2, ['status' => 'active']);
        $reports[] = $this->report('hangar', 2, ['status' => 'active']);

        $result = OpeningComparison::fromReports($reports, ['labor', 'hangar'])->compare();

        $this->assertSame([1], $result['paired_seeds']);
        $this->assertSame([2], array_keys($result['skipped_seeds']));
        $this->assertSame(16, $result['kpis']['k3']['labor']['median']);
        $this->assertSame(80, $result['kpis']['k6']['hangar']['median']);
        $this->assertNull($result['metrics']['labor'][1]['k7']);
        $this->assertNull($result['k7']['labor']['win_rate']);
        $this->assertSame(0, $result['k7']['labor']['runs']);
        $this->assertSame(1, $result['k7']['labor']['truncated']);
        $this->assertSame('nicht gemessen', $result['checks']['K7']['status']);
    }

    public function test_truncated_run_before_sol_10_has_null_k6_not_zero(): void
    {
        $r = $this->report('labor', 1, ['status' => 'active', 'last' => 8]);
        $r['truncated_at_sol'] = 9;

        $this->assertNull(OpeningComparison::metrics($r)['k6']);
        $this->assertNull(OpeningComparison::metrics($r)['k7']);
    }

    public function test_k7_check_still_runs_on_regular_runs_mixed_with_no_truncation(): void
    {
        $result = OpeningComparison::fromReports($this->batch())->compare();

        $this->assertNotSame('nicht gemessen', $result['checks']['K7']['status']);
    }

    // --- Carry-over from the Task 4 re-review ---

    public function test_k2_cantina_ignores_a_failed_cantina_action_in_a_sol_with_an_unrelated_trust_rise(): void
    {
        // Bar Lv1 Sol 4; Sol 6: trust 2 -> 5 but the only bar action of that Sol was rejected.
        $r = $this->report('cantina', 1, [
            'lv1' => 4,
            'trust' => [6 => 5, 7 => 5, 8 => 5, 9 => 5, 10 => 5, 11 => 5, 12 => 5, 13 => 5, 14 => 5, 15 => 5],
            'log' => [[6, 'accept_bar_offer', false]],
        ]);

        $this->assertNull(OpeningComparison::metrics($r)['k2']);
    }

    public function test_k6_ignores_a_failed_sol_next_entry(): void
    {
        $r = $this->report('labor', 1);
        $r['log'][] = ['sol' => 10, 'rule' => 'sol_next', 'ok' => false, 'error' => 'rejected', 'regolith_after' => 999];

        $this->assertNull(OpeningComparison::metrics($r)['k6']);
    }

    // --- Fix round 1: mixed truncated + full runs ---

    private function k7Check(array $byOpening): array
    {
        $reports = [];
        foreach ($byOpening as $opening => $truncated) {
            $o = $truncated ? ['status' => 'active', 'sols' => 20] : ['status' => 'completed', 'sols' => 40];
            $r = $this->report($opening, 1, $o + ['p2' => 16]);
            if ($truncated) {
                $r['truncated_at_sol'] = 20;
            }
            $reports[] = $r;
        }

        return OpeningComparison::fromReports($reports, array_keys($byOpening))->compare()['checks']['K7'];
    }

    public function test_k7_is_not_measured_when_all_runs_are_truncated(): void
    {
        $check = $this->k7Check(['labor' => true, 'hangar' => true]);

        $this->assertSame('nicht gemessen', $check['status']);
    }

    public function test_k7_is_not_measured_when_one_opening_has_only_truncated_runs(): void
    {
        $check = $this->k7Check(['labor' => false, 'hangar' => true]);

        $this->assertSame('nicht gemessen', $check['status']);
        $this->assertStringContainsString('hangar', $check['detail']);
        $this->assertStringNotContainsString('labor', $check['detail']);
    }

    public function test_k7_is_not_measured_when_the_reference_has_only_truncated_runs(): void
    {
        $check = $this->k7Check(['labor' => true, 'hangar' => false]);

        $this->assertSame('nicht gemessen', $check['status']);
        $this->assertStringContainsString('labor', $check['detail']);
    }

    public function test_k7_uses_only_the_full_runs_when_one_opening_mixes_truncated_and_full(): void
    {
        $reports = [];
        foreach (['labor', 'hangar'] as $opening) {
            $reports[] = $this->report($opening, 1, ['status' => 'completed', 'sols' => 40, 'p2' => 16]);
            $t = $this->report($opening, 2, ['status' => 'active', 'sols' => 20, 'p2' => 16]);
            if ($opening === 'hangar') {
                $t['truncated_at_sol'] = 20;
            } else {
                $t = $this->report($opening, 2, ['status' => 'completed', 'sols' => 40, 'p2' => 16]);
            }
            $reports[] = $t;
        }

        $result = OpeningComparison::fromReports($reports, ['labor', 'hangar'])->compare();

        $this->assertNotSame('nicht gemessen', $result['checks']['K7']['status']);
        $this->assertSame(1, $result['k7']['hangar']['runs']);
        $this->assertSame(1, $result['k7']['hangar']['truncated']);
    }
}
