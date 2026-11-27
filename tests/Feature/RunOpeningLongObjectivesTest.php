<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\RunObjective;
use App\Services\PathDepthService;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Long-horizon opening objectives (spec 2026-11-27 §3 and decision 9):
 * research breadth (science lab), mission variety (hangar), cantina circle (cantina).
 */
class RunOpeningLongObjectivesTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_each_opening_has_one_medium_and_one_long_objective(): void
    {
        $catalog = app(RunTaskCatalog::class);
        $byOpening = [];
        foreach ($catalog->pool() as $key) {
            if ($catalog->opening($key) !== null) {
                $byOpening[$catalog->opening($key)][$catalog->horizon($key)] = $key;
            }
        }

        $this->assertEquals([
            'sciencelab' => ['medium' => 'task_research_lead', 'long' => 'task_research_breadth'],
            'hangar' => ['medium' => 'task_expedition_coverage', 'long' => 'task_mission_variety'],
            'bar' => ['medium' => 'task_trade_volume', 'long' => 'task_cantina_circle'],
        ], $byOpening);
    }

    public function test_research_breadth_ignores_geology_and_agronomy(): void
    {
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->delete();
        foreach ([90 => 3, 91 => 2, 92 => 5, 93 => 4, 95 => 1] as $id => $level) {
            DB::table('colony_researches')->insert([
                'colony_id' => self::COLONY_ID, 'research_id' => $id, 'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            ]);
        }

        $this->assertSame(6, $this->progress('task_research_breadth', 11)->current_value);
    }

    public function test_mission_variety_counts_each_successful_mission_type_once(): void
    {
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        foreach ([
            ['mission_courier_run', 'completed', 1], ['mission_courier_run', 'completed', 1],
            ['mission_recon_flight', 'completed', 1], ['mission_supply_run', 'completed', 0],
            ['mission_aid_transport', 'active', null],
        ] as [$destination, $state, $succeeded]) {
            DB::table('colony_hangar_missions')->insert([
                'colony_id' => self::COLONY_ID, 'bay' => 0, 'ship_id' => 85, 'destination' => $destination,
                'sol_distance' => 1, 'dispatch_tick' => 1, 'state' => $state, 'succeeded' => $succeeded,
            ]);
        }

        $this->assertSame(2, $this->progress('task_mission_variety', 9)->current_value);
    }

    public function test_cantina_circle_sums_the_five_closest_figures_capped_at_four(): void
    {
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        foreach ([5, 5, 3, 2, 2, 2] as $i => $level) {
            DB::table('colony_path_levels')->insert([
                'colony_id' => self::COLONY_ID, 'kind' => PathDepthService::KIND_CHARACTER, 'entity_key' => "figure{$i}", 'level' => $level,
            ]);
        }
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => PathDepthService::KIND_FAMILY, 'entity_key' => 'supply', 'level' => 5,
        ]);

        $objective = $this->progress('task_cantina_circle', 15);

        $this->assertSame(4 + 4 + 3 + 2 + 2, $objective->current_value);
        $this->assertNotNull($objective->completed_at);
    }

    public function test_labels_show_the_configured_numbers(): void
    {
        $catalog = app(RunTaskCatalog::class);
        app()->setLocale('de');

        $this->assertStringContainsString('11', $catalog->label('task_research_breadth'));
        $this->assertStringContainsString('9', $catalog->label('task_mission_variety'));
        $label = $catalog->label('task_cantina_circle');
        foreach (['20', '5', '4'] as $number) {
            $this->assertStringContainsString($number, $label);
        }
        foreach (['task_research_breadth', 'task_mission_variety', 'task_cantina_circle'] as $key) {
            foreach (['de', 'en'] as $locale) {
                app()->setLocale($locale);
                $this->assertDoesNotMatchRegularExpression('/run\.task_|:[a-z_]+/', $catalog->label($key), "{$locale} {$key}");
            }
        }
    }

    private function progress(string $taskKey, int $target): RunObjective
    {
        DB::table('runs')->where('colony_id', self::COLONY_ID)->update(['status' => 'failed']);
        $run = Run::create([
            'user_id' => 3, 'colony_id' => self::COLONY_ID, 'current_tick' => 40, 'status' => 'active',
            'phase' => 2, 'phase2_start_tick' => 20, 'started_at' => now()->subHour(),
        ]);
        $id = DB::table('run_objectives')->insertGetId([
            'run_id' => $run->id, 'task_key' => $taskKey, 'target_value' => $target, 'current_value' => 0,
        ]);

        app(RunProgressService::class)->updateObjectiveProgress($run);

        return RunObjective::findOrFail($id);
    }
}
