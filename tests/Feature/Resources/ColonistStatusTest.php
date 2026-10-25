<?php

namespace Tests\Feature\Resources;

use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T43-G9 (housing loss, option A2): colonists, employed, free colonists, unfilled
 * workplaces and the staffing share — all derived from housing and workplaces, no
 * stored state.
 *
 *   colonists = stored cap; workplaces = used supply
 *   employed  = min(colonists, workplaces); unfilled = max(0, workplaces − colonists)
 *   staffing  = employed / workplaces (1 while every workplace is staffed)
 *
 * Free supply for the build gate stays cap − workplaces.
 *
 * Setup: every supply cost zeroed, then the Harvester (27, colony 1 level 1)
 * carries 20 supply → 20 workplaces. The cap is set directly on user_resources.
 */
class ColonistStatusTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 20]);
        config(['game.food.supply_per_eater' => 4]);
    }

    private function service(): ResourcesService
    {
        return $this->app->make(ResourcesService::class);
    }

    private function state(int $cap): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $cap]);
    }

    public function test_within_the_housing_everyone_is_employed_and_fully_staffed(): void
    {
        $this->state(30);

        $this->assertSame(['colonists' => 30, 'employed' => 20, 'free' => 10, 'unfilled' => 0], $this->service()->colonistsOverview(self::COLONY_ID));
        $this->assertSame(1.0, $this->service()->staffingShare(self::COLONY_ID));
    }

    public function test_workplaces_above_the_housing_are_unfilled_and_cut_the_staffing_share(): void
    {
        $this->state(12);

        $this->assertSame(['colonists' => 12, 'employed' => 12, 'free' => 0, 'unfilled' => 8], $this->service()->colonistsOverview(self::COLONY_ID));
        $this->assertEqualsWithDelta(0.6, $this->service()->staffingShare(self::COLONY_ID), 1e-9);
    }

    public function test_free_supply_for_the_build_gate_counts_unfilled_workplaces(): void
    {
        $this->state(12);

        $this->assertSame(-8, $this->service()->getFreeSupply(self::COLONY_ID));
    }

    public function test_food_need_counts_employed_colonists(): void
    {
        $this->state(30);
        $this->assertSame(5, $this->service()->foodNeed(self::COLONY_ID), '20 employed / 4');

        $this->state(12);
        $this->assertSame(3, $this->service()->foodNeed(self::COLONY_ID), '12 employed / 4');
    }

    public function test_no_workplaces_means_full_staffing(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        $this->state(10);

        $this->assertSame(1.0, $this->service()->staffingShare(self::COLONY_ID));
    }

    public function test_calculated_supply_cap_equals_the_cap_a_sol_stores(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 0]);

        Artisan::call('game:tick', ['--tick' => 11500]);

        $this->assertSame(
            (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('supply'),
            $this->service()->calculateSupplyCap(self::COLONY_ID)
        );
    }

    public function test_calculated_supply_cap_is_zero_without_command_center(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 25)->update(['level' => 0]);

        $this->assertSame(0, $this->service()->calculateSupplyCap(self::COLONY_ID));
    }
}
