<?php

namespace Tests\Feature\Colony;

use App\Services\ColonyTileService;
use App\Services\FindPoolService;
use Tests\TestCase;

class FindPoolServiceTest extends TestCase
{
    private function rows(int $seed): array
    {
        return app(ColonyTileService::class)->randomizeOuterRingRows($seed);
    }

    public function test_assigns_the_fixed_mix_to_empty_ring_2_and_3_tiles_only(): void
    {
        foreach ([1, 2, 3, 4, 5, 6, 7, 8, 99, 12345] as $seed) {
            $rows = app(FindPoolService::class)->assign($this->rows($seed), $seed);
            $signals = array_values(array_filter($rows, fn ($r) => ($r['event_type'] ?? null) !== null));

            $this->assertCount(7, $signals, "seed {$seed}");
            $counts = array_count_values(array_column($signals, 'event_type'));
            ksort($counts);
            $this->assertSame(['find_false' => 2, 'find_large' => 1, 'find_medium' => 1, 'find_small' => 3], $counts, "seed {$seed}");
            foreach ($signals as $s) {
                $this->assertSame('terrain_empty', $s['tile_type']);
                $this->assertContains($s['ring'], [2, 3]);
            }
        }
    }

    public function test_does_not_change_any_other_field(): void
    {
        $base = $this->rows(7);
        $out = app(FindPoolService::class)->assign($base, 7);
        $this->assertCount(count($base), $out);
        foreach ($base as $i => $row) {
            $o = $out[$i];
            unset($o['event_type']);
            $this->assertSame($row, $o);
        }
    }

    public function test_is_deterministic_per_seed_and_varies_between_seeds(): void
    {
        $svc = app(FindPoolService::class);
        $this->assertSame($svc->assign($this->rows(3), 3), $svc->assign($this->rows(3), 3));
        $this->assertNotSame(
            array_column($svc->assign($this->rows(3), 3), 'event_type'),
            array_column($svc->assign($this->rows(4), 4), 'event_type'),
        );
    }

    public function test_large_find_prefers_ring_3_when_available(): void
    {
        foreach (range(1, 40) as $seed) {
            $rows = $this->rows($seed);
            $ring3Empty = count(array_filter($rows, fn ($r) => $r['ring'] === 3 && $r['tile_type'] === 'terrain_empty'));
            $out = app(FindPoolService::class)->assign($rows, $seed);
            $large = array_values(array_filter($out, fn ($r) => ($r['event_type'] ?? null) === 'find_large'))[0];
            if ($ring3Empty > 0) {
                $this->assertSame(3, $large['ring'], "seed {$seed}");
            }
        }
    }

    public function test_fewer_candidates_than_signals_does_not_throw(): void
    {
        $rows = array_map(fn ($r) => array_merge($r, ['tile_type' => 'hazard_x']), $this->rows(1));
        $rows[0]['tile_type'] = 'terrain_empty';
        $rows[0]['ring'] = 2;
        $out = app(FindPoolService::class)->assign($rows, 1);
        $this->assertCount(1, array_filter($out, fn ($r) => ($r['event_type'] ?? null) !== null));
    }

    public function test_does_not_shift_the_existing_map_stream(): void
    {
        $svc = app(ColonyTileService::class);
        $this->assertSame($svc->randomizeOuterRingRows(5), $svc->randomizeOuterRingRows(5));
        $before = $svc->randomizeOuterRingRows(5);
        app(FindPoolService::class)->assign($before, 5);
        $this->assertSame($before, $svc->randomizeOuterRingRows(5));
    }

    public function test_huge_seed_does_not_overflow(): void
    {
        $out = app(FindPoolService::class)->assign($this->rows(9), PHP_INT_MAX);
        $this->assertCount(7, array_filter($out, fn ($r) => ($r['event_type'] ?? null) !== null));
    }
}
