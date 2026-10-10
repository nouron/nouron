<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * T30 step 0: game:playtest-compare reads report JSONs (newest per profile,
 * opening, seed), runs OpeningComparison and prints KPIs + threshold checks.
 * Reports live in a temp directory (--dir), never in storage/logs/playtest.
 */
class PlaytestCompareTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/playtest-compare-'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function writeReport(string $profile, string $opening, int $seed, string $stamp, int $p2, string $status = 'failed', ?int $truncatedAt = null): void
    {
        $sols = [];
        for ($s = 1; $s <= 15; $s++) {
            $sols[] = [
                'sol' => $s, 'regolith' => 50, 'ap' => ['total' => 2, 'inflow' => 16, 'unspent' => 2], 'ap_unspent' => 2,
                'regolith_sources' => ['harvester' => 16, 'mission' => 0, 'trade' => 0, 'event' => 0],
                'buildings' => [], 'researches' => [],
            ];
        }
        $report = [
            'seed' => $seed, 'profile' => $profile, 'opening' => $opening,
            'outcome' => ['status' => $status, 'fail_reason' => null, 'sols' => 100, 'score' => 0],
            'phase2_start_sol' => $p2, 'objectives' => [], 'log' => [], 'sols' => $sols,
        ];
        if ($truncatedAt !== null) {
            $report['truncated_at_sol'] = $truncatedAt;
        }
        file_put_contents("{$this->dir}/{$profile}-{$opening}-{$seed}-{$stamp}.json", json_encode($report));
    }

    private function writeBatch(): void
    {
        foreach (['labor' => 16, 'hangar' => 17, 'cantina' => 18] as $opening => $p2) {
            foreach ([1, 2] as $seed) {
                $this->writeReport('default', $opening, $seed, '20261006_120000', $p2);
            }
        }
    }

    public function test_prints_kpis_and_threshold_check_from_newest_reports(): void
    {
        $this->writeBatch();
        // Older labor report for seed 1 (would make K3 spread fail) must be ignored.
        $this->writeReport('default', 'labor', 1, '20261001_090000', 40);
        // Other profile must be ignored.
        $this->writeReport('thrifty', 'hangar', 1, '20261006_130000', 40);
        // Seed 3 only in labor -> skipped.
        $this->writeReport('default', 'labor', 3, '20261006_120000', 16);

        $this->artisan('game:playtest-compare', ['--dir' => $this->dir])
            ->expectsOutputToContain('Paired seeds: 1, 2')
            ->expectsOutputToContain('Skipped seeds: 3 (hangar: missing, cantina: missing)')
            ->expectsOutputToContain('labor median 16; hangar median 17; cantina median 18')
            ->assertExitCode(0);
    }

    public function test_prints_k11_pool_rg_rows_and_info_status(): void
    {
        $this->writeBatch();

        Artisan::call('game:playtest-compare', ['--dir' => $this->dir]);
        $out = Artisan::output();

        $this->assertStringContainsString('K11 Pool-Rg Sol 10', $out);
        $this->assertStringContainsString('K11 Pool-Rg Sol 25', $out);
        $this->assertMatchesRegularExpression('/K11\s*\|\s*info\s*\|/', $out);
    }

    public function test_threshold_violation_is_reported_as_verfehlt(): void
    {
        $this->writeReport('default', 'labor', 1, '20261006_120000', 16);
        $this->writeReport('default', 'hangar', 1, '20261006_120000', 25);

        $exit = Artisan::call('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar']);

        $this->assertSame(0, $exit);
        // K3 row: medians 16 vs 25 -> spread 9 > 2 and 25 outside 15-20.
        $this->assertMatchesRegularExpression('/K3\s*\|\s*verfehlt\s*\|\s*labor median 16; hangar median 25/', Artisan::output());
    }

    public function test_seed_filter_matches_exactly(): void
    {
        foreach (['labor', 'hangar', 'cantina'] as $opening) {
            $this->writeReport('default', $opening, 1, '20261006_120000', 16);
            $this->writeReport('default', $opening, 11, '20261006_120000', 16);
        }

        Artisan::call('game:playtest-compare', ['--dir' => $this->dir, '--seeds' => '1']);

        $this->assertStringContainsString("Paired seeds: 1\n", Artisan::output());
    }

    public function test_seeds_and_since_filter_reports(): void
    {
        $this->writeBatch();
        $this->writeReport('default', 'labor', 5, '20260901_120000', 16);
        $this->writeReport('default', 'hangar', 5, '20260901_120000', 16);
        $this->writeReport('default', 'cantina', 5, '20260901_120000', 16);

        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--seeds' => '2,5'])
            ->expectsOutputToContain('Paired seeds: 2, 5')
            ->assertExitCode(0);

        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--since' => '2026-10-01'])
            ->expectsOutputToContain('Paired seeds: 1, 2')
            ->assertExitCode(0);
    }

    public function test_invalid_openings_abort(): void
    {
        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,forge'])
            ->expectsOutputToContain('Unknown opening(s): forge')
            ->assertExitCode(1);
    }

    public function test_invalid_since_aborts(): void
    {
        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--since' => 'not-a-date'])
            ->assertExitCode(1);
    }

    public function test_truncated_reports_are_paired_and_k7_is_reported_as_not_measured(): void
    {
        foreach (['labor', 'hangar'] as $opening) {
            $this->writeReport('default', $opening, 1, '20261006_120000', 16, 'active', truncatedAt: 20);
        }
        // Active and not truncated -> skipped.
        $this->writeReport('default', 'labor', 2, '20261006_120000', 16, 'active');
        $this->writeReport('default', 'hangar', 2, '20261006_120000', 16, 'active');

        $exit = Artisan::call('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar']);

        $this->assertSame(0, $exit);
        $out = Artisan::output();
        $this->assertStringContainsString('Paired seeds: 1', $out);
        $this->assertStringContainsString('Skipped seeds: 2 (labor: unfinished, hangar: unfinished)', $out);
        $this->assertMatchesRegularExpression('/K7\s*\|\s*nicht gemessen/', $out);
    }

    public function test_warns_when_compared_runs_were_truncated_before_sol_21(): void
    {
        foreach (['labor', 'hangar'] as $opening) {
            $this->writeReport('default', $opening, 1, '20261006_120000', 16, 'active', truncatedAt: 12);
        }

        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar'])
            ->expectsOutputToContain('truncated before Sol 21')
            ->assertExitCode(0);
    }

    public function test_no_truncation_warning_at_sol_21_or_later(): void
    {
        foreach (['labor', 'hangar'] as $opening) {
            $this->writeReport('default', $opening, 1, '20261006_120000', 16, 'active', truncatedAt: 21);
        }

        Artisan::call('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar']);

        $this->assertStringNotContainsString('truncated before', Artisan::output());
    }

    public function test_warns_at_exactly_sol_20_because_a_phase2_start_at_sol_20_is_invisible(): void
    {
        foreach (['labor', 'hangar'] as $opening) {
            $this->writeReport('default', $opening, 1, '20261006_120000', 16, 'active', truncatedAt: 20);
        }

        $this->artisan('game:playtest-compare', ['--dir' => $this->dir, '--openings' => 'labor,hangar'])
            ->expectsOutputToContain('truncated before Sol 21')
            ->assertExitCode(0);
    }
}
