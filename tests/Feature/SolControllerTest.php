<?php

namespace Tests\Feature;

/**
 * SolController feature tests — POST /sol/next (route: sol.next).
 *
 * Covered scenarios:
 *
 *  AUTH GUARD
 *    - test_unauthenticated_request_redirects_to_login
 *
 *  NO ACTIVE RUN → 404
 *    - test_returns_404_when_no_active_run_exists
 *    - test_returns_404_when_run_is_completed
 *    - test_returns_404_when_run_is_failed
 *
 *  HAPPY PATH
 *    - test_increments_current_tick_and_redirects_with_flash
 *    - test_run_with_zero_tick_increments_to_one
 *    - test_multiple_posts_increment_tick_sequentially
 *    - test_only_active_run_is_advanced_when_mixed_statuses_exist
 *
 *  ADVERSARIAL
 *    - test_cannot_trigger_other_users_run
 *
 * Note on Artisan isolation:
 *   Artisan::fake() is not available in the Laravel 12 / PHPUnit 11 version used
 *   here.  Instead we bind a no-op closure to the console kernel via
 *   $this->mock() so that game:tick is intercepted without touching the DB.
 *   Tests that DO want the real tick pipeline (none, currently) can skip this.
 *
 * Note on TestSeeder:
 *   testdata.sqlite.sql inserts one active run for Bart (user_id=3, colony_id=1,
 *   current_tick=1938, status='active').  Tests that control the run state
 *   delete this row in their setUp via deleteExistingRuns() before creating
 *   their own fixture.  Tests that only need "no active run" use a freshly
 *   created user who never had a run in the seed.
 */

