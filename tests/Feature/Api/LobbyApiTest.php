<?php

namespace Tests\Feature\Api;

use App\Models\Run;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Lobby and run result JSON API (ADR 0006 stage 4f): same controllers as the Blade
 * pages; the actions answer JSON instead of redirects.
 *
 * Fixture: Bart (user 3) has one active, started run on colony 1.
 */
class LobbyApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        Sanctum::actingAs(User::find(self::USER_ID));
    }

    private function activeRun(): Run
    {
        return Run::where('user_id', self::USER_ID)->where('status', 'active')->firstOrFail();
    }

    public function test_lobby_lists_runs(): void
    {
        $this->getJson('/api/v1/lobby')->assertOk()->assertJsonStructure([
            'active' => [0 => ['id', 'colony_name', 'current_tick', 'tick_limit', 'started_at']],
            'pending', 'finished', 'finishedRuns', 'allowMultiple', 'resultRunId',
        ]);
    }

    public function test_start_and_rename_a_pending_run(): void
    {
        $this->activeRun()->update(['started_at' => null]);

        // Renaming works before the start (the Blade route sat behind run.started and never did).
        $this->patchJson('/api/v1/colony/name', ['name' => 'Neu Haven'])->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('Neu Haven', DB::table('glx_colonies')->where('user_id', self::USER_ID)->value('name'));

        $this->postJson('/api/v1/lobby/start')->assertOk()->assertJsonPath('ok', true);
        $this->assertNotNull($this->activeRun()->fresh()->started_at);
    }

    public function test_abandon_ends_the_run_and_shows_the_result(): void
    {
        $run = $this->activeRun();

        $this->postJson("/api/v1/lobby/{$run->id}/abandon")->assertOk()->assertJsonPath('ok', true);
        $this->assertSame('failed', $run->fresh()->status);

        $this->getJson('/api/v1/lobby')->assertJsonPath('resultRunId', $run->id);
        $this->getJson("/api/v1/run/{$run->id}/result")->assertOk()->assertJsonStructure([
            'status', 'fail_reason', 'score', 'current_tick', 'tick_limit',
            'objectives', // fixture run has none; the page shows a dash then, like Blade
        ]);
    }

    public function test_result_of_a_running_run_is_refused(): void
    {
        $this->getJson("/api/v1/run/{$this->activeRun()->id}/result")->assertStatus(409);
    }
}
