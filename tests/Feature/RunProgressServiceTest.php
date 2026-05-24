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
 * TASK_SELBSTVERSORGUNG (streak)
 *   - streak increments when regolith>50 AND organics>50 AND supply>0
 *   - streak resets to 0 when regolith fails (<= 50)
 *   - completes (completed_at set) when streak reaches target_value (15)
 *
 * TASK_EXPEDITIONSSTATUS (counter)
 *   - completes when 19+ explored colony-zone tiles exist
 *   - does not complete with only 10 such tiles
 *
 * TASK_INGENIEURSLEISTUNG (counter)
 *   - completes when sum of status_points >= 200
 *   - does not complete when sum < 200
 *
 * TASK_HANDELSPARTNER (counter)
 *   - completes when 5+ sold items from visits after run.started_at
 *   - items from visits before run.started_at are not counted
 *
 * TASK_BEWAEHRUNGSPROBE (counter)
 *   - completes when 3+ encounter_won events after run.started_at
 *   - events from before run.started_at are not counted
 *
 * DRAW OBJECTIVES — COMBO BLACKLIST
 *   - at most 1 economy task in any drawn set of 3
 *
 * NEXUS INTERVENTIONS
 *   - sol 30 warning fires when no task above 50% progress
 *   - sol 30 warning does NOT fire when a task is above 50%
 *   - sol 50 warning fires when 0 objectives completed
 *   - sol 65 sanction fires and locks advisor when 0 objectives completed
 *   - sol 65 sanction does NOT fire when at least one objective is completed
 *   - nexus_debt > 12000 triggers failed run status
 *   - sol 80 countdown fires when current_tick >= tick_limit - 20
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

    // ── Additional helpers ────────────────────────────────────────────────────

    /**
     * Set a colony resource amount (generic by resource_id).
     */
    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['resource_id' => $resourceId, 'colony_id' => $this->colonyId],
            ['amount' => $amount]
        );
    }

    /**
     * Insert N explored colony-zone tiles with distinct (q, r) coordinates.
     * q is set to 100 + index and r to 0 to avoid conflicts with testdata tiles.
     */
    private function insertExploredColonyZoneTiles(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            DB::table('colony_tiles')->insert([
                'colony_id'      => $this->colonyId,
                'q'              => 100 + $i,
                'r'              => 0,
                'ring'           => 1,
                'tile_type'      => 'regolith',
                'is_explored'    => 1,
                'is_colony_zone' => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    // ── task_selbstversorgung (streak) ────────────────────────────────────────

    public function test_task_selbstversorgung_increments_streak_when_all_conditions_met(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_selbstversorgung', 15, 0);

        // regolith (3) > 50, organics (5) > 50, supply (4) > 0
        $this->setColonyResource(3, 60);
        $this->setColonyResource(5, 55);
        $this->setColonyResource(4, 1);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(1, $objective->streak_value, 'streak_value must increment to 1 when all 3 conditions are met');
        $this->assertEquals(1, $objective->current_value);
        $this->assertNull($objective->completed_at, 'objective must not complete after only 1 streak tick');
    }

    public function test_task_selbstversorgung_resets_streak_when_regolith_fails(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        // Pre-load a streak of 7
        $objective = $this->makeObjective($run, 'task_selbstversorgung', 15, 7);

        // Regolith at exactly 50 — condition requires > 50, so this fails
        $this->setColonyResource(3, 50);
        $this->setColonyResource(5, 55);
        $this->setColonyResource(4, 1);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(0, $objective->streak_value, 'streak_value must reset to 0 when regolith <= 50');
        $this->assertEquals(0, $objective->current_value);
        $this->assertNull($objective->completed_at);
    }

    public function test_task_selbstversorgung_completes_when_streak_reaches_target(): void
    {
        $run = $this->makeRun(['current_tick' => 20, 'phase' => 2]);
        // Pre-load streak at 14 — one passing tick should complete it (target=15)
        $objective = $this->makeObjective($run, 'task_selbstversorgung', 15, 14);

        $this->setColonyResource(3, 100);
        $this->setColonyResource(5, 100);
        $this->setColonyResource(4, 5);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(15, $objective->streak_value, 'streak_value must reach 15');
        $this->assertNotNull($objective->completed_at, 'task_selbstversorgung must be marked complete when streak reaches target_value');
    }

    // ── task_expeditionsstatus (counter) ──────────────────────────────────────

    public function test_task_expeditionsstatus_completes_when_19_explored_colony_zone_tiles_exist(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_expeditionsstatus', 19);

        $this->insertExploredColonyZoneTiles(19);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(19, $objective->current_value);
        $this->assertNotNull($objective->completed_at, 'task_expeditionsstatus must complete when 19 explored colony-zone tiles exist');
    }

    public function test_task_expeditionsstatus_does_not_complete_with_only_10_tiles(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_expeditionsstatus', 19);

        $this->insertExploredColonyZoneTiles(10);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(10, $objective->current_value);
        $this->assertNull($objective->completed_at, 'task_expeditionsstatus must not complete with only 10 tiles');
    }

    // ── task_ingenieursleistung (counter) ─────────────────────────────────────

    public function test_task_ingenieursleistung_completes_when_status_points_sum_reaches_200(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_ingenieursleistung', 200);

        // Remove all existing buildings to have full control over the sum
        DB::table('colony_buildings')->where('colony_id', $this->colonyId)->delete();

        // Insert buildings whose status_points sum to exactly 200
        DB::table('colony_buildings')->insert([
            ['colony_id' => $this->colonyId, 'building_id' => 25, 'level' => 3, 'status_points' => 100],
            ['colony_id' => $this->colonyId, 'building_id' => 28, 'level' => 2, 'status_points' => 100],
        ]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(200, $objective->current_value);
        $this->assertNotNull($objective->completed_at, 'task_ingenieursleistung must complete when status_points sum >= 200');
    }

    public function test_task_ingenieursleistung_does_not_complete_when_status_points_below_200(): void
    {
        $run = $this->makeRun(['current_tick' => 10, 'phase' => 2]);
        $objective = $this->makeObjective($run, 'task_ingenieursleistung', 200);

        DB::table('colony_buildings')->where('colony_id', $this->colonyId)->delete();

        // Sum = 199 — just below target
        DB::table('colony_buildings')->insert([
            ['colony_id' => $this->colonyId, 'building_id' => 25, 'level' => 3, 'status_points' => 100],
            ['colony_id' => $this->colonyId, 'building_id' => 28, 'level' => 2, 'status_points' => 99],
        ]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(199, $objective->current_value);
        $this->assertNull($objective->completed_at, 'task_ingenieursleistung must not complete when status_points sum < 200');
    }

    // ── task_handelspartner (counter) ─────────────────────────────────────────

    public function test_task_handelspartner_completes_when_5_sold_items_after_run_start(): void
    {
        $startedAt = now()->subHour();
        $run = $this->makeRun([
            'current_tick' => 10,
            'phase'        => 2,
            'started_at'   => $startedAt,
        ]);
        $objective = $this->makeObjective($run, 'task_handelspartner', 5);

        // Create a merchant visit AFTER run started_at
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id'   => $this->colonyId,
            'tick_start'  => 5,
            'tick_end'    => 8,
            'was_visited' => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Insert 5 sold items for that visit
        for ($i = 0; $i < 5; $i++) {
            DB::table('merchant_items')->insert([
                'visit_id'      => $visitId,
                'item_type'     => 'ap_flex',
                'label'         => 'Test Item ' . $i,
                'cost_credits'  => 100,
                'payload'       => null,
                'sold'          => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(5, $objective->current_value);
        $this->assertNotNull($objective->completed_at, 'task_handelspartner must complete when 5+ sold items from visits after run.started_at');
    }

    public function test_task_handelspartner_does_not_count_items_from_visits_before_run_start(): void
    {
        $startedAt = now();
        $run = $this->makeRun([
            'current_tick' => 10,
            'phase'        => 2,
            'started_at'   => $startedAt,
        ]);
        $objective = $this->makeObjective($run, 'task_handelspartner', 5);

        // Create a merchant visit BEFORE run started_at
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id'   => $this->colonyId,
            'tick_start'  => 2,
            'tick_end'    => 4,
            'was_visited' => true,
            'created_at'  => now()->subDay(),
            'updated_at'  => now()->subDay(),
        ]);

        // Insert 5 sold items — but the visit predates the run, so they must not count
        for ($i = 0; $i < 5; $i++) {
            DB::table('merchant_items')->insert([
                'visit_id'      => $visitId,
                'item_type'     => 'ap_flex',
                'label'         => 'Old Item ' . $i,
                'cost_credits'  => 100,
                'payload'       => null,
                'sold'          => 1,
                'created_at'    => now()->subDay(),
                'updated_at'    => now()->subDay(),
            ]);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(0, $objective->current_value, 'Items from visits before run.started_at must not be counted');
        $this->assertNull($objective->completed_at);
    }

    // ── task_bewaehrungsprobe (counter) ───────────────────────────────────────

    public function test_task_bewaehrungsprobe_completes_when_3_encounter_won_events_after_run_start(): void
    {
        $startedAt = now()->subHour();
        $run = $this->makeRun([
            'current_tick' => 10,
            'phase'        => 2,
            'started_at'   => $startedAt,
        ]);
        $objective = $this->makeObjective($run, 'task_bewaehrungsprobe', 3);

        for ($i = 0; $i < 3; $i++) {
            DB::table('innn_events')->insert([
                'user'       => $this->userId,
                'tick'       => 7 + $i,
                'event'      => 'encounter_won',
                'area'       => 'combat',
                'parameters' => serialize([]),
                'created_at' => now(),
            ]);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(3, $objective->current_value);
        $this->assertNotNull($objective->completed_at, 'task_bewaehrungsprobe must complete when 3+ encounter_won events after run.started_at');
    }

    public function test_task_bewaehrungsprobe_does_not_count_events_before_run_start(): void
    {
        $startedAt = now();
        $run = $this->makeRun([
            'current_tick' => 10,
            'phase'        => 2,
            'started_at'   => $startedAt,
        ]);
        $objective = $this->makeObjective($run, 'task_bewaehrungsprobe', 3);

        // Insert 3 encounter_won events with created_at BEFORE started_at
        for ($i = 0; $i < 3; $i++) {
            DB::table('innn_events')->insert([
                'user'       => $this->userId,
                'tick'       => 2 + $i,
                'event'      => 'encounter_won',
                'area'       => 'combat',
                'parameters' => serialize([]),
                'created_at' => now()->subDay(),
            ]);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertEquals(0, $objective->current_value, 'Events before run.started_at must not be counted');
        $this->assertNull($objective->completed_at);
    }

    // ── drawObjectives — combo blacklist ──────────────────────────────────────

    public function test_drawObjectives_never_draws_both_economy_tasks_in_one_set(): void
    {
        // Pool: 2 economy tasks + 5 non-economy tasks
        config(['game.run.task_pool' => [
            'task_kreditimperium',
            'task_handelspartner',
            'task_expertenstab',
            'task_forschungsvorsprung',
            'task_selbstversorgung',
            'task_expeditionsstatus',
            'task_bewaehrungsprobe',
        ]]);

        $economyTasks = ['task_kreditimperium', 'task_handelspartner'];

        // Run 20 draws; none should contain both economy tasks simultaneously
        for ($draw = 0; $draw < 20; $draw++) {
            $run = $this->makeRun(['phase' => 2]);
            $this->service->drawObjectives($run);

            $drawnKeys = RunObjective::where('run_id', $run->id)
                ->pluck('task_key')
                ->all();

            $economyCount = count(array_intersect($drawnKeys, $economyTasks));

            $this->assertLessThanOrEqual(
                1,
                $economyCount,
                "Draw #{$draw} contained both economy tasks: " . implode(', ', $drawnKeys)
            );
        }
    }

    // ── checkNexusInterventions ───────────────────────────────────────────────

    /**
     * Build a run at Phase 2, sol X (phase2_start_tick=0, current_tick=sol).
     * started_at is set 1 hour in the past so newly inserted events (created_at≈now)
     * pass the eventAlreadyFired check (>= started_at).
     */
    private function makePhase2Run(int $sol, array $overrides = []): Run
    {
        return $this->makeRun(array_merge([
            'phase'             => 2,
            'phase2_start_tick' => 0,
            'current_tick'      => $sol,
            'started_at'        => now()->subHour(),
        ], $overrides));
    }

    public function test_nexus_sol30_warning_fires_when_no_task_above_50_percent(): void
    {
        $run = $this->makePhase2Run(30);

        // One objective with 0% progress (current_value=0, target_value=10)
        $this->makeObjective($run, 'task_expertenstab', 10);

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_warning_sol30')
            ->exists();

        $this->assertTrue($fired, 'nexus_warning_sol30 must fire when no task is above 50% progress at sol 30');
    }

    public function test_nexus_sol30_warning_does_not_fire_when_task_above_50_percent(): void
    {
        $run = $this->makePhase2Run(30);

        // One objective above 50% (current=6 of 10 = 60%)
        RunObjective::create([
            'run_id'        => $run->id,
            'task_key'      => 'task_expertenstab',
            'target_value'  => 10,
            'current_value' => 6,
            'streak_value'  => 0,
            'completed_at'  => null,
        ]);

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_warning_sol30')
            ->exists();

        $this->assertFalse($fired, 'nexus_warning_sol30 must NOT fire when at least one task is above 50% progress');
    }

    public function test_nexus_sol50_warning_fires_when_0_objectives_completed(): void
    {
        $run = $this->makePhase2Run(50);

        // An open objective with no completion
        $this->makeObjective($run, 'task_expertenstab', 10);

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_warning_sol50')
            ->exists();

        $this->assertTrue($fired, 'nexus_warning_sol50 must fire when 0 objectives are completed at sol 50');
    }

    public function test_nexus_sol65_sanction_fires_and_locks_advisor_when_0_objectives_completed(): void
    {
        $run = $this->makePhase2Run(65);

        // No completed objectives
        $this->makeObjective($run, 'task_expertenstab', 10);

        // Insert one active advisor to be locked
        $advisor = $this->insertAdvisor();

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_sanction_sol65')
            ->exists();

        $this->assertTrue($fired, 'nexus_sanction_sol65 must fire when 0 objectives are completed at sol 65');

        $advisor->refresh();
        $this->assertEquals(
            66,
            $advisor->unavailable_until_tick,
            'The advisor must be locked for 1 sol (unavailable_until_tick = current_tick + 1 = 66)'
        );
    }

    public function test_nexus_sol65_sanction_does_not_fire_when_objective_is_completed(): void
    {
        $run = $this->makePhase2Run(65);

        // One completed objective
        RunObjective::create([
            'run_id'        => $run->id,
            'task_key'      => 'task_expertenstab',
            'target_value'  => 1,
            'current_value' => 1,
            'streak_value'  => 0,
            'completed_at'  => 40,
        ]);

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_sanction_sol65')
            ->exists();

        $this->assertFalse($fired, 'nexus_sanction_sol65 must NOT fire when at least one objective is already completed');
    }

    public function test_nexus_debt_above_12000_fails_run(): void
    {
        $run = $this->makePhase2Run(60, ['nexus_debt' => 13000]);

        // Objective present so the run is in a valid state
        $this->makeObjective($run, 'task_expertenstab', 10);

        $this->service->checkNexusInterventions($run);

        $run->refresh();
        $this->assertEquals('failed', $run->status, 'Run must be failed when nexus_debt > 12000');
        $this->assertEquals('nexus_debt', $run->fail_reason);
    }

    public function test_nexus_sol80_countdown_fires_when_tick_near_limit(): void
    {
        // tick_limit = 100, current_tick = 85 → 85 >= 100 - 20 = 80 → fires
        $run = $this->makePhase2Run(85, [
            'settings' => ['tick_limit' => 100],
        ]);

        $this->makeObjective($run, 'task_expertenstab', 10);

        $this->service->checkNexusInterventions($run);

        $fired = DB::table('innn_events')
            ->where('user', $this->userId)
            ->where('event', 'run.nexus_countdown_sol80')
            ->exists();

        $this->assertTrue($fired, 'nexus_countdown_sol80 must fire when current_tick >= tick_limit - 20');
    }
}
