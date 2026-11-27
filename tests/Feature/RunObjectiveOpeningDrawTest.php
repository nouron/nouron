<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\RunObjective;
use App\Services\PathBuildingGateService;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Spec 2026-11-27 §2: every draw holds exactly one objective of the colony's
 * opening (its first path building) and two general ones.
 */
class RunObjectiveOpeningDrawTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const OPENING_TASKS = [
        'sciencelab' => 'task_research_lead',
        'hangar' => 'task_expedition_coverage',
        'bar' => 'task_trade_volume',
    ];

    private RunProgressService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(RunProgressService::class);
    }

    public function test_every_draw_holds_exactly_the_one_objective_of_its_opening(): void
    {
        $catalog = app(RunTaskCatalog::class);

        foreach (self::OPENING_TASKS as $opening => $own) {
            foreach (range(1, 1000) as $seed) {
                $keys = $this->service->selectObjectives($seed, $opening);
                $label = "{$opening} seed {$seed}: ".implode(',', $keys);

                $this->assertCount(3, array_unique($keys), $label);
                $this->assertSame([$own], array_values(array_intersect($keys, self::OPENING_TASKS)), $label);
                $horizons = array_map(fn (string $k): ?string => $catalog->horizon($k), $keys);
                $this->assertContains('medium', $horizons, $label);
                $this->assertContains('long', $horizons, $label);
                $this->assertLessThanOrEqual(1, count(array_filter($keys, fn (string $k): bool => $catalog->category($k) === 'economy')), $label);
            }
        }
    }

    public function test_an_opening_with_two_objectives_draws_one_of_them_by_seed(): void
    {
        config(['game.run.tasks.task_self_sufficiency.opening' => 'hangar']);

        $drawn = [];
        foreach (range(1, 100) as $seed) {
            $own = array_values(array_intersect(
                $this->service->selectObjectives($seed, 'hangar'),
                ['task_expedition_coverage', 'task_self_sufficiency']
            ));
            $this->assertCount(1, $own, "seed {$seed}");
            $drawn[$own[0]] = true;
        }

        $this->assertCount(2, $drawn);
    }

    public function test_the_first_placed_path_building_decides_the_opening(): void
    {
        $this->standing([44 => [3, 1], 31 => [9, 2], 52 => [5, 1]]);

        $this->assertSame('hangar', app(PathBuildingGateService::class)->opening(self::COLONY_ID));
    }

    public function test_an_unfinished_path_building_does_not_count(): void
    {
        $this->standing([44 => [3, 0], 52 => [5, 1]]);

        $this->assertSame('bar', app(PathBuildingGateService::class)->opening(self::COLONY_ID));
    }

    public function test_a_tie_goes_to_the_lower_building_id_and_no_path_building_means_no_opening(): void
    {
        $this->standing([52 => [4, 1], 31 => [4, 1]]);
        $this->assertSame('sciencelab', app(PathBuildingGateService::class)->opening(self::COLONY_ID));

        $this->standing([]);
        $this->assertNull(app(PathBuildingGateService::class)->opening(self::COLONY_ID));
    }

    public function test_the_drawn_objectives_follow_the_colony_opening(): void
    {
        $this->standing([52 => [4, 1]]);
        DB::table('runs')->where('colony_id', self::COLONY_ID)->update(['status' => 'failed']);

        foreach (range(1, 20) as $seed) {
            $run = Run::create([
                'user_id' => 3, 'colony_id' => self::COLONY_ID, 'current_tick' => 12, 'status' => 'active',
                'phase' => 2, 'phase2_start_tick' => 12, 'started_at' => now(), 'rng_seed' => $seed,
            ]);
            $this->service->drawObjectives($run);
            $keys = RunObjective::where('run_id', $run->id)->pluck('task_key')->all();

            $this->assertContains('task_trade_volume', $keys, "seed {$seed}");
            $this->assertEmpty(array_intersect($keys, ['task_research_lead', 'task_expedition_coverage']), "seed {$seed}");
            $run->update(['status' => 'failed']);
        }
    }

    /** @param array<int, array{int, int}> $buildings building id => [placed_at_tick, level] */
    private function standing(array $buildings): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->whereIn('building_id', PathBuildingGateService::PATH_BUILDING_IDS)->delete();

        foreach ($buildings as $id => [$tick, $level]) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID, 'building_id' => $id, 'instance_id' => 1, 'level' => $level,
                'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $id, 'tile_y' => 0, 'placed_at_tick' => $tick,
            ]);
        }
    }
}
