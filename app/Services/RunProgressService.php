<?php

namespace App\Services;

use App\Models\Advisor;
use App\Models\Run;
use App\Models\RunObjective;
use Illuminate\Support\Facades\DB;

/**
 * RunProgressService — Phase transitions, objective tracking and run-end logic.
 *
 * Responsibilities:
 *  - Detect Phase-1 completion and promote the run to Phase 2.
 *  - Draw 3 random objectives from the task pool at Phase-2 start.
 *  - Update objective progress each tick (streak, counters, completion).
 *  - Detect fail states (trust collapse, time limit).
 *  - End a run (win or loss) and calculate the final score.
 */
class RunProgressService
{
    // ── Phase-1 completion check ─────────────────────────────────────────────

    /**
     * Check whether all Phase-1 completion conditions are met.
     *
     * Conditions (GDD §15):
     *  1. Command Center (building_id = 25) at level >= 3.
     *  2. At least 2 production buildings (building_id != 25) at level >= 2.
     *  3. At least 3 active advisors (colony assigned, not on cooldown).
     */
    public function checkPhase1Completion(Run $run): bool
    {
        $ccId = config('buildings.commandCenter.id', 25);

        // Condition 1 — CC level >= 3
        $ccReady = DB::table('colony_buildings')
            ->where('colony_id', $run->colony_id)
            ->where('building_id', $ccId)
            ->where('level', '>=', 3)
            ->exists();

        if (!$ccReady) {
            return false;
        }

        // Condition 2 — at least 2 non-CC production buildings at level >= 2
        $productionCount = DB::table('colony_buildings')
            ->where('colony_id', $run->colony_id)
            ->where('building_id', '!=', $ccId)
            ->where('level', '>=', 2)
            ->count();

        if ($productionCount < 2) {
            return false;
        }

        // Condition 3 — at least 3 active advisors on this colony
        $activeAdvisors = Advisor::where('colony_id', $run->colony_id)
            ->where(function ($q) use ($run): void {
                $q->whereNull('unavailable_until_tick')
                  ->orWhere('unavailable_until_tick', '<', $run->current_tick);
            })
            ->count();

        return $activeAdvisors >= 3;
    }

    // ── Phase transition ─────────────────────────────────────────────────────

    /**
     * Promote a run from Phase 1 to Phase 2.
     *
     * Draws 3 objectives, persists the phase change and fires an INNN event.
     */
    public function transitionToPhase2(Run $run): void
    {
        DB::transaction(function () use ($run): void {
            $run->phase = 2;
            $run->save();

            $this->drawObjectives($run);

            $this->createEvent(
                $run->user_id,
                $run->current_tick,
                'run.phase1_complete',
                'run',
                ['run_id' => $run->id, 'colony_id' => $run->colony_id]
            );
        });
    }

    // ── Objective drawing ────────────────────────────────────────────────────

    /**
     * Draw 3 tasks from the configured task pool and insert RunObjective records.
     *
     * Target values per task key:
     *   task_expertenstab       → 1  (binary: all 5 slots filled + 2 senior)
     *   task_kreditimperium     → 10 (10 consecutive ticks with credits >= 5000)
     *   task_koloniebluete      → 10 (10 consecutive ticks with trust > 70)
     *   task_forschungsvorsprung → 3 (3 researches at level >= 5)
     */
    public function drawObjectives(Run $run): void
    {
        $targetValues = [
            'task_expertenstab'        => 1,
            'task_kreditimperium'      => 10,
            'task_koloniebluete'       => 10,
            'task_forschungsvorsprung' => 3,
        ];

        $pool     = config('game.run.task_pool', array_keys($targetValues));
        $shuffled = collect($pool)->shuffle()->take(3);

        $rows = [];
        foreach ($shuffled as $taskKey) {
            $rows[] = [
                'run_id'        => $run->id,
                'task_key'      => $taskKey,
                'target_value'  => $targetValues[$taskKey] ?? 1,
                'current_value' => 0,
                'streak_value'  => 0,
                'completed_at'  => null,
            ];
        }

        DB::table('run_objectives')->insert($rows);
    }

    // ── Objective progress update ────────────────────────────────────────────

    /**
     * Evaluate and persist progress for every open objective of the given run.
     *
     * Called once per tick, after resource generation and moral recalculation.
     */
    public function updateObjectiveProgress(Run $run): void
    {
        $objectives = $run->objectives()->whereNull('completed_at')->get();

        foreach ($objectives as $objective) {
            match ($objective->task_key) {
                'task_expertenstab'        => $this->updateExpertenstab($objective, $run),
                'task_kreditimperium'      => $this->updateKreditimperium($objective, $run),
                'task_koloniebluete'       => $this->updateKoloniebluete($objective, $run),
                'task_forschungsvorsprung' => $this->updateForschungsvorsprung($objective, $run),
                default                    => null,
            };
        }
    }

