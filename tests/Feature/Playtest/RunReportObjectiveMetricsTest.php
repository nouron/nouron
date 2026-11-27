<?php

namespace Tests\Feature\Playtest;

use App\Services\PathDepthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Spec 2026-11-27 §6 step 0: per Sol the value of every opening objective and of
 * the maintenance objective, whether or not it was drawn.
 */
class RunReportObjectiveMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_sums_with_and_without_the_base_knowledges(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        DB::table('colony_researches')->where('colony_id', $bot->colonyId)->delete();
        foreach (['construction' => 3, 'geology' => 5, 'agronomy' => 4, 'trade' => 2] as $key => $level) {
            DB::table('colony_researches')->insert([
                'colony_id' => $bot->colonyId, 'research_id' => config("knowledge.{$key}.id"),
                'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            ]);
        }

        $metrics = $this->metrics($bot);

        $this->assertSame(14, $metrics['research_lead']);
        $this->assertSame(5, $metrics['research_breadth']);
    }

    public function test_expeditions_and_merchant_purchases_are_counted_like_the_objectives(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        foreach ([['normal', 1, 'completed'], ['hard', 1, 'completed'], ['easy', 1, 'completed'], ['normal', 0, 'completed'], ['normal', null, 'active']] as [$difficulty, $succeeded, $state]) {
            DB::table('colony_hangar_missions')->insert([
                'colony_id' => $bot->colonyId, 'bay' => 0, 'ship_id' => 85, 'destination' => 'mission_recon_flight',
                'sol_distance' => 1, 'dispatch_tick' => 1, 'state' => $state, 'difficulty' => $difficulty, 'succeeded' => $succeeded,
            ]);
        }
        $visit = DB::table('merchant_visits')->insertGetId([
            'colony_id' => $bot->colonyId, 'tick_start' => 1, 'tick_end' => 3, 'created_at' => now()->addMinute(),
        ]);
        foreach ([1, 1, 0] as $sold) {
            DB::table('merchant_items')->insert([
                'visit_id' => $visit, 'item_type' => 'compounds', 'label' => 'x', 'cost_credits' => 1, 'sold' => $sold,
            ]);
        }

        $metrics = $this->metrics($bot);

        $this->assertSame(2, $metrics['expeditions']);
        $this->assertSame(2, $metrics['trade']);
    }

    public function test_crew_experience_and_the_five_closest_figures_capped_at_four(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        DB::table('colony_path_levels')->where('colony_id', $bot->colonyId)->delete();
        $rows = [[PathDepthService::KIND_FAMILY, 'a', 3], [PathDepthService::KIND_FAMILY, 'b', 2]];
        foreach ([5, 4, 3, 2, 1, 1] as $i => $level) {
            $rows[] = [PathDepthService::KIND_CHARACTER, "figure{$i}", $level];
        }
        foreach ($rows as [$kind, $key, $level]) {
            DB::table('colony_path_levels')->insert([
                'colony_id' => $bot->colonyId, 'kind' => $kind, 'entity_key' => $key, 'level' => $level,
            ]);
        }

        $metrics = $this->metrics($bot);

        $this->assertSame(5, $metrics['hangar_experience']);
        $this->assertSame(4 + 4 + 3 + 2 + 1, $metrics['cantina_circle']);
    }

    public function test_maintenance_holds_only_while_every_built_building_is_at_seventy_percent(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $built = DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('level', '>=', 1);
        $built->update(['status_points' => 20]);

        $this->assertTrue($this->metrics($bot)['maintenance_ok']);

        (clone $built)->limit(1)->update(['status_points' => 13]);

        $this->assertFalse($this->metrics($bot)['maintenance_ok']);
    }

    /** @return array<string, mixed> */
    private function metrics(BotSession $bot): array
    {
        $report = new RunReport(seed: 1);
        $report->snapshot($bot);

        return $report->build($bot)['sols'][0]['objective_metrics'] ?? $this->fail('objective_metrics missing');
    }
}
