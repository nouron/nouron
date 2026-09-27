<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A45: the `focus` bot profile plays towards the drawn objectives, so the
 * calibration rule (GDD §15: targeted play reaches an objective around Sol
 * 70–85) can be measured. The `default` profile stays the generalist.
 *
 * Focus rules only fire while the matching objective is drawn and still open:
 *   focus_expedition_mission  — task_expedition_coverage: missions at a counted difficulty
 *   focus_engineering_levelup — task_engineering_output: level up past Lv2
 *   focus_trust_building      — task_colony_prosperity: place missing trust buildings
 * Credit reserve rides on savingsAggressiveness (thrifty guard); promotions
 * (task_senior_advisors) are already a high-priority default rule.
 */
class BotStrategyFocusProfileTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_DRONE = 85;

    private const MONUMENT = 50;

    private const FOCUS_RULES = ['focus_expedition_mission', 'focus_engineering_levelup', 'focus_trust_building'];

    // ── Profile ──────────────────────────────────────────────────────────────

    public function test_focus_profile_turns_on_objective_focus_and_thrift(): void
    {
        $profile = BotProfile::named('focus');

        $this->assertSame('focus', $profile->name);
        $this->assertSame(1.0, $profile->objectiveFocus);
        $this->assertSame(1.0, $profile->savingsAggressiveness);
    }

    public function test_default_profile_has_no_objective_focus(): void
    {
        $this->assertSame(0.0, BotProfile::named('default')->objectiveFocus);
        $names = array_column(BotStrategy::default(), 'name');

        foreach (self::FOCUS_RULES as $rule) {
            $this->assertNotContains($rule, $names, 'the default generalist must not get focus rules');
        }
    }

    public function test_focus_rules_sit_before_the_generic_building_work(): void
    {
        $names = array_column(BotStrategy::default(BotProfile::named('focus')), 'name');

        foreach (self::FOCUS_RULES as $rule) {
            $this->assertContains($rule, $names);
            $this->assertLessThan(array_search('place_building', $names, true), array_search($rule, $names, true));
            $this->assertLessThan(array_search('invest_production', $names, true), array_search($rule, $names, true));
        }
    }

    // ── Expedition ───────────────────────────────────────────────────────────

    public function test_expedition_focus_sends_a_mission_at_a_counted_difficulty(): void
    {
        $bot = $this->bootWithDockedDrone();
        $this->drawObjective($bot, 'task_expedition_coverage');

        $candidate = $this->rule('focus_expedition_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('normal', $candidate['difficulty']);
        $mission = config("missions.catalog.{$candidate['mission_key']}");
        $this->assertContains('drone', $mission['ships']);
        $this->assertArrayNotHasKey('target', $mission['requires'] ?? []);
    }

    public function test_expedition_focus_dispatch_succeeds_against_the_server(): void
    {
        $bot = $this->bootWithDockedDrone();
        $this->drawObjective($bot, 'task_expedition_coverage');
        $rule = $this->rule('focus_expedition_mission');

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertSame('normal', DB::table('colony_hangar_missions')->where('colony_id', $bot->colonyId)->value('difficulty'));
    }

    public function test_expedition_focus_is_idle_without_the_objective(): void
    {
        $bot = $this->bootWithDockedDrone();

        $this->assertEmpty($this->rule('focus_expedition_mission')['when']($bot));
    }

    // ── Engineering ──────────────────────────────────────────────────────────

    public function test_engineering_focus_levels_a_building_past_level_two(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->drawObjective($bot, 'task_engineering_output');
        $this->placeBuilding($bot, BuildingId::Housing->value, level: 2);
        $this->setResource($bot, 3, 500);

        $candidate = $this->rule('focus_engineering_levelup')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(BuildingId::Housing->value, (int) $candidate->building_id);
    }

    public function test_engineering_focus_skips_buildings_at_max_level(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->drawObjective($bot, 'task_engineering_output');
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->whereNotIn('building_id', [BuildingId::CommandCenter->value, BuildingId::Harvester->value])->delete();
        $this->placeBuilding($bot, BuildingId::Housing->value, level: (int) config('buildings.housingComplex.max_level'));
        $this->setResource($bot, 3, 500);

        $this->assertEmpty($this->rule('focus_engineering_levelup')['when']($bot));
    }

    public function test_engineering_focus_is_idle_without_the_objective(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeBuilding($bot, BuildingId::Housing->value, level: 2);
        $this->setResource($bot, 3, 500);

        $this->assertEmpty($this->rule('focus_engineering_levelup')['when']($bot));
    }

    // ── Prosperity ───────────────────────────────────────────────────────────

    public function test_prosperity_focus_places_a_missing_trust_building(): void
    {
        $bot = $this->bootRichColony();
        $this->drawObjective($bot, 'task_colony_prosperity');

        $candidate = $this->rule('focus_trust_building')['when']($bot);

        $this->assertNotEmpty($candidate);
        [$building, $tile] = $candidate;
        $this->assertContains((int) $building['building_id'], [46, 50, 32, 53]);
        $this->assertNotNull($tile);
    }

    public function test_prosperity_focus_skips_trust_buildings_already_placed(): void
    {
        $bot = $this->bootRichColony();
        $this->drawObjective($bot, 'task_colony_prosperity');
        $this->placeBuilding($bot, self::MONUMENT, level: 1);

        $candidate = $this->rule('focus_trust_building')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertNotSame(self::MONUMENT, (int) $candidate[0]['building_id']);
    }

    public function test_prosperity_focus_is_idle_without_the_objective(): void
    {
        $bot = $this->bootRichColony();

        $this->assertEmpty($this->rule('focus_trust_building')['when']($bot));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function drawObjective(BotSession $bot, string $taskKey): void
    {
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => $taskKey,
            'target_value' => (int) config("game.run.tasks.{$taskKey}.target"),
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);
    }

    private function bootWithDockedDrone(): BotSession
    {
        $bot = BotSession::boot($this, 1);

        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Hangar->value)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1,
            'level' => 2,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
        DB::table('colony_ships')->insert([
            'colony_id' => $bot->colonyId,
            'ship_id' => self::SHIP_DRONE,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'hangar_instance_id' => 1,
            'ship_state' => 'docked',
        ]);
        $this->setResource($bot, 5, 50);

        return $bot;
    }

    /** CC at max level, stock for any trust building, room for its workplaces. */
    private function bootRichColony(): BotSession
    {
        $bot = BotSession::boot($this, 1);

        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)->update(['level' => 5]);
        $this->placeBuilding($bot, BuildingId::Housing->value, level: 3);
        $this->setResource($bot, 3, 500);
        $this->setResource($bot, 4, 200);

        return $bot;
    }

    private function placeBuilding(BotSession $bot, int $buildingId, int $level): void
    {
        $tile = DB::table('colony_tiles as ct')
            ->where('ct.colony_id', $bot->colonyId)
            ->where('ct.is_colony_zone', 1)
            ->where(fn ($q) => $q->where('ct.q', '!=', 0)->orWhere('ct.r', '!=', 0))
            ->where('ct.tile_type', 'like', 'terrain_%')
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                ->where('cb.colony_id', $bot->colonyId)
                ->whereColumn('cb.tile_x', 'ct.q')->whereColumn('cb.tile_y', 'ct.r'))
            ->first();
        $this->assertNotNull($tile, 'fixture needs a free colony-zone tile');

        $instance = (int) DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', $buildingId)->max('instance_id') + 1;

        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => $buildingId,
            'instance_id' => $instance,
            'level' => $level,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => $tile->q,
            'tile_y' => $tile->r,
        ]);
    }

    private function setResource(BotSession $bot, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $name): array
    {
        foreach (BotStrategy::default(BotProfile::named('focus')) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
