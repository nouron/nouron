<?php

namespace Tests\Feature;

use App\Models\Run;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Measurement mode for the playtest bot: with game.bypass.victory a run keeps
 * going after two completed objectives, so batches see when every objective
 * metric would have been reached.
 */
class GameTickVictoryBypassTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_completed_objectives_end_the_run(): void
    {
        $run = $this->runWithTwoCompletedObjectives();

        Artisan::call('game:tick', ['--run' => $run->id, '--tick' => 41]);

        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_victory_bypass_keeps_the_run_active(): void
    {
        config(['game.bypass.victory' => true]);
        $run = $this->runWithTwoCompletedObjectives();

        Artisan::call('game:tick', ['--run' => $run->id, '--tick' => 41]);

        $this->assertSame('active', $run->fresh()->status);
    }

    private function runWithTwoCompletedObjectives(): Run
    {
        $this->app->make(TestSeeder::class)->run();
        DB::table('runs')->delete();

        $run = Run::create([
            'user_id' => 3,
            'colony_id' => 1,
            'status' => 'active',
            'phase' => 2,
            'phase2_start_tick' => 39,
            'current_tick' => 40,
            'started_at' => now(),
        ]);

        foreach (['task_engineering_output', 'task_senior_advisors'] as $taskKey) {
            DB::table('run_objectives')->insert([
                'run_id' => $run->id, 'task_key' => $taskKey, 'target_value' => 1,
                'current_value' => 1, 'completed_at' => 40,
            ]);
        }

        return $run;
    }
}
