<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Events\RunEnded;
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
 *  - Detect fail states (trust collapse, nexus debt, time limit).
 *  - Check Nexus intervention checkpoints during Phase 2.
 *  - End a run (win or loss) and calculate the final score.
 */
class RunProgressService
{
    // ── Task metadata ────────────────────────────────────────────────────────

    private const TASK_CATEGORIES = [
        'economy' => ['task_credit_reserve', 'task_trade_volume'],
        'research' => ['task_research_lead', 'task_engineering_output'],
        'exploration' => ['task_expedition_coverage'],
        'diplomacy' => ['task_colony_prosperity'],
        'survival' => ['task_self_sufficiency'],
        'personal' => ['task_senior_advisors'],
    ];

    private const TASK_TARGETS = [
        'task_senior_advisors' => 1,
        'task_credit_reserve' => 10,
        'task_colony_prosperity' => 10,
        'task_research_lead' => 3,
        'task_self_sufficiency' => 15,
        // Max reachable is 1 (CC ring-0, always colony zone + pre-explored) + Σ
        // config('game.colony_zone_expansion') over all 5 CC levels (15) = 16.
        // Was 19 — mathematically unwinnable regardless of play skill (found
        // empirically 2026-08-14, PlaytestBot stalled at 13/19 across all seeds).
        'task_expedition_coverage' => 16,
        'task_engineering_output' => 200,
        'task_trade_volume' => 5,
    ];

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
        $ccId = BuildingId::CommandCenter->value;

        // Condition 1 — CC level >= 3
        $ccReady = DB::table('colony_buildings')
            ->where('colony_id', $run->colony_id)
            ->where('building_id', $ccId)
            ->where('level', '>=', 3)
            ->exists();

