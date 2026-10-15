<?php

namespace Tests\Feature\Colony;

/**
 * The Command Center is the pace-setter (GDD §13.7 "Taktgeber CC"): each CC level-up
 * costs rising AP (config buildings.commandCenter.cc_upgrade_ap_by_level) on top of
 * its Regolith. Other buildings keep the flat buildings.ap_for_levelup.
 */

use App\Models\User;
use App\Services\BuildingCostService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CcLevelupApCurveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['buildings.commandCenter.cc_upgrade_ap_by_level' => [2 => 10, 3 => 20, 4 => 30, 5 => 40]]);
    }

    public function test_cc_ap_follows_the_curve_by_target_level_and_other_buildings_stay_flat(): void
    {
        $costs = app(BuildingCostService::class);

        $this->assertSame(10, $costs->levelupAp(25, 2, 10));
        $this->assertSame(20, $costs->levelupAp(25, 3, 10));
        $this->assertSame(40, $costs->levelupAp(25, 5, 10));
        $this->assertSame(10, $costs->levelupAp(28, 3, 10));
        $this->assertSame(10, $costs->levelupAp(25, 1, 10), 'no curve entry -> base value');
    }

    private function setCc(int $level, int $apSpend): void
    {
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 25)
            ->update(['level' => $level, 'ap_spend' => $apSpend]);
    }

    private function invest()
    {
        return $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => 25]);
    }

    public function test_cc_lv2_to_lv3_needs_the_full_curve_value_in_invested_ap(): void
    {
        $this->setCc(2, 10);   // flat 10 would level up on the next click
        $this->invest()->assertOk();
        $this->assertSame(2, (int) DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 25)->value('level'));

        $this->setCc(2, 19);
        $this->invest()->assertOk();
        $this->assertSame(3, (int) DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 25)->value('level'));
    }
}
