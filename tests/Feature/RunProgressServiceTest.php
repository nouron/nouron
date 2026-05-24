<?php

namespace Tests\Feature;

/**
 * Feature tests for RunProgressService.
 *
 * Covered scenarios:
 *
 * PHASE-1 COMPLETION
 *   - checkPhase1Completion returns false when CC below level 3
 *   - checkPhase1Completion returns false when fewer than 2 production buildings at level 2
 *   - checkPhase1Completion returns false when fewer than 3 advisors
 *   - checkPhase1Completion returns true when all conditions met
 *
 * DRAW OBJECTIVES
 *   - drawObjectives creates 3 objectives for run
 *   - drawObjectives sets correct target values
 *
 * UPDATE OBJECTIVE PROGRESS
 *   - task_expertenstab completes when 5 advisors with 2 senior
 *   - task_forschungsvorsprung completes when 3 researches at level 5
 *   - task_kreditimperium increments streak when credits above threshold
 *   - task_kreditimperium resets streak when credits below threshold
 *
 * FAIL STATES
 *   - returns trust_collapse when trust below threshold
 *   - returns time_limit when tick equals tick_limit
 *   - returns null when no fail conditions
 *
 * END RUN
 *   - endRun sets status and ended_at
 *
 * SCORE
 *   - calculateScore returns 0 for failed run
 *   - calculateScore returns positive score for completed run with objectives
 *
 */

