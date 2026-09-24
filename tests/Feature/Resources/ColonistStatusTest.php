<?php

namespace Tests\Feature\Resources;

use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 "Unterbesetzung" (GDD §6 "Überkapazität — Konsequenzen"): the single place
 * that derives colonists, homeless colonists and the staffing share.
 *
 *   workplaces = used supply (laufende_last)
 *   departed   = min(stored overcap_departed, max(0, workplaces − cap))
 *   present    = workplaces − departed
 *   homeless   = max(0, present − cap)
 *   staffing   = present / workplaces            (1 while nobody departed)
 *
 * Free supply for the build gate stays cap − workplaces: departed colonists are
 * unfilled workplaces that keep occupying the cap.
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

    private function state(int $cap, int $departed = 0): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $cap]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_departed' => $departed]);
    }

    public function test_within_cap_everyone_is_present_and_fully_staffed(): void
    {
        $this->state(30);

        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(30, $status['cap']);
        $this->assertSame(20, $status['workplaces']);
        $this->assertSame(0, $status['departed']);
        $this->assertSame(20, $status['present']);
        $this->assertSame(0, $status['homeless']);
        $this->assertSame(1.0, $status['staffing']);
    }

    public function test_colonists_above_the_cap_are_homeless(): void
    {
        $this->state(12);

        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(8, $status['homeless']);
        $this->assertSame(20, $status['present']);
        $this->assertSame(1.0, $status['staffing'], 'homeless colonists still work');
    }

    public function test_departed_colonists_leave_unfilled_workplaces(): void
    {
        $this->state(12, 8);

        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(8, $status['departed']);
        $this->assertSame(12, $status['present']);
        $this->assertSame(0, $status['homeless']);
        $this->assertEqualsWithDelta(0.6, $status['staffing'], 1e-9);
        $this->assertEqualsWithDelta(0.6, $this->service()->staffingShare(self::COLONY_ID), 1e-9);
    }

    public function test_new_housing_lets_departed_colonists_return_up_to_the_free_room(): void
    {
        $this->state(16, 8);

        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(4, $status['departed'], 'partial return: only 4 places free');
        $this->assertSame(16, $status['present']);
        $this->assertEqualsWithDelta(0.8, $status['staffing'], 1e-9);

        $this->state(25, 8);
        $status = $this->service()->colonistStatus(self::COLONY_ID);

        $this->assertSame(0, $status['departed'], 'full return, never more than departed');
        $this->assertSame(20, $status['present']);
        $this->assertSame(1.0, $status['staffing']);
    }

    public function test_free_supply_for_the_build_gate_still_counts_unfilled_workplaces(): void
    {
        $this->state(12, 8);

        $this->assertSame(-8, $this->service()->getFreeSupply(self::COLONY_ID));
    }

    public function test_food_need_counts_present_colonists_only(): void
    {
        $this->state(30);
        $this->assertSame(5, $this->service()->foodNeed(self::COLONY_ID), '20 present / 4');

        $this->state(12, 8);
        $this->assertSame(3, $this->service()->foodNeed(self::COLONY_ID), '12 present / 4');
    }

    public function test_over_cap_colonies_are_those_with_homeless_colonists(): void
    {
        $this->state(12);
        $this->assertContains(self::COLONY_ID, $this->service()->getOverCapColonyIds());

        $this->state(12, 8);
        $this->assertNotContains(self::COLONY_ID, $this->service()->getOverCapColonyIds(), 'understaffed, but nobody homeless');
    }

    // ── Projection helpers for the Rückbau preview (A43) ──────────────────────

    public function test_colonist_status_for_a_given_cap_uses_that_cap_instead_of_the_stored_one(): void
    {
        $this->state(30, 0);

        $status = $this->service()->colonistStatus(self::COLONY_ID, capOverride: 12);

        $this->assertSame(12, $status['cap']);
        $this->assertSame(20, $status['present']);
        $this->assertSame(8, $status['homeless']);
        $this->assertSame(30, $this->service()->colonistStatus(self::COLONY_ID)['cap'], 'the stored cap stays the default');
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
