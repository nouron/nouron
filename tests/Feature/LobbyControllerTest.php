<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * LobbyController::newRun() is already heavily covered by LobbyNewRunTest — this
 * file covers the rest: index() (listing + the auto-redirect-to-result branch),
 * abandon(), start(), and newRun()'s "no colony" fallback.
 */
class LobbyControllerTest extends TestCase
{
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
        $response->assertViewHas('pending', fn ($c) => $c->count() === 1);
        $response->assertViewHas('finished', fn ($c) => $c->count() === 1);
    }

    public function test_index_shows_active_run_without_redirect(): void
    {
        $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertOk();
        $response->assertViewHas('active', fn ($c) => $c->count() === 1);
    }

    public function test_index_redirects_to_result_when_latest_run_just_ended(): void
    {
        $run = $this->makeRun('completed');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertRedirect(route('run.result', $run->id));
    }

    public function test_index_does_not_redirect_when_an_active_run_still_exists(): void
    {
        // A finished run exists, but so does an active one — must not redirect.
        $this->makeRun('completed');
        $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $response->assertOk();
    }

    public function test_index_includes_finished_run_highscore_data(): void
    {
        $run = $this->makeRun('completed');
        $run->update(['score' => 4200, 'ended_at' => now()]);
        $this->makeRun('active'); // avoid the auto-redirect-to-result branch

        $response = $this->actingAs($this->user())->get(route('lobby'));

        $finishedRuns = $response->viewData('finishedRuns');
        $this->assertSame(4200, $finishedRuns->first()['score']);
    }

    // ── abandon() ─────────────────────────────────────────────────────────

    public function test_abandon_marks_active_run_as_failed(): void
    {
        $run = $this->makeRun('active');

        $response = $this->actingAs($this->user())->post(route('lobby.abandon', $run->id));

        $response->assertRedirect(route('lobby'));
        $this->assertSame('failed', $run->fresh()->status);
        $this->assertNotNull($run->fresh()->ended_at);
    }

    public function test_abandon_rejects_non_active_run(): void
    {
        $run = $this->makeRun('completed');

        $response = $this->actingAs($this->user())->post(route('lobby.abandon', $run->id));

        $response->assertRedirect(route('lobby'));
        $response->assertSessionHas('error');
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_abandon_forbids_other_users_run(): void
    {
        $run = Run::create([
            'user_id' => 1, // Homer
            'colony_id' => 2,
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

        $response->assertRedirect(route('colony.view'));
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

        $response->assertRedirect(route('lobby'));
        $response->assertSessionHas('error');
    }
}