use App\Models\Advisor;
use App\Models\Run;
use App\Models\RunObjective;
use App\Services\RunProgressService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    // Fixtures: Bart (user_id=3) owns colony 1 (Springfield)
    protected int $userId   = 3;
    protected int $colonyId = 1;

    protected RunProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->service = $this->app->make(RunProgressService::class);

        // Remove all advisors on colony 1 so each test starts with a clean slate.
        Advisor::where('colony_id', $this->colonyId)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Personell IDs present in testdata — used as a round-robin pool so
     * the unique (colony_id, personell_id) constraint is never violated.
     * IDs: engineer=35, scientist=36, pilot=89, trader=92, strategist=93.
     */
    private array $personellPool = [35, 36, 89, 92, 93];
    private int   $personellCursor = 0;

    /**
     * Return the next available personell_id from the pool.
     */
    private function nextPersonellId(): int
    {
        return $this->personellPool[$this->personellCursor++ % count($this->personellPool)];
    }

    /**
     * Create a minimal active run for colony 1 / user 3.
     */
    private function makeRun(array $overrides = []): Run
    {
        return Run::create(array_merge([
            'user_id'      => $this->userId,
            'colony_id'    => $this->colonyId,
            'current_tick' => 5,
            'status'       => 'active',
            'phase'        => 1,
        ], $overrides));
    }

    private function setBuildingLevel(int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'building_id' => $buildingId],
            ['level' => $level, 'status_points' => 20]
        );
    }

    /**
     * Set the trust resource (resource_id = 12) for colony 1.
     */
    private function setTrust(int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['resource_id' => 12, 'colony_id' => $this->colonyId],
            ['amount' => $amount]
        );
    }

    /**
     * Set the credits resource (resource_id = 1) for colony 1.
     */
    private function setCredits(int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['resource_id' => 1, 'colony_id' => $this->colonyId],
            ['amount' => $amount]
        );
    }

    /**
     * Create a minimal advisor assigned to colony 1.
     *
     * Each call draws the next personell_id from the pool so the unique
     * (colony_id, personell_id) constraint is never violated.
     */
    private function insertAdvisor(array $overrides = []): Advisor
    {
        return Advisor::create(array_merge([
            'user_id'                => $this->userId,
            'personell_id'           => $this->nextPersonellId(),
            'colony_id'              => $this->colonyId,
            'rank'                   => 1,
            'active_ticks'           => 0,
            'unavailable_until_tick' => null,
            'fleet_id'               => null,
            'is_commander'           => 0,
        ], $overrides));
    }

    /**
     * Create an open RunObjective for the given run and task key.
     */
    private function makeObjective(Run $run, string $taskKey, int $targetValue, int $streakValue = 0): RunObjective
    {
        return RunObjective::create([
            'run_id'        => $run->id,
            'task_key'      => $taskKey,
            'target_value'  => $targetValue,
            'current_value' => 0,
            'streak_value'  => $streakValue,
            'completed_at'  => null,
        ]);
    }

    /**
     * Reset all colony_researches for colony 1 to level 0.
     *
     * Required for forschungsvorsprung tests because testdata already seeds
     * several researches at high levels (e.g. research_id=39 at level 19,
     * research_id=72 at level 17).
     */
    private function resetAllResearchLevels(): void
    {
        DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->update(['level' => 0]);
    }

    // ── Phase-1 completion ────────────────────────────────────────────────────

    public function test_checkPhase1Completion_returns_false_when_cc_below_level_3(): void
    {
        $run = $this->makeRun();

        // CC at level 1 — condition 1 must fail
        $this->setBuildingLevel(25, 1);

        $result = $this->service->checkPhase1Completion($run);

        $this->assertFalse($result, 'Phase-1 must not complete when CC is below level 3');
    }

    public function test_checkPhase1Completion_returns_false_when_fewer_than_2_production_buildings_at_level_2(): void
    {
        $run = $this->makeRun();

        // CC at level 3 — condition 1 passes
        $this->setBuildingLevel(25, 3);

        // Drop all non-CC buildings in colony 1 to level 1 so none qualify.
        // Testdata has buildings 25 (lv3), 28 (lv2), 30 (lv3), 31 (lv2) etc.
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', '!=', 1)
            ->update(['level' => 1]);

        // Seed exactly 1 qualifying production building (level >= 2)
        $this->setBuildingLevel(28, 2);

        $result = $this->service->checkPhase1Completion($run);

        $this->assertFalse($result, 'Phase-1 must not complete when fewer than 2 production buildings are at level >= 2');
    }

    public function test_checkPhase1Completion_returns_false_when_fewer_than_3_advisors(): void
    {
        $run = $this->makeRun();

        $this->setBuildingLevel(25, 3);

        // Ensure 2 non-CC buildings at level >= 2
        $this->setBuildingLevel(28, 2);
        $this->setBuildingLevel(30, 2);

        // Only 2 advisors with distinct personell_ids — condition 3 must fail
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 35, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 36, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);

        $result = $this->service->checkPhase1Completion($run);

        $this->assertFalse($result, 'Phase-1 must not complete when fewer than 3 advisors are active');
    }

    public function test_checkPhase1Completion_returns_true_when_all_conditions_met(): void
    {
        $run = $this->makeRun();

        // Condition 1: CC at level 3
        $this->setBuildingLevel(25, 3);

        // Condition 2: 2 non-CC production buildings at level >= 2
        $this->setBuildingLevel(28, 2);
        $this->setBuildingLevel(30, 2);

        // Condition 3: 3 available advisors with distinct personell_ids
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 35, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 36, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 89, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);

        $result = $this->service->checkPhase1Completion($run);

        $this->assertTrue($result, 'Phase-1 must complete when all three conditions are satisfied');
    }

    // ── drawObjectives ────────────────────────────────────────────────────────

    public function test_drawObjectives_creates_3_objectives_for_run(): void
    {
        $run = $this->makeRun(['phase' => 2]);

        $this->service->drawObjectives($run);

        $count = RunObjective::where('run_id', $run->id)->count();

        $this->assertEquals(3, $count, 'drawObjectives must create exactly 3 RunObjective records');
    }

    public function test_drawObjectives_sets_correct_target_values(): void
    {
        // Lock the task pool to all 4 tasks and force deterministic draw of the
        // first 3 by seeding the config pool in the expected order.
        config(['game.run.task_pool' => [
            'task_expertenstab',
            'task_kreditimperium',
            'task_koloniebluete',
            'task_forschungsvorsprung',
        ]]);

        $expectedTargets = [
            'task_expertenstab'        => 1,
            'task_kreditimperium'      => 10,
            'task_koloniebluete'       => 10,
            'task_forschungsvorsprung' => 3,
        ];

        $run = $this->makeRun(['phase' => 2]);

        $this->service->drawObjectives($run);

        $objectives = RunObjective::where('run_id', $run->id)->get()->keyBy('task_key');

        // Every drawn objective must carry the correct target value
        foreach ($objectives as $taskKey => $objective) {
            $this->assertArrayHasKey($taskKey, $expectedTargets, "Unexpected task_key '{$taskKey}' drawn");
            $this->assertEquals(
                $expectedTargets[$taskKey],
                $objective->target_value,
                "Target value mismatch for {$taskKey}"
            );
        }
    }

    // ── updateObjectiveProgress: task_expertenstab ────────────────────────────

    public function test_task_expertenstab_completes_when_5_advisors_with_2_senior(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_expertenstab', 1);

        // 5 advisors: 3 rank-1, 2 rank-2 (senior).
        // Each uses a distinct personell_id to satisfy the (colony_id, personell_id) unique constraint.
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 35, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 36, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 89, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 92, 'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 93, 'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 0]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertNotNull($objective->completed_at, 'task_expertenstab must be marked completed');
        $this->assertEquals(1, $objective->current_value);
    }

    public function test_task_expertenstab_does_not_complete_when_not_enough_senior_advisors(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_expertenstab', 1);

        // 5 advisors but only 1 senior — requirement is 2 senior
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 35, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 36, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 89, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 92, 'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0]);
        Advisor::create(['user_id' => $this->userId, 'personell_id' => 93, 'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 0]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertNull($objective->completed_at, 'task_expertenstab must not complete with only 1 senior advisor');
    }

    // ── updateObjectiveProgress: task_forschungsvorsprung ────────────────────

    public function test_task_forschungsvorsprung_completes_when_3_researches_at_level_5(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_forschungsvorsprung', 3);

        // Reset all researches first — testdata has some already at level 19/17 etc.
        $this->resetAllResearchLevels();

        // Raise exactly 3 to level 5 (research IDs 33, 34, 39 exist for colony 1)
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => 33],
            ['level' => 5]
        );
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => 34],
            ['level' => 5]
        );
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => 39],
            ['level' => 5]
        );

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertNotNull($objective->completed_at, 'task_forschungsvorsprung must complete when 3 researches are at level >= 5');
        $this->assertEquals(3, $objective->current_value);
    }

    public function test_task_forschungsvorsprung_does_not_complete_when_fewer_than_3_at_level_5(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_forschungsvorsprung', 3);

        // Reset all researches first so testdata high levels don't interfere
        $this->resetAllResearchLevels();

        // Only 2 researches at level 5
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => 33],
            ['level' => 5]
        );
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => 34],
            ['level' => 5]
        );
        // research_id=39 stays at level 0 after reset

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertNull($objective->completed_at, 'task_forschungsvorsprung must not complete with only 2 qualifying researches');
        $this->assertEquals(2, $objective->current_value);
    }

    // ── updateObjectiveProgress: task_kreditimperium ─────────────────────────

    public function test_task_kreditimperium_increments_streak_when_credits_above_threshold(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_kreditimperium', 10, 0);

        $this->setCredits(6000);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(1, $objective->streak_value, 'streak_value must increment by 1 when credits >= 5000');
        $this->assertEquals(1, $objective->current_value);
        $this->assertNull($objective->completed_at, 'objective must not be completed after only 1 streak tick');
    }

    public function test_task_kreditimperium_resets_streak_when_credits_below_threshold(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        // Pre-load a streak of 5
        $objective = $this->makeObjective($run, 'task_kreditimperium', 10, 5);

        // Credits below the 5000 threshold
        $this->setCredits(2000);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(0, $objective->streak_value, 'streak_value must reset to 0 when credits < 5000');
        $this->assertEquals(0, $objective->current_value);
        $this->assertNull($objective->completed_at);
    }

    public function test_task_kreditimperium_completes_when_streak_reaches_target(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        // streak already at 9 — one more tick above threshold should complete it
        $objective = $this->makeObjective($run, 'task_kreditimperium', 10, 9);

        $this->setCredits(5000);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(10, $objective->streak_value);
        $this->assertNotNull($objective->completed_at, 'task_kreditimperium must be marked complete when streak reaches 10');
    }

    // ── checkFailStates ───────────────────────────────────────────────────────

    public function test_checkFailStates_returns_trust_collapse_when_trust_below_threshold(): void
    {
        $run = $this->makeRun(['current_tick' => 10]);

        // Default threshold is -20; set trust to -25 (below)
        $this->setTrust(-25);

        $result = $this->service->checkFailStates($run);

        $this->assertEquals('trust_collapse', $result, 'Must return trust_collapse when trust < -20');
    }

    public function test_checkFailStates_returns_time_limit_when_tick_equals_tick_limit(): void
    {
        $tickLimit = (int) config('game.run.tick_limit', 100);

        $run = $this->makeRun(['current_tick' => $tickLimit]);

        // Trust at a safe level so it does not trigger trust_collapse first
        $this->setTrust(50);

        $result = $this->service->checkFailStates($run);

        $this->assertEquals('time_limit', $result, 'Must return time_limit when current_tick >= tick_limit');
    }

    public function test_checkFailStates_returns_null_when_no_fail_conditions(): void
    {
        $run = $this->makeRun(['current_tick' => 10]);

        $this->setTrust(50);

        $result = $this->service->checkFailStates($run);

        $this->assertNull($result, 'Must return null when neither fail condition is active');
    }

    // ── endRun ────────────────────────────────────────────────────────────────

    public function test_endRun_sets_status_and_ended_at(): void
    {
        $run = $this->makeRun();

        $this->service->endRun($run, 'failed', 'trust_collapse');

        $row = DB::table('runs')->where('id', $run->id)->first();

        $this->assertEquals('failed', $row->status, 'status must be persisted as failed');
        $this->assertEquals('trust_collapse', $row->fail_reason, 'fail_reason must be persisted');
        $this->assertNotNull($row->ended_at, 'ended_at must be set after endRun');
    }

    public function test_endRun_sets_status_completed_without_fail_reason(): void
    {
        $run = $this->makeRun();

        $this->service->endRun($run, 'completed');

        $row = DB::table('runs')->where('id', $run->id)->first();

        $this->assertEquals('completed', $row->status);
        $this->assertNull($row->fail_reason, 'fail_reason must be null for a successful run');
        $this->assertNotNull($row->ended_at);
    }

    // ── calculateScore ────────────────────────────────────────────────────────

    public function test_calculateScore_returns_0_for_failed_run(): void
    {
        $run = $this->makeRun(['status' => 'failed', 'current_tick' => 50]);

        $score = $this->service->calculateScore($run);

        $this->assertEquals(0, $score, 'A failed run must always score 0');
    }

    public function test_calculateScore_returns_positive_score_for_completed_run_with_objectives(): void
    {
        $run = $this->makeRun(['status' => 'completed', 'current_tick' => 50]);

        // 2 completed objectives
        RunObjective::create([
            'run_id'        => $run->id,
            'task_key'      => 'task_expertenstab',
            'target_value'  => 1,
            'current_value' => 1,
            'streak_value'  => 0,
            'completed_at'  => 40,
        ]);
        RunObjective::create([
            'run_id'        => $run->id,
            'task_key'      => 'task_kreditimperium',
            'target_value'  => 10,
            'current_value' => 10,
            'streak_value'  => 10,
            'completed_at'  => 45,
        ]);

        // Provide positive resources for score calculation
        $this->setCredits(3000);
        $this->setTrust(40);

        $score = $this->service->calculateScore($run);

        // Formula: (2 × 1000) + ((100 - 50) × 10) + (3000 / 10) + (40 × 5)
        //        = 2000 + 500 + 300 + 200 = 3000
        $this->assertGreaterThan(0, $score, 'A completed run with objectives and resources must yield a positive score');
        $this->assertEquals(3000, $score, 'Score formula must compute correctly');
    }
}
