<?php

namespace Tests\Feature\Resources;

use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Level-0 workplace reserve (GDD §6 "Supply als Bau-Gate", A14 Owner decision
 * 2026-09-24): a building that is placed on the map but still on level 0 already
 * occupies the workplaces of its first level (supply_cost × 1). The reserve is part
 * of the single workplaces number — free supply, the display breakdown, colonists
 * (homeless comparison, food) all see it. A level-0 row without a tile (seeded,
 * not placed) reserves nothing.
 *
 * Setup: every supply cost zeroed, then the Harvester (27, colony 1 level 1)
 * carries 20 supply → 20 workplaces; the Agrardom (41) costs 6. The cap is set
 * directly on user_resources.
 */
class SupplyReserveTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    private const AGRARDOM = 41;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 20]);
        DB::table('buildings')->where('id', self::AGRARDOM)->update(['supply_cost' => 6]);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 30]);
        config(['game.food.supply_per_eater' => 4, 'game.bypass.supply_checks' => false]);
    }

    private function service(): ResourcesService
    {
        return $this->app->make(ResourcesService::class);
    }

    private function agrardom(int $level, ?int $tileX): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $tileX, 'tile_y' => $tileX === null ? null : 0]
        );
    }

    public function test_a_placed_level_zero_building_reserves_its_first_level(): void
    {
        $this->agrardom(0, 2);

        $breakdown = $this->service()->getSupplyBreakdown(self::COLONY_ID);

        $this->assertSame(4, $breakdown['free'], '30 − (20 + 6 reserved)');
        $this->assertSame(26, $breakdown['used']['buildings'], 'the reserve is part of the building workplaces');
        $this->assertSame(6, $breakdown['used']['reserved'], 'reserved share shown separately for transparency');
        $this->assertSame(4, $this->service()->getFreeSupply(self::COLONY_ID));
    }

    public function test_a_level_zero_row_without_a_tile_reserves_nothing(): void
    {
        $this->agrardom(0, null);

        $breakdown = $this->service()->getSupplyBreakdown(self::COLONY_ID);

        $this->assertSame(10, $breakdown['free']);
        $this->assertSame(0, $breakdown['used']['reserved']);
    }

    public function test_completing_the_first_level_does_not_change_the_workplaces(): void
    {
        $this->agrardom(0, 2);
        $before = $this->service()->colonistStatus(self::COLONY_ID)['workplaces'];

        $this->agrardom(1, 2);

        $this->assertSame(26, $before);
        $this->assertSame($before, $this->service()->colonistStatus(self::COLONY_ID)['workplaces']);
        $this->assertSame(0, $this->service()->getSupplyBreakdown(self::COLONY_ID)['used']['reserved']);
    }

    public function test_reserved_colonists_are_present_need_housing_and_eat(): void
    {
        $this->agrardom(0, 2);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 24]);

        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(26, $status['workplaces']);
        $this->assertSame(26, $status['present']);
        $this->assertSame(2, $status['homeless'], 'reserve counts in the housing comparison');
        $this->assertSame(6, $this->service()->foodNeed(self::COLONY_ID), 'floor(26 present / 4)');
    }

    public function test_techtree_supply_check_skips_the_first_level_of_a_placed_building(): void
    {
        $this->agrardom(0, 2);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 20]); // free −6

        $buildings = $this->app->make(BuildingService::class);

        $this->assertTrue(
            $buildings->checkRequiredSupplyByEntityId(self::COLONY_ID, self::AGRARDOM),
            'the first level is already reserved — it must never fail on supply'
        );

        $this->agrardom(1, 2);
        $this->assertFalse(
            $buildings->checkRequiredSupplyByEntityId(self::COLONY_ID, self::AGRARDOM),
            'level 1 → 2 is checked as before'
        );
    }
}
