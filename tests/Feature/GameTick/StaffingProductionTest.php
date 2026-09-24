<?php

namespace Tests\Feature\GameTick;

use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 "Unterbesetzung" (GDD §5 produced amount × staffing share, §6): unfilled
 * workplaces lower raw-material production (Regolith, Organika) by the staffing
 * share, but not AP or Credits.
 *
 * Setup: trust 0 (multiplier 1.0), food need disabled, decay off. Supply costs
 * zeroed, then Harvester (27) and Agrardom (41, level 1) carry 10 supply each →
 * 20 workplaces. Stored cap 10 plus 10 departed colonists → staffing 0.5.
 * Production runs before the tick recalculates the cap, so it sees exactly this
 * state.
 *
 * Fixture colony 1 (user 3). Uses tick numbers 12900–12919.
 */
class StaffingProductionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    private const AGRARDOM = 41;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => 12], ['amount' => 0]);
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();
        config(['game.food.supply_per_eater' => PHP_INT_MAX]);

        DB::table('buildings')->update(['supply_cost' => 0, 'decay_rate' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->whereIn('id', [self::HARVESTER, self::AGRARDOM])->update(['supply_cost' => 10]);

        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER)->where('instance_id', 1)
            ->update(['level' => 1, 'status_points' => 20, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]);
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3,
            'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 300, 'resource_max' => 300,
        ]);
    }

    private function understaff(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 10]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_departed' => 10]);
    }

    private function amount(int $resourceId): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    public function test_staffing_share_scales_regolith_and_organika_production(): void
    {
        $this->understaff();
        $regolith = $this->amount(self::RES_REGOLITH);
        $organika = $this->amount(self::RES_ORGANICS);

        Artisan::call('game:tick', ['--tick' => 12900]);

        $fresh = (int) config('game.harvester.fresh_yield.regolith_normal');
        $agrardomL1 = (int) config('game.production_curve.41.5.1');
        $this->assertSame((int) round($fresh * 0.5), $this->amount(self::RES_REGOLITH) - $regolith, 'Regolith × staffing 0.5');
        $this->assertSame((int) round($agrardomL1 * 0.5), $this->amount(self::RES_ORGANICS) - $organika, 'Organika × staffing 0.5');
    }

    public function test_full_staffing_produces_the_full_amount(): void
    {
        $regolith = $this->amount(self::RES_REGOLITH);

        Artisan::call('game:tick', ['--tick' => 12905]);

        $this->assertSame((int) config('game.harvester.fresh_yield.regolith_normal'), $this->amount(self::RES_REGOLITH) - $regolith);
    }

    public function test_staffing_share_does_not_touch_credits_or_ap(): void
    {
        $advisors = $this->app->make(AdvisorService::class);

        $creditsBefore = $this->credits();
        Artisan::call('game:tick', ['--tick' => 12910]);
        $fullyStaffedCredits = $this->credits() - $creditsBefore;
        $apFullyStaffed = $advisors->getAvailableActionPoints(self::COLONY_ID);

        $this->understaff();
        $this->assertSame($apFullyStaffed, $advisors->getAvailableActionPoints(self::COLONY_ID), 'AP ignore the staffing share');

        $creditsBefore = $this->credits();
        Artisan::call('game:tick', ['--tick' => 12911]);

        $this->assertSame($fullyStaffedCredits, $this->credits() - $creditsBefore, 'Credits ignore the staffing share');
    }
}
