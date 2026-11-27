<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Credit, compounds and expedition missions wear ships; without a repair rule a
 * worn ship stays docked for the rest of the run (batch 20261126_m6: a drone sat
 * idle on 62 of 67 docked Sols while the expedition objective was open).
 */
class BotStrategyShipRepairTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_DRONE = 85;

    public function test_repairs_a_docked_ship_that_misses_a_full_repair_click(): void
    {
        $bot = $this->bootWithDrone(2.0);
        $rule = $this->rule($bot);

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertSame(2.0 + (float) config('game.ship_repair.sp_per_click'), $this->shipStatus($bot));
    }

    public function test_leaves_a_ship_alone_that_would_waste_part_of_a_click(): void
    {
        $max = (float) config('game.ship_repair.max_status_points');
        $bot = $this->bootWithDrone($max - (float) config('game.ship_repair.sp_per_click') + 1);

        $this->assertEmpty($this->rule($bot)['when']($bot));
    }

    public function test_repairs_before_dispatching_any_mission(): void
    {
        $names = array_column(BotStrategy::default(BotProfile::named('aimless')), 'name');

        $this->assertContains('repair_ship_for_flight', $names);
        $this->assertLessThan(array_search('dispatch_credit_mission', $names, true), array_search('repair_ship_for_flight', $names, true));
    }

    private function rule(BotSession $bot): array
    {
        $rules = array_column(BotStrategy::default(BotProfile::named('aimless')), null, 'name');

        return $rules['repair_ship_for_flight'] ?? $this->fail('rule repair_ship_for_flight missing');
    }

    private function shipStatus(BotSession $bot): float
    {
        return (float) DB::table('colony_ships')->where('colony_id', $bot->colonyId)->value('status_points');
    }

    private function bootWithDrone(float $statusPoints): BotSession
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
            'status_points' => $statusPoints,
            'ap_spend' => 0,
            'bay' => 1,
            'ship_state' => 'docked',
        ]);

        return $bot;
    }
}
