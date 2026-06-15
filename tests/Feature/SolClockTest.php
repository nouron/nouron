<?php

namespace Tests\Feature;

/**
 * SolClockTest — regression guard for the "stuck at Sol 1" bug.
 *
 * Root cause (fixed): the in-game Sol number was derived from the time-based
 * global tick minus colony.since_tick, which does not move when the player
 * ends a Sol. The canonical clock is now runs.current_tick: run start
 * (current_tick = 0) is "Sol 1", and each "Sol beenden" advances it by one.
 *
 * These tests assert that:
 *   - the colony hex-view Sol chip reflects runs.current_tick + 1
 *   - advancing the Sol moves the displayed Sol number forward
 *   - AP locked on the previous tick no longer count after the Sol advances
 *
 * Fixture: Bart (user_id=3) owns colony_id=1 (Springfield) with a seeded
 * active, started run.
 */

use App\Models\Run;
use App\Models\User;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolClockTest extends TestCase
{
    use RefreshDatabase;

    private const BART_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::BART_ID);
    }

    private function setRunTick(int $tick): Run
    {
        $run = Run::where('user_id', self::BART_ID)->where('status', 'active')->firstOrFail();
        $run->update(['current_tick' => $tick]);

        return $run->refresh();
    }

    /**
     * Replace game:tick with a no-op so the HTTP layer can be exercised without
     * running the full tick pipeline. Artisan::fake() is unavailable in this stack.
     */
    private function fakeGameTick(): void
    {
        $this->app->make(Kernel::class)->registerCommand(
            new class extends Command
            {
                protected $signature = 'game:tick {--run=} {--tick=}';

                protected $description = 'No-op stub for SolClockTest';

                public function handle(): int
                {
                    return self::SUCCESS;
                }
            }
        );
    }

    /**
     * A run at current_tick = 0 must render as "Sol 1" in the colony view,
     * regardless of the colony's since_tick.
     */
    public function test_colony_view_shows_sol_one_at_run_start(): void
    {
        $this->setRunTick(0);

        $response = $this->actingAs($this->bart())->get(route('colony.view'));

        $response->assertOk();
        $response->assertSee('currentSol: 1,', false);
    }

    /**
     * A run at current_tick = 5 must render as "Sol 6".
     */
    public function test_colony_view_shows_run_tick_plus_one(): void
    {
        $this->setRunTick(5);

        $response = $this->actingAs($this->bart())->get(route('colony.view'));

        $response->assertOk();
        $response->assertSee('currentSol: 6,', false);
    }

    /**
     * Core regression: ending a Sol advances the displayed Sol number.
     * current_tick 0 → Sol 1; after POST sol.next → current_tick 1 → Sol 2.
     */
    public function test_ending_sol_advances_displayed_sol(): void
    {
        $this->fakeGameTick();
        $run = $this->setRunTick(0);

        $before = $this->actingAs($this->bart())->get(route('colony.view'));
        $before->assertSee('currentSol: 1,', false);

        $this->actingAs($this->bart())->from('/colony/view')->post(route('sol.next'));

        $this->assertDatabaseHas('runs', ['id' => $run->id, 'current_tick' => 1]);

        $after = $this->actingAs($this->bart())->get(route('colony.view'));
        $after->assertSee('currentSol: 2,', false);
    }

    /**
     * AP locked on the previous tick must not count once the Sol advances.
     *
     * Under `php artisan test` the container is in console context, so the
     * request-scoped TickService bind falls back to the time-based clock.
     * We therefore inject an explicit run-scoped TickService to simulate what
     * happens in a real web request (where the bind uses runs.current_tick).
     */
    public function test_ap_locked_on_previous_tick_does_not_count_after_advance(): void
    {
        // Simulate the web request clock at run tick 0.
        $this->app->instance(TickService::class, new TickService(0));
        $personell = $this->app->make(PersonellService::class);

        $available = $personell->getAvailableActionPoints('construction', self::COLONY_ID);
        $this->assertGreaterThan(0, $available, 'Expected construction AP at run start');

        // Lock all available construction AP on tick 0.
        $this->assertTrue($personell->lockActionPoints('construction', self::COLONY_ID, $available));
        $this->assertSame(0, $personell->getAvailableActionPoints('construction', self::COLONY_ID));

        // Advance the Sol: the clock moves to run tick 1.
        $this->app->instance(TickService::class, new TickService(1));
        $personell = $this->app->make(PersonellService::class);

        // The lock recorded against tick 0 must no longer reduce the budget.
        $this->assertSame(
            $available,
            $personell->getAvailableActionPoints('construction', self::COLONY_ID),
            'AP should regenerate on the next Sol — previous tick locks must not carry over'
        );

        // The previous lock row still exists, just keyed to the old tick.
        $this->assertDatabaseHas('locked_actionpoints', ['tick' => 0]);
    }
}
