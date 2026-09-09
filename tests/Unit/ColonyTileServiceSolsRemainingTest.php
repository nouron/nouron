<?php

namespace Tests\Unit;

/**
 * ColonyTileService::solsRemaining() — "≈N Sole bis Erschöpfung" estimate
 * for the Harvester constant-yield spec (A25, docs/superpowers/specs/
 * 2026-08-10-harvester-constant-yield-design.md §2).
 *
 *   solsRemaining(tile):
 *     resource_amount <= 0        → null (already exhausted, no countdown)
 *     effectiveRate <= 0          → null (no production, e.g. unconfigured tile type)
 *     otherwise                   → ceil(resource_amount / effectiveRate)
 *
 * Communicated as an ESTIMATE (UI label "ca. N Sole") — trust multiplier and
 * geology level can change before the tile is actually exhausted.
 */
use App\Services\ColonyTileService;
use Tests\TestCase;

class ColonyTileServiceSolsRemainingTest extends TestCase
{
    private ColonyTileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(ColonyTileService::class);
    }

    public function test_returns_null_when_already_exhausted(): void
    {
        $this->assertNull($this->service->solsRemaining('regolith_normal', 0, 300, 0, 1.0));
    }

    public function test_returns_null_for_unconfigured_tile_type(): void
    {
        $this->assertNull($this->service->solsRemaining('terrain_empty', 100, 300, 0, 1.0));
    }

    public function test_rounds_up_to_next_full_sol(): void
    {
        // fresh_yield regolith_normal = 23, rate = 23 * 1.0 = 23. 111 / 23 = 4.826 → 5.
        $this->assertSame(5, $this->service->solsRemaining('regolith_normal', 111, 300, 0, 1.0));
    }

    public function test_exact_division_does_not_add_an_extra_sol(): void
    {
        // 46 / 23 = 2.0 exactly → 2, not 3.
        $this->assertSame(2, $this->service->solsRemaining('regolith_normal', 46, 300, 0, 1.0));
    }

    public function test_geology_bonus_increases_rate_and_lowers_estimate(): void
    {
        $withoutGeology = $this->service->solsRemaining('regolith_normal', 111, 300, 0, 1.0);
        $withGeology = $this->service->solsRemaining('regolith_normal', 111, 300, 5, 1.0);

        $this->assertLessThan($withoutGeology, $withGeology, 'A higher geology bonus must shorten the estimate');
    }

    public function test_trust_multiplier_scales_the_estimate(): void
    {
        // Rate halved by trust penalty → roughly double the sols.
        $fullTrust = $this->service->solsRemaining('regolith_normal', 92, 300, 0, 1.0);
        $halfTrust = $this->service->solsRemaining('regolith_normal', 92, 300, 0, 0.5);

        $this->assertGreaterThan($fullTrust, $halfTrust);
    }

    public function test_returns_null_when_effective_rate_rounds_to_zero(): void
    {
        // A trust multiplier of 0 makes the effective rate 0 — no meaningful countdown.
        $this->assertNull($this->service->solsRemaining('regolith_normal', 100, 300, 0, 0.0));
    }
}
