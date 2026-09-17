<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 10b — Charakter-Anliegen roll (A41).
 *
 * generateConcernForColony() is only rolled when the shared encounter slot
 * did NOT fire this tick — at most one Cantina special event per tick, total.
 */
class GameTickBarConcernTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;   // Springfield

    private const BAR_BUILDING_ID = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // status_points pinned to max so buildingDecay (which runs before the
        // Cantina steps in GameTick::handle()) cannot drop the bar's level
        // back down within this test.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 5, 'status_points' => 999]);
    }

    public function test_concern_is_rolled_when_encounter_does_not_fire(): void
    {
        config([
            'game.bar.encounter.spawn_chance_per_level.5' => 0.0,
            'game.bar.concern.spawn_chance_per_level.5' => 1.0,
        ]);

        Artisan::call('game:tick', ['--tick' => 5000]);

        $this->assertSame(1, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_concern_is_not_rolled_when_encounter_fires(): void
    {
        config([
            'game.bar.encounter.spawn_chance_per_level.5' => 1.0,
            'game.bar.concern.spawn_chance_per_level.5' => 1.0,
        ]);

        Artisan::call('game:tick', ['--tick' => 5000]);

        $this->assertSame(1, DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->count());
        $this->assertSame(0, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }
}