        if (! $ccReady) {
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
     * Draws 3 objectives, persists the phase change, records phase2_start_tick
     * and fires an INNN event.
     */
    public function transitionToPhase2(Run $run): void
    {
        DB::transaction(function () use ($run): void {
            $run->phase = 2;
            $run->phase2_start_tick = $run->current_tick;
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
     * Combo-blacklist: no more than 1 economy task in a single draw set.
     * Economy tasks: task_credit_reserve, task_trade_volume.
     * If the full shuffled pool yields < 3 valid tasks, fill up with non-economy tasks.
     *
     * The draw order is derived from run.rng_seed, so two runs with the same seed get
     * the same objectives — a prerequisite for comparing balance changes across runs.
     *
     * Ordering by a per-key hash rather than shuffle() is deliberate:
     *  - Collection::shuffle() has taken no seed argument since Laravel 10 — passing one
     *    is silently ignored and the draw stays random.
     *  - A global mt_srand() would seed the draw, but also perturb every later roll in
     *    the same request; mission loot rolls off rng_seed + mission_id (ADR 0003) and
     *    must stay independent.
     * seededOrderHash() is a pure function, so it touches no global RNG state at all.
     */
    public function drawObjectives(Run $run): void
    {
        $pool = config('game.run.task_pool', array_keys(self::TASK_TARGETS));
        $economy = self::TASK_CATEGORIES['economy'];

        $seed = (int) ($run->rng_seed ?? random_int(1, PHP_INT_MAX));

        $shuffled = collect($pool)
            ->sortBy(fn (string $taskKey): int => $this->seededOrderHash($seed, $taskKey))
            ->values();

        $selected = [];
        $economyCount = 0;

        foreach ($shuffled as $taskKey) {
            if (count($selected) >= 3) {
                break;
            }

            $isEconomy = in_array($taskKey, $economy, true);

            if ($isEconomy && $economyCount >= 1) {
                // Combo-blacklist: skip second economy task
                continue;
            }

            $selected[] = $taskKey;

            if ($isEconomy) {
                $economyCount++;
            }
        }

        // Fallback: if still < 3 tasks, fill with non-economy tasks not yet selected
        if (count($selected) < 3) {
            foreach ($shuffled as $taskKey) {
                if (count($selected) >= 3) {
                    break;
                }
                if (! in_array($taskKey, $selected, true) && ! in_array($taskKey, $economy, true)) {
                    $selected[] = $taskKey;
                }
            }
        }

        $rows = [];
        foreach ($selected as $taskKey) {
            $rows[] = [
                'run_id' => $run->id,
                'task_key' => $taskKey,
                'target_value' => self::TASK_TARGETS[$taskKey] ?? 1,
                'current_value' => 0,
                'streak_value' => 0,
                'completed_at' => null,
            ];
        }

        DB::table('run_objectives')->insert($rows);
    }

    // ── Objective progress update ────────────────────────────────────────────

    /**
     * Evaluate and persist progress for every open objective of the given run.
     *
     * Called once per tick, after resource generation and trust recalculation.
     */
    public function updateObjectiveProgress(Run $run): void
    {
        $objectives = $run->objectives()->whereNull('completed_at')->get();

        foreach ($objectives as $objective) {
            match ($objective->task_key) {
                'task_senior_advisors' => $this->updateSeniorAdvisors($objective, $run),
                'task_credit_reserve' => $this->updateCreditReserve($objective, $run),
                'task_colony_prosperity' => $this->updateColonyProsperity($objective, $run),
                'task_research_lead' => $this->updateResearchLead($objective, $run),
                'task_self_sufficiency' => $this->updateSelfSufficiency($objective, $run),
                'task_expedition_coverage' => $this->updateExpeditionCoverage($objective, $run),
                'task_engineering_output' => $this->updateEngineeringOutput($objective, $run),
                'task_trade_volume' => $this->updateTradeVolume($objective, $run),
                default => null,
            };
        }
    }

    private function updateSeniorAdvisors(RunObjective $objective, Run $run): void
    {
        $totalAdvisors = Advisor::where('colony_id', $run->colony_id)->count();
        $seniorAdvisors = Advisor::where('colony_id', $run->colony_id)
            ->where('rank', '>=', 2)
            ->count();

        $fulfilled = $totalAdvisors >= config('game.advisor.max_slots') && $seniorAdvisors >= 2;

        $objective->current_value = $fulfilled ? 1 : 0;

        if ($fulfilled && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    private function updateCreditReserve(RunObjective $objective, Run $run): void
    {
        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $run->user_id)
            ->value('credits') ?? 0);

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

    private function updateColonyProsperity(RunObjective $objective, Run $run): void
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

    private function updateResearchLead(RunObjective $objective, Run $run): void
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

    /**
     * Streak task: all three conditions must hold simultaneously each sol.
     * Regolith (colony_resources, resource_id=3) > 50, Organika (resource_id=5) > 50,
     * Supply (user_resources.supply) > 0. Any single failure resets the streak to 0.
     */
    private function updateSelfSufficiency(RunObjective $objective, Run $run): void
    {
        $regolith = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 3)
            ->value('amount') ?? 0);

        $organics = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 5)
            ->value('amount') ?? 0);

        $supply = (int) (DB::table('user_resources')
            ->where('user_id', $run->user_id)
            ->value('supply') ?? 0);

        $allMet = $regolith > 50 && $organics > 50 && $supply > 0;

        if ($allMet) {
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

    /**
     * Counter task: number of explored colony-zone tiles >= target_value (19).
     */
    private function updateExpeditionCoverage(RunObjective $objective, Run $run): void
    {
        $count = (int) DB::table('colony_tiles')
            ->where('colony_id', $run->colony_id)
            ->where('is_explored', 1)
            ->where('is_colony_zone', 1)
            ->count();

        $objective->current_value = $count;

        if ($count >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    /**
     * Counter task: sum of status_points across all colony buildings >= 200.
     */
    private function updateEngineeringOutput(RunObjective $objective, Run $run): void
    {
        $total = (int) DB::table('colony_buildings')
            ->where('colony_id', $run->colony_id)
            ->sum('status_points');

        $objective->current_value = $total;

        if ($total >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    /**
     * Counter task: number of sold merchant items purchased during this run session >= 5.
     *
     * Join path: merchant_items.visit_id → merchant_visits.id → merchant_visits.colony_id = run.colony_id.
     * Time filter: merchant_visits.created_at >= run.started_at.
     */
    private function updateTradeVolume(RunObjective $objective, Run $run): void
    {
        $count = (int) DB::table('merchant_items')
            ->join('merchant_visits', 'merchant_items.visit_id', '=', 'merchant_visits.id')
            ->where('merchant_visits.colony_id', $run->colony_id)
            ->where('merchant_items.sold', 1)
            ->where('merchant_visits.created_at', '>=', $run->started_at)
            ->count();

        $objective->current_value = $count;

        if ($count >= $objective->target_value && $objective->completed_at === null) {
            $objective->completed_at = $run->current_tick;
        }

        $objective->save();
    }

    // ── Nexus interventions ──────────────────────────────────────────────────

    /**
     * Check Phase-2 Nexus intervention checkpoints and fire INNN events or penalties.
     *
     * Called once per tick, only when run is in Phase 2.
     * Each checkpoint fires at most once per run (guarded by colony_log lookup).
     *
     * Checkpoints by Phase-2 sol:
     *  Sol 30  — < 1 task at > 50% progress → warning event
     *  Sol 50  — 0 tasks completed           → warning event
     *  Sol 55  — nexus_debt over threshold   → endRun failed (nexus_debt)
     *  Sol 65  — 0 tasks completed           → sanction event + 1 random advisor locked 1 sol
     *  Sol 80  — countdown warning           → event only when tick >= tick_limit - 20
     */
    public function checkNexusInterventions(Run $run): void
    {
        $sol = $run->getPhase2Sol();

        if ($sol >= 30) {
            $this->maybeFireSol30Warning($run, $sol);
        }

        if ($sol >= 50) {
            $this->maybeFireSol50Warning($run);
        }

        if ($sol >= 55) {
            if (($run->nexus_debt ?? 0) > $this->nexusDebtFailThreshold()) {
                $this->endRun($run, 'failed', 'nexus_debt');

                return;
            }
        }

        if ($sol >= 65) {
            $this->maybeFireSol65Sanction($run);
        }

        if ($sol >= 80) {
            $this->maybeFireSol80Countdown($run);
        }
    }

    private function maybeFireSol30Warning(Run $run, int $sol): void
    {
        $eventKey = 'run.nexus_warning_sol30';

        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        // Only fire once, at the exact sol-30 boundary
        if ($sol !== 30) {
            return;
        }

        $objectives = $run->objectives()->get();
        $aboveHalf = $objectives->filter(fn ($o) => $o->progressPct() > 50)->count();

        if ($aboveHalf < 1) {
            $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
                'run_id' => $run->id,
                'colony_id' => $run->colony_id,
            ]);
        }
    }

    private function maybeFireSol50Warning(Run $run): void
    {
        $eventKey = 'run.nexus_warning_sol50';

        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        $completed = $run->objectives()->whereNotNull('completed_at')->count();

        if ($completed === 0) {
            $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
                'run_id' => $run->id,
                'colony_id' => $run->colony_id,
            ]);
        }
    }

    private function maybeFireSol65Sanction(Run $run): void
    {
        $eventKey = 'run.nexus_sanction_sol65';

        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        $completed = $run->objectives()->whereNotNull('completed_at')->count();

        if ($completed > 0) {
            return;
        }

        // Fire sanction event
        $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
            'run_id' => $run->id,
            'colony_id' => $run->colony_id,
        ]);

        // Apply penalty: pick a random active advisor and lock them for 1 sol
        $advisor = Advisor::where('colony_id', $run->colony_id)
            ->where(function ($q) use ($run): void {
                $q->whereNull('unavailable_until_tick')
                    ->orWhere('unavailable_until_tick', '<', $run->current_tick);
            })
            ->inRandomOrder()
            ->first();

        if ($advisor !== null) {
            $advisor->unavailable_until_tick = $run->current_tick + 1;
            $advisor->save();
        }
    }

    private function maybeFireSol80Countdown(Run $run): void
    {
        $eventKey = 'run.nexus_countdown_sol80';

        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        if ($run->current_tick < $run->getTickLimit() - 20) {
            return;
        }

        $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
            'run_id' => $run->id,
            'colony_id' => $run->colony_id,
        ]);
    }

    // ── Phase-1 deadline warning ──────────────────────────────────────────────

    /**
     * Escalating Nexus warning if Phase 1 is still not complete by
     * config('game.run.phase1_warning_sol') — heads-up before the hard
     * config('game.run.phase1_deadline_sol') fail state in checkFailStates().
     *
     * Called once per tick, only while the run is in Phase 1 (see GameTick.php).
     * Fires at most once per run (guarded by colony_log lookup, same pattern
     * as maybeFireSol30Warning() etc.).
     */
    public function checkPhase1DeadlineWarnings(Run $run): void
    {
        if ($run->phase !== 1) {
            return;
        }

        $warningSol = (int) config('game.run.phase1_warning_sol', 22);
        if ($run->current_tick < $warningSol) {
            return;
        }

        $eventKey = 'run.nexus_phase1_warning';
        if ($this->eventAlreadyFired($run, $eventKey)) {
            return;
        }

        $this->createEvent($run->user_id, $run->current_tick, $eventKey, 'run', [
            'run_id' => $run->id,
            'colony_id' => $run->colony_id,
        ]);
    }

    /**
     * Return true if an colony_log row with this event key already exists
     * for this user, created at or after the run's start time.
     */
    private function eventAlreadyFired(Run $run, string $eventKey): bool
    {
        return DB::table('colony_log')
            ->where('user', $run->user_id)
            ->where('event', $eventKey)
            ->where('created_at', '>=', $run->started_at)
            ->exists();
    }

    // ── Fail state checks ────────────────────────────────────────────────────

    /**
     * Check whether the run has entered a fail state this tick.
     *
     * Returns the fail reason key (for endRun()) or null if the run continues.
     *
     * Fail states checked:
     *  trust_collapse   — trust value < trust_fail_threshold (instant fail).
     *  nexus_debt       — nexus_debt > nexus_debt_fail_threshold (checked here as secondary path).
     *  phase1_deadline  — still in Phase 1 at current_tick >= phase1_deadline_sol (instant fail).
     *  time_limit       — current_tick >= tick_limit.
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

        if (($run->nexus_debt ?? 0) > $this->nexusDebtFailThreshold()) {
            return 'nexus_debt';
        }

        if ($run->phase === 1 && $run->current_tick >= (int) config('game.run.phase1_deadline_sol', 30)) {
            return 'phase1_deadline';
        }

        if ($run->current_tick >= $run->getTickLimit()) {
            return 'time_limit';
        }

        return null;
    }

    /**
     * Deterministic sort key for one task in a seeded draw. Pure — no global RNG state.
     *
     * Hashes seed and key *together* rather than combining them arithmetically. The LCG
     * used by GameTick::seededRoll() is wrong for this job: `(seed + crc32(key)) * a + c`
     * shifts every task's hash by the same `seed * a`, leaving their relative order — and
     * therefore the draw — identical for every seed.
     */
    private function seededOrderHash(int $seed, string $taskKey): int
    {
        return crc32($seed.'|'.$taskKey);
    }

    /**
     * Nexus debt ceiling — exceeding it fails the run.
     *
     * Read by both fail paths: checkFailStates() (every tick) and the Phase-2
     * sol-55 checkpoint in checkNexusInterventions().
     */
    private function nexusDebtFailThreshold(): int
    {
        return (int) config('game.run.nexus_debt_fail_threshold', 12000);
    }

    // ── Run end ──────────────────────────────────────────────────────────────

    /**
     * Finalise the run with the given status and optional fail reason.
     *
     * Persists status, fail_reason, ended_at and the final score atomically, then fires
     * an INNN event.
     *
     * Order matters: calculateScore() only returns 0 once $run->status is already
     * 'failed'. Scoring before the status assignment would give failed runs a positive
     * score, so the status is set first and the score derived from it.
     *
     * @param  string  $status  'completed' or 'failed'
     * @param  string|null  $failReason  e.g. 'trust_collapse', 'time_limit', 'nexus_debt'
     */
    public function endRun(Run $run, string $status, ?string $failReason = null): void
    {
        DB::transaction(function () use ($run, $status, $failReason): void {
            $run->status = $status;
            $run->fail_reason = $failReason;
            $run->ended_at = now();
            $run->score = $this->calculateScore($run);
            $run->save();

            $eventKey = match (true) {
                $status === 'completed' => 'run.run_completed',
                $failReason === 'trust_collapse' => 'run.run_failed_trust',
                $failReason === 'nexus_debt' => 'run.run_failed_nexus_debt',
                $failReason === 'phase1_deadline' => 'run.run_failed_phase1_deadline',
                default => 'run.run_failed_time',
            };

            $this->createEvent(
                $run->user_id,
                $run->current_tick,
                $eventKey,
                'run',
                ['run_id' => $run->id, 'colony_id' => $run->colony_id, 'fail_reason' => $failReason]
            );

            event(new RunEnded($run, $status, $failReason));
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

        $completed = $run->objectives()->whereNotNull('completed_at')->count();
        $tickLimit = $run->getTickLimit();
        $completedTick = $run->current_tick;

        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $run->user_id)
            ->value('credits') ?? 0);

        $trust = (int) (DB::table('colony_resources')
            ->where('colony_id', $run->colony_id)
            ->where('resource_id', 12)
            ->value('amount') ?? 0);

        return max(0, ($completed * 1000)
            + (($tickLimit - $completedTick) * 10)
            + (int) ($credits / 10)
            + ($trust * 5));
    }

    // ── Internal helpers ─────────────────────────────────────────────────────

    private function createEvent(
        int $userId,
        int $tick,
        string $event,
        string $area,
        array $parameters = []
    ): void {
        $isNexus = $area === 'nexus' || in_array($event, [
            'run.nexus_warning_sol30', 'run.nexus_warning_sol50',
            'run.nexus_sanction_sol65', 'run.nexus_countdown_sol80', 'run.nexus_phase1_warning',
            'run.run_completed', 'run.run_failed_trust',
            'run.run_failed_nexus_debt', 'run.run_failed_time', 'run.run_failed_phase1_deadline',
            'run.phase1_complete',
        ], true);

        DB::table('colony_log')->insert([
            'user' => $userId,
            'tick' => $tick,
            'event' => $event,
            'area' => $area,
            'parameters' => json_encode($parameters),
            'created_at' => now(),
            'is_read' => $isNexus ? 0 : 1,
        ]);
    }
}
