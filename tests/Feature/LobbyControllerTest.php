<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * LobbyController::newRun() is already heavily covered by LobbyNewRunTest — this
 * file covers the rest: data() (listing + the resultRunId jump-to-result hint),
 * abandon(), start(), and newRun()'s "no colony" fallback.
 */
class LobbyControllerTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Seed data ships an active run (id 1) for this user — clear it so each
        // test starts from a known run-less state.
        Run::where('user_id', self::USER_ID)->delete();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function makeRun(string $status, ?string $startedAt = '2026-01-01 00:00:00'): Run
    {
        return Run::create([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'current_tick' => 5,
            'status' => $status,
            'started_at' => $startedAt,
            'phase' => 1,
        ]);
    }

    // ── index() ───────────────────────────────────────────────────────────

    public function test_index_lists_pending_active_and_finished_runs(): void
    {
        $this->makeRun('active', startedAt: null); // pending
        $this->makeRun('completed');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertOk();
        $response->assertJsonCount(1, 'pending');
        $response->assertJsonCount(1, 'finished');
    }

    public function test_index_shows_active_run_without_redirect(): void
    {
        $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertOk();
        $response->assertJsonCount(1, 'active');
        $response->assertJsonPath('resultRunId', null);
    }

    public function test_index_points_to_result_when_latest_run_just_ended(): void
    {
        $run = $this->makeRun('completed');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        // The former redirect is now the resultRunId hint the frontend follows.
        $response->assertOk();
        $response->assertJsonPath('resultRunId', $run->id);
    }

    public function test_index_does_not_point_to_result_when_an_active_run_still_exists(): void
    {
        // A finished run exists, but so does an active one — must not jump to the result.
        $this->makeRun('completed');
        $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertOk();
        $response->assertJsonPath('resultRunId', null);
    }

    public function test_index_includes_finished_run_highscore_data(): void
    {
        $run = $this->makeRun('completed');
        $run->update(['score' => 4200, 'ended_at' => now()]);
        $this->makeRun('active'); // avoid the auto-redirect-to-result branch

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertJsonPath('finishedRuns.0.score', 4200);
    }

    // ── abandon() ─────────────────────────────────────────────────────────

    public function test_abandon_marks_active_run_as_failed(): void
    {
        $run = $this->makeRun('active');

        $response = $this->actingAs($this->user())->post(route('lobby.abandon', $run->id));

        $response->assertOk();
        $response->assertJson(['ok' => true, 'message' => __('lobby.abandon_success')]);
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->ended_at);
    }

    public function test_abandon_rejects_non_active_run(): void
    {
        $run = $this->makeRun('completed');

        $response = $this->actingAs($this->user())->post(route('lobby.abandon', $run->id));

        $response->assertStatus(422);
        $response->assertJson(['ok' => false, 'message' => __('lobby.abandon_not_active')]);
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_abandon_forbids_other_users_run(): void
    {
        $foreign = $this->createForeignColony();
        $run = Run::create([
            'user_id' => $foreign['user_id'],
            'colony_id' => $foreign['colony_id'],
            'current_tick' => 1,
            'status' => 'active',
            'phase' => 1,
        ]);

        $response = $this->actingAs($this->user())->post(route('lobby.abandon', $run->id));

        $response->assertForbidden();
    }

    // ── start() ───────────────────────────────────────────────────────────

    public function test_start_marks_pending_run_as_started(): void
    {
        $run = $this->makeRun('active', startedAt: null);

        $response = $this->actingAs($this->user())->post(route('lobby.start'));

        $response->assertOk();
        $response->assertJson(['ok' => true]);
        $this->assertNotNull($run->fresh()->started_at);
    }

    public function test_start_without_pending_run_returns_404(): void
    {
        $response = $this->actingAs($this->user())->post(route('lobby.start'));

        $response->assertNotFound();
    }

    // ── newRun(): "no colony" fallback ───────────────────────────────────────

    public function test_new_run_without_colony_shows_error(): void
    {
        DB::table('glx_colonies')->where('user_id', self::USER_ID)->update(['user_id' => null]);

        $response = $this->actingAs($this->user())->post(route('run.new'));

        $response->assertStatus(422);
        $response->assertJson(['ok' => false, 'message' => __('run.new_run_no_colony')]);
    }
}
