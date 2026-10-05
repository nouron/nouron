<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R5b: runs.rng_seed is random_int(1, PHP_INT_MAX); the tick adds domain salts and
 * tick multiples to it. Without reducing the seed first the sum overflows into a
 * float and seededRoll(int) throws a TypeError on every tick of that run.
 */
class GameTickHugeSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_run_with_the_maximum_rng_seed_still_ticks(): void
    {
        $this->app->make(TestSeeder::class)->run();
        DB::table('runs')->where('colony_id', 1)->update(['rng_seed' => PHP_INT_MAX]);

        // Fixture colony 1 has levelled buildings, so storm/instability/plague rolls all run.
        $this->artisan('game:tick', ['--tick' => 11800])->assertExitCode(0);
        $this->artisan('game:tick', ['--tick' => 11801])->assertExitCode(0);
    }
}
