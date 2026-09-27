<?php

namespace Tests\Feature;

/**
 * A45 Nexus checkpoints (config('game.run.nexus_checkpoints')) and the countdown.
 *
 * Checkpoints test progress, not completion, as a rising ladder in Phase-2 Sols:
 *   Sol 30 — one objective >= 50 %
 *   Sol 50 — one objective >= 75 % and a second >= 50 %
 *   Sol 65 — one objective completed and a second >= 75 % (miss → sanction)
 * Streak objectives count with their best streak so far.
 *
 * The countdown fires at tick_limit − countdown_sols_before_limit, independent
 * of the Phase-2 Sol.
 */

use App\Models\Advisor;
use App\Models\Run;
use App\Models\RunObjective;
use App\Services\RunProgressService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunNexusCheckpointTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    private RunProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(RunProgressService::class);
        Advisor::where('colony_id', self::COLONY_ID)->delete();
    }

    // ── Config ────────────────────────────────────────────────────────────────

    public function test_checkpoint_ladder_is_configured(): void
    {
        $checkpoints = config('game.run.nexus_checkpoints');

        $this->assertSame([50], $checkpoints[30]['requirements']);
        $this->assertSame([75, 50], $checkpoints[50]['requirements']);
        $this->assertSame([100, 75], $checkpoints[65]['requirements']);
        $this->assertSame('run.nexus_sanction_sol65', $checkpoints[65]['event']);
        $this->assertSame(20, config('game.run.countdown_sols_before_limit'));
    }

    public function test_dead_nexus_milestones_block_is_removed(): void
    {
        $this->assertNull(config('game.run.nexus_milestones'));
    }

    // ── Phase-2 Sol 30: one objective >= 50 % ────────────────────────────────

    public function test_sol30_passes_with_one_objective_at_exactly_50_percent(): void
    {
        $run = $this->phase2Run(30);
        $this->objective($run, 5, 10);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_warning_sol30');
    }

    public function test_sol30_warns_just_below_50_percent(): void
    {
        $run = $this->phase2Run(30);
        $this->objective($run, 49, 100);
        $this->objective($run, 49, 100);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_warning_sol30');
    }

    public function test_sol30_counts_the_best_streak_not_the_current_one(): void
    {
        $run = $this->phase2Run(30);
        // Streak broke one Sol before the checkpoint, but the record is 5 of 10.
        $this->objective($run, 0, 10, best: 5, taskKey: 'task_credit_reserve');

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_warning_sol30');
    }

    // ── Phase-2 Sol 50: one >= 75 % and a second >= 50 % ────────────────────

    public function test_sol50_passes_at_exactly_75_and_50_percent(): void
    {
        $run = $this->phase2Run(50);
        $this->objective($run, 3, 4);
        $this->objective($run, 5, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_warning_sol50');
    }

    public function test_sol50_warns_when_the_leader_is_just_below_75_percent(): void
    {
        $run = $this->phase2Run(50);
        $this->objective($run, 74, 100);
        $this->objective($run, 74, 100);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_warning_sol50');
    }

    public function test_sol50_warns_when_the_second_objective_is_just_below_50_percent(): void
    {
        $run = $this->phase2Run(50);
        $this->objective($run, 10, 10, completed: true);
        $this->objective($run, 49, 100);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_warning_sol50');
    }

    // ── Phase-2 Sol 65: one completed and a second >= 75 % ──────────────────

    public function test_sol65_passes_with_one_completed_and_a_second_at_75_percent(): void
    {
        $run = $this->phase2Run(65);
        $this->objective($run, 10, 10, completed: true);
        $this->objective($run, 3, 4);
        $advisor = $this->advisor();

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_sanction_sol65');
        $this->assertNull($advisor->refresh()->unavailable_until_tick);
    }

    public function test_sol65_sanctions_when_the_second_objective_is_just_below_75_percent(): void
    {
        $run = $this->phase2Run(65);
        $this->objective($run, 10, 10, completed: true);
        $this->objective($run, 74, 100);
        $advisor = $this->advisor();

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_sanction_sol65');
        $this->assertSame(66, $advisor->refresh()->unavailable_until_tick, 'sanction locks one advisor for one Sol');
    }

    public function test_sol65_sanctions_when_no_objective_is_completed(): void
    {
        $run = $this->phase2Run(65);
        $this->objective($run, 99, 100);
        $this->objective($run, 99, 100);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_sanction_sol65');
    }

    // ── Timing / config wiring ───────────────────────────────────────────────

    public function test_checkpoints_are_only_evaluated_on_their_exact_phase2_sol(): void
    {
        $run = $this->phase2Run(51);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_warning_sol30');
        $this->assertEventMissing('run.nexus_warning_sol50');
    }

    public function test_checkpoints_use_phase2_sol_not_total_sol(): void
    {
        // Total Sol 30, but only Phase-2 Sol 10 — no checkpoint yet.
        $run = $this->phase2Run(10, phase2Start: 20);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_warning_sol30');
    }

    public function test_checkpoint_schedule_and_thresholds_come_from_config(): void
    {
        config(['game.run.nexus_checkpoints' => [
            12 => ['requirements' => [20], 'event' => 'run.nexus_warning_sol30'],
        ]]);
        $run = $this->phase2Run(12);
        $this->objective($run, 1, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_warning_sol30');
    }

    // ── Countdown ────────────────────────────────────────────────────────────

    public function test_countdown_fires_at_tick_limit_minus_20_regardless_of_phase2_sol(): void
    {
        // Phase 1 ended at Sol 20: total Sol 80 is only Phase-2 Sol 60.
        $run = $this->phase2Run(60, phase2Start: 20, tickLimit: 100);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_countdown_sol80');
    }

    public function test_countdown_does_not_fire_one_sol_early(): void
    {
        $run = $this->phase2Run(59, phase2Start: 20, tickLimit: 100);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventMissing('run.nexus_countdown_sol80');
    }

    public function test_countdown_lead_comes_from_config(): void
    {
        config(['game.run.countdown_sols_before_limit' => 30]);
        $run = $this->phase2Run(50, phase2Start: 20, tickLimit: 100);
        $this->objective($run, 0, 10);

        $this->service->checkNexusInterventions($run);

        $this->assertEventFired('run.nexus_countdown_sol80');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function phase2Run(int $phase2Sol, int $phase2Start = 0, int $tickLimit = 100): Run
    {
        return Run::create([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'status' => 'active',
            'phase' => 2,
            'phase2_start_tick' => $phase2Start,
            'current_tick' => $phase2Start + $phase2Sol,
            'started_at' => now()->subHour(),
            'settings' => ['tick_limit' => $tickLimit],
        ]);
    }

    private function objective(
        Run $run,
        int $current,
        int $target,
        bool $completed = false,
        int $best = 0,
        string $taskKey = 'task_research_lead'
    ): RunObjective {
        return RunObjective::create([
            'run_id' => $run->id,
            'task_key' => $taskKey,
            'target_value' => $target,
            'current_value' => $current,
            'streak_value' => $current,
            'best_streak_value' => $best,
            'completed_at' => $completed ? 10 : null,
        ]);
    }

    private function advisor(): Advisor
    {
        return Advisor::create([
            'user_id' => self::USER_ID,
            'personell_id' => 35,
            'colony_id' => self::COLONY_ID,
            'rank' => 1,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);
    }

    private function assertEventFired(string $event): void
    {
        $this->assertTrue(
            DB::table('colony_log')->where('user', self::USER_ID)->where('event', $event)->exists(),
            "{$event} should have fired"
        );
    }

    private function assertEventMissing(string $event): void
    {
        $this->assertFalse(
            DB::table('colony_log')->where('user', self::USER_ID)->where('event', $event)->exists(),
            "{$event} should not have fired"
        );
    }
}
