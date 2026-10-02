<?php

namespace Tests\Feature;

/**
 * A45 "Sieg-Timing / Ziele" — objective parameters from config('game.run.tasks'),
 * the new measurements for task_engineering_output / task_expedition_coverage /
 * task_senior_advisors, the best-streak record and the objective labels
 * (displayed number = effective number).
 *
 * Nexus checkpoints and the countdown are covered in RunNexusCheckpointTest.
 */

use App\Models\Advisor;
use App\Models\Colony;
use App\Models\Run;
use App\Models\RunObjective;
use App\Services\ColonyService;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunObjectivesTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    private const SHIP_DRONE = 85;

    private RunProgressService $service;

    /** Personell IDs present in testdata — each advisor needs a distinct one. */
    private array $personellPool = [35, 36, 89, 92, 93];

    private int $personellCursor = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(RunProgressService::class);

        Advisor::where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
    }

    // ── Config ────────────────────────────────────────────────────────────────

    public function test_every_pool_task_has_a_config_entry_with_category_type_and_target(): void
    {
        $tasks = config('game.run.tasks');

        $this->assertIsArray($tasks);
        foreach (config('game.run.task_pool') as $taskKey) {
            $this->assertArrayHasKey($taskKey, $tasks, "run.tasks is missing {$taskKey}");
            $this->assertIsString($tasks[$taskKey]['category'] ?? null, "{$taskKey} needs a category");
            $this->assertContains($tasks[$taskKey]['type'] ?? null, ['counter', 'streak'], "{$taskKey} needs a type");
            $this->assertIsInt($tasks[$taskKey]['target'] ?? null, "{$taskKey} needs an integer target");
            $this->assertGreaterThan(0, $tasks[$taskKey]['target']);
        }
    }

    public function test_owner_decided_task_values_are_in_config(): void
    {
        $this->assertSame(4, config('game.run.tasks.task_senior_advisors.target'));
        $this->assertSame(3, config('game.run.tasks.task_senior_advisors.min_rank'));
        $this->assertSame(6000, config('game.run.tasks.task_credit_reserve.threshold'));
        $this->assertSame(20, config('game.run.tasks.task_credit_reserve.target'));
        $this->assertSame(35, config('game.run.tasks.task_engineering_output.target'));
        $this->assertSame(10, config('game.run.tasks.task_expedition_coverage.target'));
        $this->assertSame('normal', config('game.run.tasks.task_expedition_coverage.min_difficulty'));
        // R1 (Owner 2026-10-01)
        $this->assertSame(35, config('game.run.tasks.task_self_sufficiency.target'));
        $this->assertSame(8, config('game.run.tasks.task_trade_volume.target'));
        $this->assertSame(7, config('game.run.tasks.task_research_lead.target'));
        $this->assertSame(40, config('game.run.tasks.task_colony_prosperity.threshold'));
        $this->assertSame(12, config('game.run.tasks.task_colony_prosperity.target'));
    }

    public function test_the_old_credit_reserve_threshold_key_is_gone(): void
    {
        $this->assertNull(config('game.run.task_credit_reserve_threshold'));
    }

    public function test_draw_objectives_takes_target_values_from_config(): void
    {
        config([
            'game.run.task_pool' => ['task_research_lead', 'task_engineering_output', 'task_trade_volume'],
            'game.run.tasks.task_research_lead.target' => 7,
            'game.run.tasks.task_engineering_output.target' => 41,
            'game.run.tasks.task_trade_volume.target' => 2,
        ]);
        $run = $this->makeRun();

        $this->service->drawObjectives($run);

        $targets = array_map('intval', RunObjective::where('run_id', $run->id)->pluck('target_value', 'task_key')->all());
        ksort($targets);
        $this->assertSame(['task_engineering_output' => 41, 'task_research_lead' => 7, 'task_trade_volume' => 2], $targets);
    }

    public function test_combo_blacklist_reads_categories_from_config(): void
    {
        // Declare two non-economy tasks as economy: they must never be drawn together.
        config([
            'game.run.task_pool' => ['task_research_lead', 'task_engineering_output', 'task_trade_volume', 'task_senior_advisors'],
            'game.run.tasks.task_research_lead.category' => 'economy',
            'game.run.tasks.task_engineering_output.category' => 'economy',
            'game.run.tasks.task_trade_volume.category' => 'research',
        ]);

        foreach (range(1, 12) as $seed) {
            $run = $this->makeRun(['rng_seed' => $seed]);
            $this->service->drawObjectives($run);

            $keys = RunObjective::where('run_id', $run->id)->pluck('task_key')->all();
            $this->assertFalse(
                in_array('task_research_lead', $keys, true) && in_array('task_engineering_output', $keys, true),
                "seed {$seed} drew two economy tasks: ".implode(',', $keys)
            );
            $run->delete();
        }
    }

    // ── task_senior_advisors ─────────────────────────────────────────────────

    public function test_senior_advisors_completes_with_four_advisors_at_rank_three(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_senior_advisors', 4);
        foreach (range(1, 4) as $i) {
            $this->insertAdvisor(3);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(4, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_senior_advisors_counts_only_advisors_at_min_rank(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_senior_advisors', 4);
        $this->insertAdvisor(3);
        $this->insertAdvisor(3);
        $this->insertAdvisor(3);
        $this->insertAdvisor(2);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(3, $objective->current_value, 'a rank-2 advisor does not count');
        $this->assertNull($objective->completed_at);
    }

    public function test_senior_advisors_min_rank_comes_from_config(): void
    {
        config(['game.run.tasks.task_senior_advisors.min_rank' => 2]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_senior_advisors', 2);
        $this->insertAdvisor(2);
        $this->insertAdvisor(2);
        $this->insertAdvisor(1);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(2, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    // ── task_engineering_output ──────────────────────────────────────────────

    public function test_engineering_output_sums_building_levels_including_every_instance(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_engineering_output', 30);
        $this->replaceBuildings([[25, 1, 5], [28, 1, 5], [28, 2, 5], [41, 1, 5], [31, 1, 5], [44, 1, 5]]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(30, $objective->current_value);
        $this->assertNotNull($objective->completed_at, 'sum of levels 30 meets target 30');
    }

    public function test_engineering_output_one_level_short_does_not_complete(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_engineering_output', 30);
        $this->replaceBuildings([[25, 1, 5], [28, 1, 5], [28, 2, 5], [41, 1, 5], [31, 1, 5], [44, 1, 4]]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(29, $objective->current_value);
        $this->assertNull($objective->completed_at);
    }

    public function test_engineering_output_ignores_status_points(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_engineering_output', 30);
        $this->replaceBuildings([[25, 1, 2]]);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->update(['status_points' => 400]);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(2, $objective->refresh()->current_value);
    }

    // ── task_expedition_coverage ─────────────────────────────────────────────

    public function test_expedition_coverage_counts_successful_missions_at_normal_or_harder(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_expedition_coverage', 3);
        $this->insertMission('normal', succeeded: true);
        $this->insertMission('hard', succeeded: true);
        $this->insertMission('easy', succeeded: true);           // too easy
        $this->insertMission('normal', succeeded: false);        // failed roll
        $this->insertMission('hard', succeeded: null, state: 'aborted');
        $this->insertMission('normal', succeeded: null, state: 'active');

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(2, $objective->current_value);
        $this->assertNull($objective->completed_at);
    }

    public function test_expedition_coverage_completes_at_target(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_expedition_coverage', 10);
        foreach (range(1, 10) as $i) {
            $this->insertMission('normal', succeeded: true);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(10, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_expedition_coverage_min_difficulty_comes_from_config(): void
    {
        config(['game.run.tasks.task_expedition_coverage.min_difficulty' => 'hard']);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_expedition_coverage', 10);
        $this->insertMission('normal', succeeded: true);
        $this->insertMission('hard', succeeded: true);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(1, $objective->refresh()->current_value);
    }

    // ── Thresholds from config (streak / counter tasks) ──────────────────────

    public function test_credit_reserve_threshold_comes_from_config(): void
    {
        config(['game.run.tasks.task_credit_reserve.threshold' => 100]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_credit_reserve', 10);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 100]);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(1, $objective->refresh()->streak_value, 'credits >= threshold counts');
    }

    public function test_colony_prosperity_threshold_comes_from_config(): void
    {
        config(['game.run.tasks.task_colony_prosperity.threshold' => 10]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_colony_prosperity', 10, streak: 3);

        $this->setColonyResource(12, 11);
        $this->service->updateObjectiveProgress($run);
        $this->assertSame(4, $objective->refresh()->streak_value, 'trust above threshold extends the streak');

        $this->setColonyResource(12, 10);
        $this->service->updateObjectiveProgress($run);
        $this->assertSame(0, $objective->refresh()->streak_value, 'trust at the threshold resets the streak');
    }

    public function test_research_lead_min_level_comes_from_config(): void
    {
        config(['game.run.tasks.task_research_lead.min_level' => 3]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_research_lead', 2);
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->update(['level' => 0]);
        DB::table('colony_researches')->updateOrInsert(['colony_id' => self::COLONY_ID, 'research_id' => 90], ['level' => 3]);
        DB::table('colony_researches')->updateOrInsert(['colony_id' => self::COLONY_ID, 'research_id' => 91], ['level' => 3]);
        DB::table('colony_researches')->updateOrInsert(['colony_id' => self::COLONY_ID, 'research_id' => 92], ['level' => 2]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(2, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_self_sufficiency_thresholds_come_from_config(): void
    {
        config([
            'game.run.tasks.task_self_sufficiency.regolith_min' => 5,
            'game.run.tasks.task_self_sufficiency.organics_min' => 6,
        ]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 15);
        $this->setColonyResource(3, 6);
        $this->setColonyResource(5, 7);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 1]);

        $this->service->updateObjectiveProgress($run);
        $this->assertSame(1, $objective->refresh()->streak_value);

        $this->setColonyResource(5, 6);
        $this->service->updateObjectiveProgress($run);
        $this->assertSame(0, $objective->refresh()->streak_value, 'organics at the minimum breaks the streak');
    }

    // ── Best streak ──────────────────────────────────────────────────────────

    public function test_best_streak_survives_a_streak_reset(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_credit_reserve', 10);
        $threshold = (int) config('game.run.tasks.task_credit_reserve.threshold');

        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $threshold]);
        foreach (range(1, 5) as $i) {
            $this->service->updateObjectiveProgress($run);
        }
        $this->assertSame(5, $objective->refresh()->best_streak_value);

        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $threshold - 1]);
        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(0, $objective->streak_value);
        $this->assertSame(0, $objective->current_value);
        $this->assertSame(5, $objective->best_streak_value, 'the best streak is kept after a reset');

        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $threshold]);
        $this->service->updateObjectiveProgress($run);
        $this->assertSame(5, $objective->refresh()->best_streak_value, 'a shorter new streak does not lower the record');
    }

    public function test_best_streak_is_tracked_for_every_streak_task(): void
    {
        $run = $this->makeRun();
        config(['game.run.tasks.task_colony_prosperity.threshold' => 0]);
        $this->setColonyResource(12, 50);
        $objective = $this->makeObjective($run, 'task_colony_prosperity', 10, streak: 6);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(7, $objective->refresh()->best_streak_value);
    }

    // ── Labels: displayed number = effective number ──────────────────────────

    public function test_task_labels_show_the_configured_values(): void
    {
        config([
            'game.run.tasks.task_credit_reserve.threshold' => 4321,
            'game.run.tasks.task_colony_prosperity.threshold' => 66,
            'game.run.tasks.task_senior_advisors.min_rank' => 3,
        ]);
        $catalog = app(RunTaskCatalog::class);

        $credit = $catalog->label('task_credit_reserve', 12);
        $this->assertStringContainsString('4.321', $credit);
        $this->assertStringContainsString('12', $credit);

        $this->assertStringContainsString('66', $catalog->label('task_colony_prosperity', 9));

        $senior = $catalog->label('task_senior_advisors', 4);
        $this->assertStringContainsString('4', $senior);
        $this->assertStringContainsString('3', $senior);

        $this->assertStringContainsString('30', $catalog->label('task_engineering_output', 30));
        $this->assertStringContainsString(__('missions.difficulty_normal'), $catalog->label('task_expedition_coverage', 10));
    }

    public function test_every_task_label_is_translated_without_leftover_placeholders(): void
    {
        $catalog = app(RunTaskCatalog::class);

        foreach (config('game.run.task_pool') as $taskKey) {
            $label = $catalog->label($taskKey);
            $this->assertNotSame('run.'.$taskKey, $label);
            $this->assertDoesNotMatchRegularExpression('/:[a-z_]+/', $label, "{$taskKey} label has an unfilled placeholder: {$label}");
        }
    }

    public function test_phase_progress_label_uses_the_objectives_target(): void
    {
        $run = $this->makeRun(['phase' => 2]);
        RunObjective::create([
            'run_id' => $run->id,
            'task_key' => 'task_engineering_output',
            'target_value' => 33,
            'current_value' => 12,
            'streak_value' => 0,
            'completed_at' => null,
        ]);

        $progress = app(ColonyService::class)->getPhaseProgress(Colony::findOrFail(self::COLONY_ID));

        $objective = $progress['objectives'][0];
        $this->assertSame(33, $objective['target']);
        $this->assertStringContainsString('33', $objective['label']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeRun(array $overrides = []): Run
    {
        DB::table('runs')->where('colony_id', self::COLONY_ID)->where('status', 'active')->update(['status' => 'failed']);

        return Run::create(array_merge([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'current_tick' => 40,
            'status' => 'active',
            'phase' => 2,
            'phase2_start_tick' => 20,
            'started_at' => now()->subHour(),
        ], $overrides));
    }

    private function makeObjective(Run $run, string $taskKey, int $target, int $streak = 0): RunObjective
    {
        return RunObjective::create([
            'run_id' => $run->id,
            'task_key' => $taskKey,
            'target_value' => $target,
            'current_value' => $streak,
            'streak_value' => $streak,
            'completed_at' => null,
        ]);
    }

    private function insertAdvisor(int $rank): void
    {
        Advisor::create([
            'user_id' => self::USER_ID,
            'personell_id' => $this->personellPool[$this->personellCursor++ % count($this->personellPool)],
            'colony_id' => self::COLONY_ID,
            'rank' => $rank,
            'active_ticks' => 60,
        ]);
    }

    /**
     * @param  array<int, array{0:int, 1:int, 2:int}>  $rows  [building_id, instance_id, level]
     */
    private function replaceBuildings(array $rows): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->delete();
        foreach ($rows as [$buildingId, $instanceId, $level]) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => $buildingId,
                'instance_id' => $instanceId,
                'level' => $level,
                'status_points' => 20,
            ]);
        }
    }

    private function insertMission(string $difficulty, ?bool $succeeded, string $state = 'completed'): void
    {
        DB::table('colony_hangar_missions')->insert([
            'colony_id' => self::COLONY_ID,
            'instance_id' => 1,
            'ship_id' => self::SHIP_DRONE,
            'destination' => 'mission_courier_run',
            'sol_distance' => 1,
            'difficulty' => $difficulty,
            'dispatch_tick' => 10,
            'recall_tick' => null,
            'state' => $state,
            'succeeded' => $succeeded,
            'created_at' => now(),
        ]);
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }
}
