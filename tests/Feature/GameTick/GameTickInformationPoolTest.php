<?php

namespace Tests\Feature\GameTick;

/**
 * GameTick — Deva & Lenn Vier-Ausgänge-Pool spawn roll (A42).
 *
 * generateInformationEncounterForColony() rolls independently of, and in
 * parallel with, the bar_encounters (A35) / bar_concerns (A41) special-event
 * slot — it must fire regardless of whether that shared slot fired this tick.
 */

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameTickInformationPoolTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield

    private const BAR_BUILDING_ID = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 1, 'status_points' => 999]);
    }

    public function test_information_encounter_is_rolled_even_when_bar_encounter_also_fires(): void
    {
        config([
            'game.bar.encounter.spawn_chance_per_level.1' => 1.0,
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
        ]);

        $this->artisan('game:tick', ['--tick' => 5000])->assertExitCode(0);

        $this->assertSame(1, DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->count());
        $this->assertSame(1, DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_information_encounter_is_rolled_when_bar_encounter_does_not_fire(): void
    {
        config([
            'game.bar.encounter.spawn_chance_per_level.1' => 0.0,
            'game.bar.concern.spawn_chance_per_level.1' => 0.0,
            'game.bar.information_pool.spawn_chance_per_tick' => 1.0,
        ]);

        $this->artisan('game:tick', ['--tick' => 5001])->assertExitCode(0);

        $this->assertSame(0, DB::table('bar_encounters')->where('colony_id', self::COLONY_ID)->count());
        $this->assertSame(1, DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_no_information_encounter_when_bar_not_built(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 0]);
        config(['game.bar.information_pool.spawn_chance_per_tick' => 1.0]);

        $this->artisan('game:tick', ['--tick' => 5002])->assertExitCode(0);

        $this->assertSame(0, DB::table('bar_information_encounters')->where('colony_id', self::COLONY_ID)->count());
    }
}
