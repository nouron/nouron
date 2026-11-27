<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\PathDepthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Valuers;
use Tests\TestCase;

/**
 * Spec 2026-11-27, step 3: the focus profile pursues the long-horizon opening
 * objectives (research breadth, mission variety, cantina circle). aimless ignores them.
 */
class BotStrategyOpeningObjectivesTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_CORVETTE = 37;

    private const NEW_RULES = ['focus_mission_variety', 'focus_cantina_circle'];

    public function test_aimless_gets_none_of_the_new_rules(): void
    {
        $names = array_column(BotStrategy::default(BotProfile::named('aimless')), 'name');

        foreach (self::NEW_RULES as $rule) {
            $this->assertNotContains($rule, $names);
        }
    }

    public function test_new_rules_sit_before_the_generic_work_they_compete_with(): void
    {
        $at = array_flip(array_column(BotStrategy::default(BotProfile::named('focus')), 'name'));

        $this->assertLessThan($at['dispatch_credit_mission'], $at['focus_mission_variety']);
        $this->assertLessThan($at['converse_character'], $at['focus_cantina_circle']);
        $this->assertLessThan($at['invest_production'], $at['focus_cantina_circle']);
    }

    public function test_research_breadth_values_every_counted_knowledge_only(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->drawObjective($bot, 'task_research_breadth');
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);
        $share = (float) $ctx->weights['w_objective'] / (int) config('game.run.tasks.task_research_breadth.target');

        $this->assertEqualsWithDelta($share, Valuers::research($bot, $ctx, 95, 0, 0)->terms['objective'] ?? 0.0, 1e-9, 'trade counts');
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $ctx, 92, 0, 0)->terms, 'geology is excluded');

        $aimless = BotContext::capture($bot, BotProfile::named('aimless'), withGoal: true);
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $aimless, 95, 0, 0)->terms);
    }

    public function test_mission_variety_flies_a_kind_not_yet_succeeded(): void
    {
        $bot = $this->bootWithCorvette();
        $this->drawObjective($bot, 'task_mission_variety');
        $rule = $this->rule('focus_mission_variety');
        $first = $rule['when']($bot);
        $this->assertNotEmpty($first);

        DB::table('colony_hangar_missions')->insert([
            'colony_id' => $bot->colonyId, 'bay' => 0, 'ship_id' => self::SHIP_CORVETTE, 'destination' => $first['mission_key'],
            'sol_distance' => 1, 'dispatch_tick' => 1, 'state' => 'completed', 'succeeded' => 1,
        ]);
        $second = $rule['when']($bot);

        $this->assertNotSame($first['mission_key'], $second['mission_key'] ?? null);
        if ($second !== null) {
            $res = $rule['do']($bot, $second);
            $this->assertTrue($res['ok'], json_encode($res['body']));
        }
    }

    public function test_mission_variety_is_idle_without_the_objective(): void
    {
        $this->assertEmpty($this->rule('focus_mission_variety')['when']($this->bootWithCorvette()));
    }

    public function test_cantina_circle_talks_longest_to_the_closest_figure_below_the_cap(): void
    {
        $bot = $this->bootWithCantina();
        $this->drawObjective($bot, 'task_cantina_circle');
        $rule = $this->rule('focus_cantina_circle');

        $candidate = $rule['when']($bot);

        $this->assertNotEmpty($candidate);
        $options = collect(app(PathDepthService::class)->conversation($bot->colonyId, $candidate['slug'])['options'])
            ->filter(fn (array $o): bool => $o['available'] && ! $o['capped'] && $o['wasted'] === 0);
        $this->assertSame((int) $options->max('ap'), $candidate['ap']);
        $res = $rule['do']($bot, $candidate);
        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    public function test_cantina_circle_leaves_figures_at_the_cap_alone(): void
    {
        $bot = $this->bootWithCantina();
        $this->drawObjective($bot, 'task_cantina_circle');
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'kind' => PathDepthService::KIND_CHARACTER, 'entity_key' => 'bartender'],
            ['level' => (int) config('game.run.tasks.task_cantina_circle.level'), 'ap_spend' => 0]
        );

        $candidate = $this->rule('focus_cantina_circle')['when']($bot);

        $this->assertNotSame('bartender', $candidate['slug'] ?? null);
    }

    private function rule(string $name): array
    {
        $rules = array_column(BotStrategy::default(BotProfile::named('focus')), null, 'name');

        return $rules[$name] ?? $this->fail("rule {$name} missing");
    }

    private function drawObjective(BotSession $bot, string $taskKey): void
    {
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId, 'task_key' => $taskKey,
            'target_value' => (int) config("game.run.tasks.{$taskKey}.target"), 'current_value' => 0,
        ]);
    }

    private function bootWithCorvette(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->place($bot, BuildingId::Hangar->value, 3);
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_ships')->insert([
            'colony_id' => $bot->colonyId, 'ship_id' => self::SHIP_CORVETTE, 'level' => 1, 'status_points' => 20,
            'ap_spend' => 0, 'bay' => 1, 'ship_state' => 'docked',
        ]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 5], ['amount' => 100]);

        return $bot;
    }

    private function bootWithCantina(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->place($bot, BuildingId::Bar->value, 3);

        return $bot;
    }

    private function place(BotSession $bot, int $buildingId, int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'building_id' => $buildingId, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $buildingId % 7, 'tile_y' => 1]
        );
    }
}
