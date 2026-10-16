<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/** tools/ap-idle.php: unspent AP share and milestone Sols per opening from game:playtest reports. */
class ApIdleToolTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/apidle_'.uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
    }

    /** @param array<int, array{0:int,1:int,2:int,3:int}> $sols [sol, inflow, unspent, cc_level] */
    private function report(string $opening, int $seed, array $sols, array $labFromSol = [], string $profile = 'default', string $engine = 'utility'): void
    {
        $rows = [];
        foreach ($sols as [$sol, $in, $un, $cc]) {
            $b = ['25:1' => ['level' => $cc, 'ap_spend' => 0]];
            if (isset($labFromSol[0]) && $sol >= $labFromSol[0]) {
                $b['31:1'] = ['level' => 1, 'ap_spend' => 0];
            }
            $rows[] = ['sol' => $sol, 'ap' => ['inflow' => $in, 'unspent' => $un], 'cc_level' => $cc, 'buildings' => $b];
        }
        file_put_contents("{$this->dir}/{$profile}.{$engine}-{$opening}-{$seed}-20261016_100000.json", json_encode([
            'seed' => $seed, 'profile' => $profile, 'opening' => $opening, 'engine' => $engine, 'sols' => $rows,
        ]));
    }

    private function run_tool(string $extra = ''): array
    {
        $out = shell_exec('php '.escapeshellarg(dirname(__DIR__, 2).'/tools/ap-idle.php')
            .' --dir='.escapeshellarg($this->dir).' --json '.$extra.' 2>/dev/null');

        return json_decode((string) $out, true) ?? [];
    }

    public function test_idle_share_counts_only_sols_at_or_above_the_threshold_inside_the_window(): void
    {
        $this->report('hangar', 1, [
            [11, 10, 9, 2],   // before the window
            [12, 10, 5, 2],   // idle (>= 5)
            [13, 10, 4, 2],   // below threshold: not idle
            [14, 10, 10, 3],  // idle
            [46, 10, 10, 3],  // after the window
        ]);

        $res = $this->run_tool('--from=12 --to=45 --threshold=5');

        $this->assertSame(1, $res['hangar']['runs']);
        $this->assertEqualsWithDelta(15 / 30, $res['hangar']['idle_share'], 1e-9);
        $this->assertSame(15.0, (float) $res['hangar']['idle_ap_median']);
        $this->assertSame(2.0, (float) $res['hangar']['idle_sols_median']);
    }

    public function test_milestones_report_the_median_first_sol_and_the_never_count(): void
    {
        $this->report('hangar', 1, [[12, 10, 0, 2], [20, 10, 0, 3], [30, 10, 0, 4]], [20]);
        $this->report('hangar', 2, [[12, 10, 0, 2], [24, 10, 0, 3], [36, 10, 0, 3]], [24]);
        $this->report('hangar', 3, [[12, 10, 0, 2], [40, 10, 0, 3]]);   // never a Sciencelab, never CC Lv4

        $res = $this->run_tool();

        $this->assertSame(['median' => 22.0, 'never' => 1], $this->num($res['hangar']['sciencelab_first_sol']));
        $this->assertSame(['median' => 24.0, 'never' => 0], $this->num($res['hangar']['cc3_first_sol']));
        $this->assertSame(['median' => 30.0, 'never' => 2], $this->num($res['hangar']['cc4_first_sol']));
        $this->assertSame(['median' => null, 'never' => 3], $res['hangar']['cc5_first_sol']);
    }

    public function test_filters_by_profile_and_engine_and_groups_per_opening(): void
    {
        $this->report('labor', 1, [[12, 10, 0, 2]]);
        $this->report('cantina', 1, [[12, 10, 5, 2]]);
        $this->report('labor', 2, [[12, 10, 0, 2]], [], 'thrifty');
        $this->report('labor', 3, [[12, 10, 0, 2]], [], 'default', 'legacy');

        $res = $this->run_tool('--profile=default --engine=utility');

        $this->assertSame(1, $res['labor']['runs']);
        $this->assertSame(1, $res['cantina']['runs']);
        $this->assertEqualsWithDelta(0.5, $res['cantina']['idle_share'], 1e-9);
    }

    private function num(array $m): array
    {
        return ['median' => $m['median'] === null ? null : (float) $m['median'], 'never' => $m['never']];
    }
}
