<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * OnboardingService::resetColonyToSol1() must leave exactly one active Run
 * behind — the invariant [[project_singleplayer_scope]] requires ("genau ein
 * aktiver Run"). LobbyController::newRun() already guards against calling
 * this while a run is active, but other callers (BotSession, the
 * game:reset-player dev command) call it directly without that guard. Bug
 * found 2026-08-17: a stale active Run left over from TestSeeder fixtures
 * plus a fresh resetColonyToSol1()-created Run both had status='active',
 * and every ambiguous "the active run" query since then picked the wrong
 * one — bot playtest reports undercounted elapsed ticks by the stale run's
 * current_tick.
 */
class OnboardingResetActiveRunTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_reset_colony_to_sol1_closes_a_pre_existing_active_run(): void
    {
        DB::table('runs')->where('user_id', self::USER_ID)->delete();

        $staleRun = Run::create([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'current_tick' => 5,
            'status' => 'active',
            'phase' => 1,
        ]);

        app(OnboardingService::class)->resetColonyToSol1(self::USER_ID, self::COLONY_ID);

        $staleRun->refresh();
        $this->assertNotSame('active', $staleRun->status, 'Pre-existing active run must be closed, not left dangling');

        $activeRuns = Run::where('user_id', self::USER_ID)->where('status', 'active')->get();
        $this->assertCount(1, $activeRuns, 'Exactly one active run must exist after reset');
        $this->assertSame(0, $activeRuns->first()->current_tick, 'The surviving active run must be the freshly seeded Sol-1 run');
    }
}
