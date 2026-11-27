<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Valuers;
use Tests\TestCase;

/**
 * Measurement 20261127_m8: the hangar bot filled every bay with freighters and only
 * researched geology and agronomy, so no hard mission and no ninth mission kind ever
 * happened. With a hangar objective open, a focused profile saves a bay and the Credits
 * for the corvette and values the knowledges that open missions.
 */
class BotStrategyHangarObjectivesTest extends TestCase
{
    use RefreshDatabase;

    private const DRONE = 85;

    private const FREIGHTER = 47;

    private const CORVETTE = 37;

    private const CONSTRUCTION = 90;

    private const CARTOGRAPHY = 91;

    private const GEOLOGY = 92;

    private const TRADE = 95;

    private const DEFENSE = 96;

    public function test_buys_the_corvette_for_an_open_hangar_objective(): void
    {
        foreach (['task_expedition_coverage', 'task_mission_variety'] as $task) {
            $bot = $this->bootHangar(level: 3, ships: [self::FREIGHTER => 1], credits: 5000);
            $this->drawObjective($bot, $task);

            $this->assertSame(self::CORVETTE, $this->shipWanted($bot, 'focus'), $task);
        }
    }

    public function test_saves_for_the_corvette_instead_of_filling_the_last_bay(): void
    {
        $bot = $this->bootHangar(level: 3, ships: [self::FREIGHTER => 1], credits: 600);
        $this->drawObjective($bot, 'task_mission_variety');

        $this->assertNull($this->shipWanted($bot, 'focus'));
    }

    public function test_still_buys_the_first_freighter_while_saving(): void
    {
        $bot = $this->bootHangar(level: 3, ships: [self::DRONE => 1], credits: 600);
        $this->drawObjective($bot, 'task_mission_variety');

        $this->assertSame(self::FREIGHTER, $this->shipWanted($bot, 'focus'));
    }

    public function test_keeps_the_old_purchases_without_a_reason_for_the_corvette(): void
    {
        $noObjective = $this->bootHangar(level: 3, ships: [self::FREIGHTER => 1], credits: 5000);
        $this->assertSame(self::FREIGHTER, $this->shipWanted($noObjective, 'focus'), 'no hangar objective');

        $this->drawObjective($noObjective, 'task_mission_variety');
        $this->assertSame(self::FREIGHTER, $this->shipWanted($noObjective, 'aimless'), 'aimless ignores objectives');

        DB::table('colony_buildings')->where('colony_id', $noObjective->colonyId)
            ->where('building_id', BuildingId::Hangar->value)->update(['level' => 2]);
        $this->assertSame(self::FREIGHTER, $this->shipWanted($noObjective, 'focus'), 'hall too small for a corvette');

        $owned = $this->bootHangar(level: 3, ships: [self::CORVETTE => 1], credits: 5000);
        $this->drawObjective($owned, 'task_mission_variety');
        $this->assertSame(self::FREIGHTER, $this->shipWanted($owned, 'focus'), 'one corvette is enough');
    }

    public function test_variety_values_the_knowledges_that_open_a_mission(): void
    {
        $bot = $this->bootHangar(level: 3, ships: [], credits: 0);
        $this->drawObjective($bot, 'task_mission_variety');
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);
        $share = (float) $ctx->weights['w_objective'] / (int) config('game.run.tasks.task_mission_variety.target');

        foreach ([self::CONSTRUCTION, self::CARTOGRAPHY, self::TRADE, self::DEFENSE] as $id) {
            $this->assertEqualsWithDelta($share, Valuers::research($bot, $ctx, $id, 0, 0)->terms['objective'] ?? 0.0, 1e-9, (string) $id);
        }
        $this->assertArrayHasKey('objective', Valuers::research($bot, $ctx, self::CARTOGRAPHY, 2, 0)->terms, 'long range expedition needs level 3');
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $ctx, self::CONSTRUCTION, 1, 0)->terms, 'gate already passed');
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $ctx, self::GEOLOGY, 0, 0)->terms, 'opens no mission');

        $aimless = BotContext::capture($bot, BotProfile::named('aimless'), withGoal: true);
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $aimless, self::TRADE, 0, 0)->terms);
    }

    public function test_expeditions_value_only_gates_of_missions_with_a_counted_difficulty(): void
    {
        config(['missions.catalog.mission_trade_convoy.difficulties' => ['normal']]);
        $bot = $this->bootHangar(level: 3, ships: [], credits: 0);
        $this->drawObjective($bot, 'task_expedition_coverage');
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);

        $this->assertArrayHasKey('objective', Valuers::research($bot, $ctx, self::CONSTRUCTION, 0, 0)->terms);
        $this->assertArrayNotHasKey('objective', Valuers::research($bot, $ctx, self::TRADE, 0, 0)->terms);
    }

    public function test_buys_no_drone_without_a_free_bay(): void
    {
        $full = $this->bootHangar(level: 2, ships: [self::FREIGHTER => 1, self::CORVETTE => 2], credits: 5000);
        foreach (['aimless', 'focus'] as $profile) {
            $this->assertNull($this->shipWanted($full, $profile), $profile);
        }

        $room = $this->bootHangar(level: 2, ships: [self::FREIGHTER => 1], credits: 400);
        $this->assertSame(self::DRONE, $this->shipWanted($room, 'aimless'), 'a free bay and too few Credits for a freighter');
    }

    private function shipWanted(BotSession $bot, string $profile): ?int
    {
        $rules = array_column(BotStrategy::default(BotProfile::named($profile)), null, 'name');
        $wanted = $rules['request_ship']['when']($bot);

        return $wanted === null ? null : (int) $wanted;
    }

    /** @param array<int, int> $ships ship_id => bay */
    private function bootHangar(int $level, array $ships, int $credits): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 1]
        );
        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        foreach ($ships as $shipId => $bay) {
            DB::table('colony_ships')->insert([
                'colony_id' => $bot->colonyId, 'ship_id' => $shipId, 'level' => 1, 'status_points' => 20,
                'ap_spend' => 0, 'bay' => $bay, 'ship_state' => 'docked',
            ]);
        }
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        foreach (array_slice(BotStrategy::HIRE_ORDER, 0, 3) as $personellId) {
            DB::table('advisors')->insert([
                'user_id' => $bot->userId, 'colony_id' => $bot->colonyId, 'personell_id' => $personellId, 'rank' => 1,
            ]);
        }
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits]);

        return $bot;
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
}