    private function updateExpertenstab(RunObjective $objective, Run $run): void
    {
        $totalAdvisors = Advisor::where('colony_id', $run->colony_id)->count();
        $seniorAdvisors = Advisor::where('colony_id', $run->colony_id)
            ->where('rank', '>=', 2)
            ->count();

        $fulfilled = $totalAdvisors >= 5 && $seniorAdvisors >= 2;

        $objective->current_value = $fulfilled ? 1 : 0;

        if ($fulfilled && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    private function updateKreditimperium(RunObjective $objective, Run $run): void
    {
        $credits = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 1)
            ->value('amount') ?? 0);

        if ($credits >= 5000) {
            $objective->streak_value++;
        } else {
            $objective->streak_value = 0;
        }

        $objective->current_value = $objective->streak_value;

        if ($objective->current_value >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    private function updateKoloniebluete(RunObjective $objective, Run $run): void
    {
        $trust = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 12)
            ->value('amount') ?? 0);

        if ($trust > 70) {
            $objective->streak_value++;
        } else {
            $objective->streak_value = 0;
        }

        $objective->current_value = $objective->streak_value;

        if ($objective->current_value >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    private function updateForschungsvorsprung(RunObjective $objective, Run $run): void
    {
        $count = (int) DB::table('colony_researches')
            ->where('colony_id', $run->colony_id)
            ->where('level', '>=', 5)
            ->count();

        $objective->current_value = $count;

        if ($count >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    // ── Fail state checks ────────────────────────────────────────────────────

    /**
     * Check whether the run has entered a fail state this tick.
     *
     * Returns the fail reason key (for endRun()) or null if the run continues.
     *
     * Fail states checked:
     *  trust_collapse — trust value < trust_fail_threshold (instant fail).
     *  time_limit     — current_tick >= tick_limit.
     */
    public function checkFailStates(Run $run): ?string
    {
        $trustThreshold = (int) config('game.run.trust_fail_threshold', -20);

        $trust = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 12)
            ->value('amount') ?? 0);

        if ($trust < $trustThreshold) {
            return 'trust_collapse';
        }

        if ($run->current_tick >= $run->getTickLimit()) {
            return 'time_limit';
        }

        return null;
    }

    // ── Run end ──────────────────────────────────────────────────────────────

    /**
     * Finalise the run with the given status and optional fail reason.
     *
     * Persists status, fail_reason and ended_at atomically, then fires an INNN event.
     *
     * @param string      $status     'completed' or 'failed'
     * @param string|null $failReason e.g. 'trust_collapse', 'time_limit'
     */
    public function endRun(Run $run, string $status, ?string $failReason = null): void
    {
        DB::transaction(function () use ($run, $status, $failReason): void {
            $run->status     = $status;
            $run->fail_reason = $failReason;
            $run->ended_at   = now();
            $run->save();

            $eventKey = match (true) {
                $status === 'completed'               => 'run.run_completed',
                $failReason === 'trust_collapse'      => 'run.run_failed_trust',
                default                               => 'run.run_failed_time',
            };

            $this->createEvent(
                $run->user_id,
                $run->current_tick,
                $eventKey,
                'run',
                ['run_id' => $run->id, 'colony_id' => $run->colony_id, 'fail_reason' => $failReason]
            );
        });
    }

    // ── Score calculation ────────────────────────────────────────────────────

    /**
     * Calculate the final score for a run.
     *
     * Formula (GDD §15):
     *   score = (completed × 1000) + ((tick_limit − done_tick) × 10) + (credits / 10) + (trust × 5)
     *
     * Returns 0 for failed runs.
     */
    public function calculateScore(Run $run): int
    {
        if ($run->status === 'failed') {
            return 0;
        }

        $completed    = $run->objectives()->whereNotNull('completed_at')->count();
        $tickLimit    = $run->getTickLimit();
        $completedTick = $run->current_tick;

        $credits = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 1)
            ->value('amount') ?? 0);

        $trust = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 12)
            ->value('amount') ?? 0);

        return ($completed * 1000)
            + (($tickLimit - $completedTick) * 10)
            + (int) ($credits / 10)
            + ($trust * 5);
    }

    // ── Internal helpers ─────────────────────────────────────────────────────

    private function createEvent(
        int    $userId,
        int    $tick,
        string $event,
        string $area,
        array  $parameters = []
    ): void {
        DB::table('innn_events')->insert([
            'user'       => $userId,
            'tick'       => $tick,
            'event'      => $event,
            'area'       => $area,
            'parameters' => serialize($parameters),
        ]);
    }
}
