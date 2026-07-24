<?php

namespace Tests\Feature\Console;

use App\Models\Colony;
use App\Models\Run;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ResetPlayer had 0.6% test coverage — the biggest single gap in the codebase.
 * Each scenario method is largely a straight-line sequence of DB writes with
 * little branching, so one successful run per scenario (non-interactive via
 * --yes --scenario=X) exercises the bulk of it; a handful of assertions per
 * scenario confirm the documented resulting state (tick/trust/credits/etc.).
 */
class ResetPlayerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function colony(): Colony
    {
        return Colony::where('user_id', self::USER_ID)->latest('id')->firstOrFail();
    }

    private function activeRun(): Run
    {
        return Run::where('user_id', self::USER_ID)->where('status', 'active')->latest('id')->firstOrFail();
    }

    // ── User/scenario resolution + confirmation ──────────────────────────────

    public function test_unknown_user_by_name_fails(): void
    {
        $this->artisan('game:reset-player', ['user' => 'nobody', '--yes' => true, '--scenario' => 'fresh'])
            ->expectsOutputToContain('User not found: nobody')
            ->assertExitCode(1);
    }

    public function test_unknown_scenario_fails(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'bogus'])
            ->expectsOutputToContain('Unknown scenario: bogus')
            ->assertExitCode(1);
    }

    public function test_resolves_user_by_numeric_id(): void
    {
        $this->artisan('game:reset-player', [
            'user' => (string) self::USER_ID, '--yes' => true, '--scenario' => 'fresh',
        ])->assertExitCode(0);
    }

    public function test_aborts_without_confirmation(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--scenario' => 'fresh'])
            ->expectsConfirmation('Alle Spielerdaten löschen und Szenario anwenden?', 'no')
            ->expectsOutputToContain('Aborted.')
            ->assertExitCode(0);
    }

    // ── fresh ─────────────────────────────────────────────────────────────────

    public function test_fresh_scenario_resets_to_sol1(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'fresh'])
            ->expectsOutputToContain('reset to Sol 1')
            ->assertExitCode(0);

        $run = $this->activeRun();
        $this->assertSame(1, $run->phase);
        $this->assertSame(0, $run->current_tick);
    }

    // ── pre-phase2 ────────────────────────────────────────────────────────────

    public function test_pre_phase2_scenario_sets_documented_state(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'pre-phase2'])
            ->expectsOutputToContain("Scenario 'pre-phase2' applied.")
            ->assertExitCode(0);

        $colony = $this->colony();
        $run = $this->activeRun();

        $this->assertSame(1, $run->phase); // still Phase 1 — one hire short
        $this->assertSame(12, $run->current_tick);
        $this->assertSame(2400, DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits'));
        $this->assertSame(3, DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', 25)->value('level'));
        $this->assertSame(2, DB::table('advisors')->where('colony_id', $colony->id)->count());
    }

    // ── phase2 ────────────────────────────────────────────────────────────────

    public function test_phase2_scenario_transitions_to_phase2_with_objectives(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'phase2'])
            ->assertExitCode(0);

        $run = $this->activeRun();
        $this->assertSame(2, $run->phase);
        $this->assertSame(15, $run->current_tick);
        $this->assertSame(12, $run->phase2_start_tick);
        $this->assertSame(3, $run->objectives()->count());
        $this->assertSame(3, DB::table('advisors')->where('colony_id', $run->colony_id)->count());
    }

    // ── near-fail-trust ───────────────────────────────────────────────────────

    public function test_near_fail_trust_scenario_sets_low_trust(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'near-fail-trust'])
            ->assertExitCode(0);

        $colony = $this->colony();
        $run = $this->activeRun();

        $this->assertSame(30, $run->current_tick);
        $this->assertSame(-15, DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 12)->value('amount'));
    }

    // ── near-deadline ─────────────────────────────────────────────────────────

    public function test_near_deadline_scenario_sets_tick_near_limit_and_completes_one_objective(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'near-deadline'])
            ->assertExitCode(0);

        $run = $this->activeRun();
        $this->assertSame((int) config('game.run.tick_limit', 100) - 5, $run->current_tick);

        $objectives = $run->objectives()->orderBy('id')->get();
        $this->assertSame(3, $objectives->count());
        $this->assertNotNull($objectives->first()->completed_at);
        $this->assertGreaterThan(0, $objectives->get(1)->current_value);
        $this->assertNull($objectives->get(1)->completed_at);

        // 4th advisor (trader/Konsul) hired after Cantina built.
        $this->assertSame(4, DB::table('advisors')->where('colony_id', $run->colony_id)->count());

        // Harvester relocated off its Phase-1 tile by exploreTilesAndMoveHarvester().
        $harvesterTile = DB::table('colony_buildings')
            ->where('colony_id', $run->colony_id)->where('building_id', 27)->first(['tile_x', 'tile_y']);
        $this->assertNotSame([1, 0], [$harvesterTile->tile_x, $harvesterTile->tile_y]);
    }

    // ── objectives-done ───────────────────────────────────────────────────────

    public function test_objectives_done_scenario_completes_all_objectives(): void
    {
        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'objectives-done'])
            ->assertExitCode(0);

        $run = $this->activeRun();
        $this->assertSame(60, $run->current_tick);

        $objectives = $run->objectives()->get();
        $this->assertSame(3, $objectives->count());
        foreach ($objectives as $objective) {
            $this->assertSame(40, $objective->completed_at);
            $this->assertSame($objective->target_value, $objective->current_value);
        }

        // engineer + scientist upgraded to Senior (rank 2) for the "Expertenstab" objective.
        $seniorCount = DB::table('advisors')
            ->where('colony_id', $run->colony_id)
            ->whereIn('personell_id', [(int) config('advisors.engineer.id'), (int) config('advisors.scientist.id')])
            ->where('rank', 2)
            ->count();
        $this->assertSame(2, $seniorCount);

        // 5 advisors total (engineer, scientist, pilot, trader, strategist).
        $this->assertSame(5, DB::table('advisors')->where('colony_id', $run->colony_id)->count());
    }

    // ── wipe behavior ─────────────────────────────────────────────────────────

    public function test_reset_wipes_previous_colony_state_before_reapplying(): void
    {
        // Leave some stale state from the seed data's default colony.
        $oldColonyId = $this->colony()->id;

        $this->artisan('game:reset-player', ['user' => 'bart', '--yes' => true, '--scenario' => 'fresh'])
            ->assertExitCode(0);

        // Exactly one active run and one colony remain for this user.
        $this->assertSame(1, Run::where('user_id', self::USER_ID)->where('status', 'active')->count());
        $this->assertSame(1, DB::table('glx_colonies')->where('user_id', self::USER_ID)->count());
    }
}
