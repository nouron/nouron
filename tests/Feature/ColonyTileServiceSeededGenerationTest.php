<?php

namespace Tests\Feature;

use App\Services\ColonyTileService;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A38 root cause: ColonyTileService::randomTileType()/randomizeOuterRingRows()
 * used unseeded random_int()/shuffle()/array_rand() for the Sol-1 starting
 * map — documented as deliberate roguelike variance, but this also meant
 * `game:playtest --seeds=X` never actually reproduced anything but the
 * objective draw. The same nominal "seed" produced wildly different runs
 * even with zero concurrency involved (found 2026-09-13).
 *
 * Fix: thread a caller-supplied seed through map generation so the SAME
 * seed reproduces the SAME starting map (needed for the playtest tool to
 * mean what its name says), while real players (OnboardingService::
 * setupNewPlayer(), LobbyController's reset — no seed passed) keep true
 * per-run randomness, unaffected.
 *
 * Covered scenarios:
 *  - test_same_seed_produces_identical_outer_ring_rows
 *  - test_different_seeds_produce_different_outer_ring_rows
 *  - test_reset_colony_to_sol1_with_explicit_seed_is_reproducible
 */
class ColonyTileServiceSeededGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    public function test_same_seed_produces_identical_outer_ring_rows(): void
    {
        $service = app(ColonyTileService::class);

        $first = $service->randomizeOuterRingRows(12345);
        $second = $service->randomizeOuterRingRows(12345);

        $this->assertSame($first, $second);
    }

    public function test_different_seeds_produce_different_outer_ring_rows(): void
    {
        $service = app(ColonyTileService::class);

        $first = $service->randomizeOuterRingRows(1);
        $second = $service->randomizeOuterRingRows(2);

        $this->assertNotSame($first, $second);
    }

    public function test_reset_colony_to_sol1_with_explicit_seed_is_reproducible(): void
    {
        $this->app->make(TestSeeder::class)->run();
        $onboarding = app(OnboardingService::class);

        $onboarding->resetColonyToSol1(self::USER_ID, self::COLONY_ID, rngSeed: 777);
        $firstTiles = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->get(['q', 'r', 'ring', 'tile_type', 'is_explored'])
            ->toArray();

        $onboarding->resetColonyToSol1(self::USER_ID, self::COLONY_ID, rngSeed: 777);
        $secondTiles = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->get(['q', 'r', 'ring', 'tile_type', 'is_explored'])
            ->toArray();

        $this->assertEquals($firstTiles, $secondTiles);
    }
}
