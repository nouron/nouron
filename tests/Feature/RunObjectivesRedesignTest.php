<?php

namespace Tests\Feature;

/**
 * Run objectives redesign (spec docs/superpowers/specs/2026-11-25-run-ziele-umbau.md,
 * acceptance criteria §10):
 *  - draw rule: at least one medium- and one long-horizon objective per draw,
 *    stored in seed-rank order (§2.2)
 *  - Forschungsvorsprung = sum of knowledge levels (§3.1)
 *  - Expertenstab = sum of advisor ranks (§3.2)
 *  - Selbstversorgung = own harvest covers the colonists' need (§3.3)
 *  - Ingenieursleistung target 32 (§3.4)
 *
 * Owner 2026-11-26: one research target for every opening (no opening_target yet).
 */

use App\Models\Advisor;
use App\Models\Run;
use App\Models\RunObjective;
use App\Services\AdvisorService;
use App\Services\PathBuildingGateService;
use App\Services\ResourceFlowService;
use App\Services\ResourcesService;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class RunObjectivesRedesignTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

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
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 0]);
    }

    // ── Config ────────────────────────────────────────────────────────────────

    public function test_every_pool_task_has_a_valid_horizon(): void
    {
        foreach (config('game.run.task_pool') as $taskKey) {
            $this->assertContains(
                config("game.run.tasks.{$taskKey}.horizon"),
                ['medium', 'long'],
                "{$taskKey} needs horizon 'medium' or 'long'"
            );
        }
    }

    public function test_horizons_and_targets_follow_the_spec(): void
    {
        $horizons = collect(config('game.run.task_pool'))
            ->mapWithKeys(fn (string $key): array => [$key => config("game.run.tasks.{$key}.horizon")])
            ->all();

        $this->assertSame([
            'task_senior_advisors' => 'long',
            'task_credit_reserve' => 'medium',
            'task_colony_prosperity' => 'long',
            'task_research_lead' => 'medium',
            'task_self_sufficiency' => 'long',
            'task_expedition_coverage' => 'medium',
            'task_engineering_output' => 'long',
            'task_trade_volume' => 'medium',
            'task_research_breadth' => 'long',
            'task_mission_variety' => 'long',
            'task_cantina_circle' => 'long',
        ], $horizons);

        $this->assertSame(16, config('game.run.tasks.task_research_lead.target'));
        $this->assertSame(11, config('game.run.tasks.task_senior_advisors.target'));
        $this->assertSame(35, config('game.run.tasks.task_self_sufficiency.target'));
        $this->assertSame(55, config('game.run.tasks.task_self_sufficiency.min_fed'));
        $this->assertSame(32, config('game.run.tasks.task_engineering_output.target'));
    }

    public function test_dropped_parameters_are_gone_from_config(): void
    {
        $this->assertNull(config('game.run.tasks.task_research_lead.min_level'));
        $this->assertNull(config('game.run.tasks.task_research_lead.opening_target'));
        $this->assertNull(config('game.run.tasks.task_senior_advisors.min_rank'));
        $this->assertNull(config('game.run.tasks.task_self_sufficiency.regolith_min'));
        $this->assertNull(config('game.run.tasks.task_self_sufficiency.organics_min'));
    }

    public function test_catalog_reads_the_horizon_and_rejects_unknown_values(): void
    {
        config(['game.run.tasks.task_trade_volume.horizon' => 'short']);
        $catalog = app(RunTaskCatalog::class);

        $this->assertSame('long', $catalog->horizon('task_senior_advisors'));
        $this->assertSame('medium', $catalog->horizon('task_research_lead'));
        $this->assertNull($catalog->horizon('task_trade_volume'), 'an invalid horizon counts as none');
        $this->assertNull($catalog->horizon('task_unknown'));
    }

    // ── Draw rule (§2.2) ─────────────────────────────────────────────────────

    public function test_every_seed_draws_three_objectives_with_both_horizons_and_at_most_one_economy(): void
    {
        $catalog = app(RunTaskCatalog::class);

        foreach (range(1, 1000) as $seed) {
            $keys = $this->service->selectObjectives($seed);

            $this->assertCount(3, $keys, "seed {$seed}");
            $this->assertCount(3, array_unique($keys), "seed {$seed} drew a task twice");
            $horizons = array_map(fn (string $k): ?string => $catalog->horizon($k), $keys);
            $this->assertContains('medium', $horizons, "seed {$seed} has no medium objective: ".implode(',', $keys));
            $this->assertContains('long', $horizons, "seed {$seed} has no long objective: ".implode(',', $keys));
            $economy = array_filter($keys, fn (string $k): bool => $catalog->category($k) === 'economy');
            $this->assertLessThanOrEqual(1, count($economy), "seed {$seed} drew two economy tasks");
        }
    }

    public function test_both_draw_shapes_occur(): void
    {
        $catalog = app(RunTaskCatalog::class);
        $shapes = [];
        foreach (range(1, 200) as $seed) {
            $medium = count(array_filter(
                $this->service->selectObjectives($seed),
                fn (string $k): bool => $catalog->horizon($k) === 'medium'
            ));
            $shapes[$medium] = true;
        }

        $this->assertArrayHasKey(1, $shapes, '1 medium + 2 long must occur');
        $this->assertArrayHasKey(2, $shapes, '2 medium + 1 long must occur');
    }

    public function test_same_seed_and_opening_draw_the_same_objectives_regardless_of_colony_state(): void
    {
        $first = $this->makeRun(['rng_seed' => 777]);
        $this->service->drawObjectives($first);
        $keysFirst = RunObjective::where('run_id', $first->id)->orderBy('id')->pluck('task_key')->all();

        // Different colony state, same opening: other knowledges, one more advisor.
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->update(['level' => 4]);
        $this->insertAdvisor(3);

        $second = $this->makeRun(['rng_seed' => 777, 'current_tick' => 55]);
        $this->service->drawObjectives($second);
        $keysSecond = RunObjective::where('run_id', $second->id)->orderBy('id')->pluck('task_key')->all();

        $this->assertSame($keysFirst, $keysSecond);
        $this->assertSame($this->service->selectObjectives(777, app(PathBuildingGateService::class)->opening(self::COLONY_ID)), $keysSecond);
    }

    public function test_stored_order_is_the_seed_rank_not_the_pick_order(): void
    {
        $firstIsLong = false;
        $catalog = app(RunTaskCatalog::class);

        foreach (range(1, 60) as $seed) {
            $run = $this->makeRun(['rng_seed' => $seed]);
            $this->service->drawObjectives($run);
            $stored = RunObjective::where('run_id', $run->id)->orderBy('id')->pluck('task_key')->all();

            $ranked = $stored;
            usort($ranked, fn (string $a, string $b): int => crc32($seed.'|'.$a) <=> crc32($seed.'|'.$b));
            $this->assertSame($ranked, $stored, "seed {$seed}: objectives must be stored in rank order");

            $firstIsLong = $firstIsLong || $catalog->horizon($stored[0]) === 'long';
            $run->delete();
        }

        $this->assertTrue($firstIsLong, 'the visible first objective must not always be medium');
    }

    public function test_pool_with_only_medium_tasks_still_draws_three(): void
    {
        config(['game.run.task_pool' => ['task_research_lead', 'task_expedition_coverage', 'task_trade_volume', 'task_credit_reserve']]);

        foreach (range(1, 20) as $seed) {
            $keys = $this->service->selectObjectives($seed);
            $this->assertCount(3, $keys, "seed {$seed}");
            $this->assertFalse(
                in_array('task_trade_volume', $keys, true) && in_array('task_credit_reserve', $keys, true),
                'combo rule still applies'
            );
        }
    }

    public function test_pool_with_only_long_tasks_still_draws_three(): void
    {
        config(['game.run.task_pool' => ['task_senior_advisors', 'task_engineering_output', 'task_self_sufficiency', 'task_colony_prosperity']]);

        foreach (range(1, 20) as $seed) {
            $this->assertCount(3, $this->service->selectObjectives($seed), "seed {$seed}");
        }
    }

    public function test_task_without_horizon_never_fills_a_minimum_but_can_be_drawn_third(): void
    {
        config([
            'game.run.task_pool' => ['task_trade_volume', 'task_research_lead', 'task_senior_advisors', 'task_engineering_output'],
            'game.run.tasks.task_trade_volume.horizon' => null,
        ]);

        $drawnWithoutHorizon = false;
        foreach (range(1, 200) as $seed) {
            $keys = $this->service->selectObjectives($seed);
            $this->assertCount(3, $keys);
            $this->assertContains('task_research_lead', $keys, "seed {$seed}: the only medium task must always be drawn");
            $drawnWithoutHorizon = $drawnWithoutHorizon || in_array('task_trade_volume', $keys, true);
        }

        $this->assertTrue($drawnWithoutHorizon, 'a task without horizon may still be drawn as the third objective');
    }

    public function test_pool_with_fewer_than_three_tasks_draws_what_is_there(): void
    {
        config(['game.run.task_pool' => ['task_research_lead', 'task_senior_advisors']]);

        $keys = $this->service->selectObjectives(5);

        sort($keys);
        $this->assertSame(['task_research_lead', 'task_senior_advisors'], $keys);
    }

    public function test_combo_fallback_never_yields_two_economy_tasks(): void
    {
        config(['game.run.task_pool' => ['task_trade_volume', 'task_credit_reserve', 'task_research_lead']]);

        foreach (range(1, 20) as $seed) {
            $keys = $this->service->selectObjectives($seed);
            $this->assertCount(2, $keys, "seed {$seed}: only two tasks can be drawn without two economy tasks");
        }
    }

    // ── Forschungsvorsprung (§3.1) ───────────────────────────────────────────

    public function test_research_lead_counts_the_sum_of_knowledge_levels(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_research_lead', 16);
        $this->setKnowledgeLevels([90 => 3, 91 => 2, 92 => 1]);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(6, $objective->current_value, 'levels 3 + 2 + 1, non-knowledge researches excluded');
        $this->assertNull($objective->completed_at);
    }

    public function test_research_lead_completes_on_the_sol_the_sum_reaches_the_target(): void
    {
        $run = $this->makeRun(['current_tick' => 47]);
        $objective = $this->makeObjective($run, 'task_research_lead', 6);
        $this->setKnowledgeLevels([90 => 2, 91 => 2, 92 => 1]);

        $this->service->updateObjectiveProgress($run);
        $this->assertNull($objective->refresh()->completed_at);

        // Silent research ("stille Forschung") levels construction 2 -> 3.
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->where('research_id', 90)->update(['ap_spend' => 0]);
        $cost = app(ResearchService::class)->knowledgeLevelupCost(self::COLONY_ID, 90);
        $result = app(ResearchService::class)->investBonus(self::COLONY_ID, 90, $cost);
        $this->assertSame(3, $result->levelAfter, 'precondition: silent research levelled up');

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(6, $objective->current_value);
        $this->assertSame(47, $objective->completed_at);
    }

    public function test_research_lead_label_shows_the_target_without_min_level(): void
    {
        $label = app(RunTaskCatalog::class)->label('task_research_lead', 16);

        $this->assertStringContainsString('16', $label);
        $this->assertDoesNotMatchRegularExpression('/:[a-z_]+/', $label);
    }

    // ── Expertenstab (§3.2) ──────────────────────────────────────────────────

    public function test_senior_advisors_counts_the_sum_of_ranks(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_senior_advisors', 11);
        foreach ([3, 3, 2, 1] as $rank) {
            $this->insertAdvisor($rank);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(9, $objective->current_value);
        $this->assertNull($objective->completed_at);
    }

    public function test_senior_advisors_completes_at_the_target_sum(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_senior_advisors', 11);
        foreach ([3, 3, 3, 2] as $rank) {
            $this->insertAdvisor($rank);
        }

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(11, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_temporarily_withdrawn_advisor_still_counts(): void
    {
        $run = $this->makeRun(['current_tick' => 40]);
        $objective = $this->makeObjective($run, 'task_senior_advisors', 11);
        $this->insertAdvisor(3);
        $this->insertAdvisor(2, unavailableUntil: 50);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(5, $objective->refresh()->current_value);
    }

    public function test_dismissed_advisor_no_longer_counts_but_a_completed_objective_stays(): void
    {
        $run = $this->makeRun();
        $open = $this->makeObjective($run, 'task_senior_advisors', 11);
        $this->insertAdvisor(3);
        $fired = $this->insertAdvisor(3);

        app(AdvisorService::class)->fire($fired->id);
        $this->service->updateObjectiveProgress($run);
        $this->assertSame(3, $open->refresh()->current_value);

        $open->update(['completed_at' => 30, 'current_value' => 11]);
        $this->service->updateObjectiveProgress($run);
        $open->refresh();
        $this->assertSame(30, $open->completed_at, 'a completed objective stays completed');
    }

    public function test_senior_advisors_label_has_no_rank_parameter(): void
    {
        $label = app(RunTaskCatalog::class)->label('task_senior_advisors', 11);

        $this->assertStringContainsString('11', $label);
        $this->assertDoesNotMatchRegularExpression('/:[a-z_]+/', $label);
    }

    // ── Selbstversorgung (§3.3) ──────────────────────────────────────────────

    public function test_self_sufficiency_extends_when_harvest_covers_a_large_enough_need(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 4);
        $this->fakeOrganics(harvest: 60, need: 55);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(5, $objective->refresh()->streak_value);
    }

    public function test_self_sufficiency_resets_when_harvest_falls_short_even_with_a_big_stock(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 4);
        $this->setColonyResource(5, 5000);
        $this->fakeOrganics(harvest: 59, need: 60);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(0, $objective->refresh()->streak_value);
    }

    public function test_self_sufficiency_extends_with_an_empty_stock(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 2);
        $this->setColonyResource(5, 0);
        $this->setColonyResource(3, 0);
        $this->fakeOrganics(harvest: 70, need: 70);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(3, $objective->refresh()->streak_value);
    }

    public function test_self_sufficiency_resets_below_the_minimum_colony_size(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 4);
        $this->fakeOrganics(harvest: 70, need: 54);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(0, $objective->refresh()->streak_value);
    }

    public function test_min_fed_comes_from_config(): void
    {
        config(['game.run.tasks.task_self_sufficiency.min_fed' => 10]);
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35);
        $this->fakeOrganics(harvest: 12, need: 10);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(1, $objective->refresh()->streak_value);
    }

    public function test_self_sufficiency_resets_on_a_hunger_sol(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 4);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['hunger_streak' => 1]);
        $this->fakeOrganics(harvest: 70, need: 60);

        $this->service->updateObjectiveProgress($run);

        $this->assertSame(0, $objective->refresh()->streak_value);
    }

    public function test_self_sufficiency_completes_and_keeps_its_best_streak(): void
    {
        $run = $this->makeRun();
        $objective = $this->makeObjective($run, 'task_self_sufficiency', 35, streak: 34);
        $this->fakeOrganics(harvest: 70, need: 60);

        $this->service->updateObjectiveProgress($run);

        $objective->refresh();
        $this->assertSame(35, $objective->best_streak_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_own_harvest_counts_only_agrardom_and_agronomy_and_need_matches_the_sol_report(): void
    {
        $flow = app(ResourceFlowService::class);
        $perSol = $flow->perSol(self::COLONY_ID);

        $balance = $flow->organicsBalance(self::COLONY_ID);

        $this->assertSame(
            $perSol['organika']['sources']['agrardom'] + $perSol['organika']['sources']['agronomy'],
            $balance['harvest'],
            'harvest = the sources the resource bar shows, nothing from trade, missions or events'
        );
        $this->assertSame(app(ResourcesService::class)->foodNeed(self::COLONY_ID), $balance['need'], 'need = the Sol report food line');
    }

    public function test_mission_provisions_do_not_raise_the_need(): void
    {
        $flow = app(ResourceFlowService::class);
        $before = $flow->organicsBalance(self::COLONY_ID)['need'];

        DB::table('colony_hangar_missions')->insert([
            'colony_id' => self::COLONY_ID,
            'bay' => 1,
            'ship_id' => 85,
            'destination' => 'mission_courier_run',
            'sol_distance' => 3,
            'difficulty' => 'normal',
            'dispatch_tick' => 10,
            'recall_tick' => null,
            'state' => 'active',
            'succeeded' => null,
            'created_at' => now(),
        ]);

        $this->assertSame($before, $flow->organicsBalance(self::COLONY_ID)['need']);
    }

    public function test_self_sufficiency_label_names_min_fed_and_the_streak(): void
    {
        $label = app(RunTaskCatalog::class)->label('task_self_sufficiency', 35);

        $this->assertStringContainsString('55', $label);
        $this->assertStringContainsString('35', $label);
        $this->assertDoesNotMatchRegularExpression('/:[a-z_]+/', $label);
    }

    public function test_german_labels_name_the_new_measurements(): void
    {
        app()->setLocale('de');
        $catalog = app(RunTaskCatalog::class);

        $this->assertSame('Forschungsvorsprung: Stufen aller Kenntnisse zusammen mindestens 16', $catalog->label('task_research_lead', 16));
        $this->assertSame('Expertenstab: Ränge aller Berater zusammen mindestens 11', $catalog->label('task_senior_advisors', 11));
        $this->assertSame('Selbstversorgung: mindestens 55 Kolonisten aus eigener Ernte satt, 35 Sole in Folge', $catalog->label('task_self_sufficiency', 35));
    }

    // ── Nexus ladder with the new counters ───────────────────────────────────

    public function test_ladder_counts_research_lead_and_senior_advisors_by_percentage(): void
    {
        $run = $this->phase2Run(30);
        $research = $this->makeObjective($run, 'task_research_lead', 16);
        $senior = $this->makeObjective($run, 'task_senior_advisors', 12);
        $this->setKnowledgeLevels([90 => 3, 91 => 3, 92 => 2]);   // 8 / 16 = 50 %
        foreach ([2, 1, 1] as $rank) {
            $this->insertAdvisor($rank);                         // 4 / 12 = 33 %
        }

        $this->service->updateObjectiveProgress($run);
        $this->service->checkNexusInterventions($run);
        $this->assertSame(8, $research->refresh()->current_value);
        $this->assertFalse($this->eventFired('run.nexus_warning_sol30'), 'research lead at 50 % passes Sol 30');

        // Sol 50: one objective >= 75 %, a second >= 50 %.
        $run->update(['current_tick' => $run->phase2_start_tick + 50]);
        $this->setKnowledgeLevels([90 => 4, 91 => 4, 92 => 4]);   // 12 / 16 = 75 %
        $this->insertAdvisor(2);                                 // 6 / 12 = 50 %

        $this->service->updateObjectiveProgress($run);
        $this->service->checkNexusInterventions($run);

        $this->assertSame(6, $senior->refresh()->current_value);
        $this->assertFalse($this->eventFired('run.nexus_warning_sol50'), '75 % + 50 % passes Sol 50');
    }

    public function test_ladder_warns_when_the_new_counters_fall_short(): void
    {
        $run = $this->phase2Run(30);
        $this->makeObjective($run, 'task_research_lead', 16);
        $this->makeObjective($run, 'task_senior_advisors', 12);
        $this->setKnowledgeLevels([90 => 3, 91 => 2, 92 => 2]);   // 7 / 16 < 50 %
        foreach ([2, 2, 1] as $rank) {
            $this->insertAdvisor($rank);                         // 5 / 12 < 50 %
        }

        $this->service->updateObjectiveProgress($run);
        $this->service->checkNexusInterventions($run);

        $this->assertTrue($this->eventFired('run.nexus_warning_sol30'));
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

    private function phase2Run(int $phase2Sol): Run
    {
        return $this->makeRun([
            'phase2_start_tick' => 0,
            'current_tick' => $phase2Sol,
            'settings' => ['tick_limit' => 100],
        ]);
    }

    private function makeObjective(Run $run, string $taskKey, int $target, int $streak = 0): RunObjective
    {
        return RunObjective::create([
            'run_id' => $run->id,
            'task_key' => $taskKey,
            'target_value' => $target,
            'current_value' => $streak,
            'streak_value' => $streak,
            'best_streak_value' => $streak,
            'completed_at' => null,
        ]);
    }

    private function insertAdvisor(int $rank, ?int $unavailableUntil = null): Advisor
    {
        return Advisor::create([
            'user_id' => self::USER_ID,
            'personell_id' => $this->personellPool[$this->personellCursor++ % count($this->personellPool)],
            'colony_id' => self::COLONY_ID,
            'rank' => $rank,
            'active_ticks' => 60,
            'unavailable_until_tick' => $unavailableUntil,
        ]);
    }

    /** @param array<int, int> $levels knowledge id => level; every other knowledge drops to 0 */
    private function setKnowledgeLevels(array $levels): void
    {
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)
            ->whereBetween('research_id', [90, 96])->update(['level' => 0]);
        foreach ($levels as $id => $level) {
            DB::table('colony_researches')->updateOrInsert(
                ['colony_id' => self::COLONY_ID, 'research_id' => $id],
                ['level' => $level, 'status_points' => 20],
            );
        }
    }

    private function fakeOrganics(int $harvest, int $need): void
    {
        $this->mock(ResourceFlowService::class, function (MockInterface $mock) use ($harvest, $need): void {
            $mock->shouldReceive('organicsBalance')->with(self::COLONY_ID)->andReturn(['harvest' => $harvest, 'need' => $need]);
        });
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function eventFired(string $event): bool
    {
        return DB::table('colony_log')->where('user', self::USER_ID)->where('event', $event)->exists();
    }
}