use App\Models\Run;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SolControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    /** Bart — user_id=3, owns colony_id=1 (Springfield). Has a seeded active run. */
    private const BART_ID = 3;

    /** Springfield — colony_id=1, tied to Bart in the seed. */
    private const COLONY_ID = 1;

    // ── Setup ─────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bart(): User
    {
        return User::find(self::BART_ID);
    }

    /**
     * Create a fresh user with no colony or run in the seed.
     * Used for tests that require "authenticated but no run".
     */
    private function freshUserWithNoRun(): User
    {
        return User::factory()->create();
    }

    /**
     * Remove all runs for Bart so tests can start with a clean slate.
     */
    private function deleteBartRuns(): void
    {
        DB::table('runs')->where('user_id', self::BART_ID)->delete();
    }

    /**
     * Insert a run for the given user and return the model.
     */
    private function insertRun(int $userId, int $colonyId, string $status = 'active', int $currentTick = 5): Run
    {
        return Run::create([
            'user_id' => $userId,
            'colony_id' => $colonyId,
            'status' => $status,
            'current_tick' => $currentTick,
        ]);
    }

    /**
     * Swap the game:tick Artisan command with a no-op so that the HTTP layer
     * can be tested without executing the full tick pipeline.
     *
     * Approach: replace the 'game:tick' command in the Laravel application
     * with a command whose handle() immediately returns SUCCESS.  Because
     * Artisan::fake() is not available in this stack, we register a mock
     * command at the same signature name before the test runs.
     */
    private function fakeGameTick(): void
    {
        $this->app->make(Kernel::class)
            ->registerCommand(
                new class extends Command
                {
                    protected $signature = 'game:tick {--run=} {--tick=}';

                    protected $description = 'No-op stub for SolControllerTest';

                    public function handle(): int
                    {
                        return self::SUCCESS;
                    }
                }
            );
    }

    // ── AUTH GUARD ────────────────────────────────────────────────────────────

    /**
     * An unauthenticated POST must be redirected to the login page (302).
     * The auth middleware short-circuits before the controller executes,
     * so no run or colony fixture is needed.
     */
    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $response = $this->post(route('sol.next'));

        $response->assertRedirect(route('login'));
    }

    // ── NO ACTIVE RUN → 404 ───────────────────────────────────────────────────

    /**
     * Authenticated user with zero runs → firstOrFail throws → 404.
     */
    public function test_returns_404_when_no_active_run_exists(): void
    {
        // Use a fresh user who has no run in the seed data.
        $user = $this->freshUserWithNoRun();

        $response = $this->actingAs($user)->post(route('sol.next'));

        $response->assertNotFound();
    }

    /**
     * A run with status='completed' is invisible to the active-run query → 404.
     */
    public function test_returns_404_when_run_is_completed(): void
    {
        $user = $this->freshUserWithNoRun();
        $this->insertRun($user->user_id, self::COLONY_ID, 'completed', 10);

        $response = $this->actingAs($user)->post(route('sol.next'));

        $response->assertNotFound();
    }

    /**
     * A run with status='failed' is invisible to the active-run query → 404.
     */
    public function test_returns_404_when_run_is_failed(): void
    {
        $user = $this->freshUserWithNoRun();
        $this->insertRun($user->user_id, self::COLONY_ID, 'failed', 7);

        $response = $this->actingAs($user)->post(route('sol.next'));

        $response->assertNotFound();
    }

    // ── HAPPY PATH ────────────────────────────────────────────────────────────

    /**
     * Successful Sol trigger:
     *   - current_tick is incremented by exactly 1 in the DB
     *   - response redirects back (302)
     *   - flash 'sol_advanced' equals the new tick value
     *   - game:tick stub is satisfied without polluting other DB state
     */
    public function test_increments_current_tick_and_redirects_with_flash(): void
    {
        $this->fakeGameTick();

        // Replace the seeded run with a controlled one at tick=5.
        $this->deleteBartRuns();
        $run = $this->insertRun(self::BART_ID, self::COLONY_ID, 'active', 5);

        $response = $this->actingAs($this->bart())
            ->from('/colony')
            ->post(route('sol.next'));

        $response->assertRedirect('/colony');
        $response->assertSessionHas('sol_advanced', 6);

        $this->assertDatabaseHas('runs', [
            'id' => $run->id,
            'current_tick' => 6,
            'status' => 'active',
        ]);
    }

    /**
     * A fresh run starting at tick=0 must reach tick=1 after one Sol trigger.
     * Verifies the increment works at the boundary value zero.
     */
    public function test_run_with_zero_tick_increments_to_one(): void
    {
        $this->fakeGameTick();

        $this->deleteBartRuns();
        $run = $this->insertRun(self::BART_ID, self::COLONY_ID, 'active', 0);

        $response = $this->actingAs($this->bart())
            ->from('/colony')
            ->post(route('sol.next'));

        $response->assertRedirect('/colony');
        $response->assertSessionHas('sol_advanced', 1);

        $this->assertDatabaseHas('runs', [
            'id' => $run->id,
            'current_tick' => 1,
        ]);
    }

    /**
     * Posting twice in sequence must increment the tick by 2 in total.
     *
     * Verifies that the SQL-level increment() is applied on the persisted
     * value each time, not on a stale in-memory model value.
     */
    public function test_multiple_posts_increment_tick_sequentially(): void
    {
        $this->fakeGameTick();

        $this->deleteBartRuns();
        $run = $this->insertRun(self::BART_ID, self::COLONY_ID, 'active', 10);

        $this->actingAs($this->bart())->from('/colony')->post(route('sol.next'));
        $this->actingAs($this->bart())->from('/colony')->post(route('sol.next'));

        $this->assertDatabaseHas('runs', [
            'id' => $run->id,
            'current_tick' => 12,
        ]);
    }

    /**
     * When the same user has both a completed and an active run, only the
     * active one must be advanced.  The completed run must remain untouched.
     */
    public function test_only_active_run_is_advanced_when_mixed_statuses_exist(): void
    {
        $this->fakeGameTick();

        $this->deleteBartRuns();
        $completedRun = $this->insertRun(self::BART_ID, self::COLONY_ID, 'completed', 50);
        $activeRun = $this->insertRun(self::BART_ID, self::COLONY_ID, 'active', 5);

        $response = $this->actingAs($this->bart())
            ->from('/colony')
            ->post(route('sol.next'));

        $response->assertSessionHas('sol_advanced', 6);

        $this->assertDatabaseHas('runs', ['id' => $activeRun->id,    'current_tick' => 6]);
        $this->assertDatabaseHas('runs', ['id' => $completedRun->id, 'current_tick' => 50]);
    }

    // ── ADVERSARIAL ───────────────────────────────────────────────────────────

    /**
     * Player A (Bart) must not advance Player B's run.
     *
     * Bart posts to /sol/next but only a second player has an active run.
     * The controller filters by auth()->id() → no match for Bart → 404.
     * The other player's tick must remain unchanged.
     */
    public function test_cannot_trigger_other_users_run(): void
    {
        // Remove Bart's seeded run so he has nothing active.
        $this->deleteBartRuns();

        // Give Marge (user_id=1) an active run on Springfield.
        $margeRun = $this->insertRun(1, self::COLONY_ID, 'active', 3);

        // Bart posts — should get 404 because he has no active run.
        $response = $this->actingAs($this->bart())->post(route('sol.next'));

        $response->assertNotFound();

        // Marge's run must be completely untouched.
        $this->assertDatabaseHas('runs', [
            'id' => $margeRun->id,
            'current_tick' => 3,
        ]);
    }
}
