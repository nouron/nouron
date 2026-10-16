<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\Techtree\BuildingService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Per-building per-Sol AP cap for construction investment (Owner decision 2026-10-16).
 * Cap = base + engineer rank bonus; null base = off. Repair and placement are not capped.
 *
 * Fixture: Colony 1, Bart (user 3) with an engineer advisor of rank 1 (personell 35).
 */
class BuildingApCapTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const CC_ID = 25;

    private const AGRARDOM_ID = 41;

    private const ENGINEER_ID = 35;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        app(TickService::class)->setTickCount(10);

        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)
            ->update(['level' => 3, 'ap_spend' => 0]);
        // Plenty of AP so the cap, not the pool, is what stops an invest.
        config(['game.ap.base' => 40]);
    }

    private function setCap(?int $base): void
    {
        config(['game.build.ap_cap_per_sol' => [
            'base' => $base,
            'engineer_bonus_per_rank' => [1 => 2, 2 => 3, 3 => 4],
        ]]);
    }

    private function invest(int $buildingId = self::CC_ID)
    {
        return $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => $buildingId]);
    }

    private function spendUpTo(int $n, int $buildingId = self::CC_ID): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->invest($buildingId)->assertOk();
        }
    }

    private function engineer(): Builder
    {
        return DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::ENGINEER_ID);
    }

    public function test_cap_off_allows_unlimited_invests_and_reports_no_cap(): void
    {
        $this->setCap(null);

        $this->spendUpTo(8);
        $this->invest()->assertOk()->assertJsonPath('ap_cap', null);
    }

    public function test_cap_blocks_the_invest_beyond_base_plus_engineer_bonus(): void
    {
        $this->setCap(3); // + rank 1 engineer (+2) = 5
        $this->spendUpTo(5);

        $r = $this->invest();

        $r->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'building_ap_cap');
        $this->assertSame(__('colony.error_building_ap_cap', ['cap' => 5]), $r->json('message'));
        $this->assertSame(5, $r->json('cap'));
    }

    public function test_rejection_consumes_no_ap_and_no_regolith(): void
    {
        $this->setCap(1);
        $this->spendUpTo(3);
        $locked = DB::table('locked_actionpoints')->sum('spend_ap');
        $regolith = DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->get()->toJson();
        $spend = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->value('ap_spend');

        $this->invest()->assertStatus(422);

        $this->assertEquals($locked, DB::table('locked_actionpoints')->sum('spend_ap'));
        $this->assertSame($regolith, DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->get()->toJson());
        $this->assertEquals($spend, DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->value('ap_spend'));
    }

    public function test_success_response_reports_remaining_cap(): void
    {
        $this->setCap(3);

        $this->invest()->assertOk()
            ->assertJsonPath('ap_cap.cap', 5)
            ->assertJsonPath('ap_cap.spent', 1)
            ->assertJsonPath('ap_cap.remaining', 4);
    }

    public function test_cap_is_per_building_another_building_still_accepts_ap(): void
    {
        $this->setCap(3);
        $this->spendUpTo(5);

        $this->invest()->assertStatus(422);
        $this->invest(self::AGRARDOM_ID)->assertOk();
    }

    public function test_next_sol_resets_the_cap(): void
    {
        $this->setCap(3);
        $this->spendUpTo(5);
        $this->invest()->assertStatus(422);

        app(TickService::class)->setTickCount(11);

        $this->invest()->assertOk()->assertJsonPath('ap_cap.spent', 1);
    }

    public function test_engineer_rank_adds_its_bonus(): void
    {
        $this->setCap(3);
        $this->engineer()->update(['rank' => 2]); // +3 -> cap 6
        $this->spendUpTo(6);

        $this->invest()->assertStatus(422)->assertJsonPath('cap', 6);
    }

    public function test_unavailable_engineer_gives_base_only(): void
    {
        $this->setCap(3);
        $this->engineer()->update(['unavailable_until_tick' => 15]);
        $this->spendUpTo(3);

        $this->invest()->assertStatus(422)->assertJsonPath('cap', 3);
    }

    public function test_building_service_add_is_capped_but_repair_is_not(): void
    {
        $this->setCap(1);
        $this->spendUpTo(3);
        $service = app(BuildingService::class);

        $this->assertSame('building_ap_cap', $service->investBlocker(self::COLONY_ID, self::CC_ID, 'add', 1, false, 1));
        $this->assertNull($service->investBlocker(self::COLONY_ID, self::CC_ID, 'repair', 1, false, 1));
    }

    public function test_building_service_invest_counts_toward_the_same_cap(): void
    {
        $this->setCap(1); // cap 3 with engineer
        $service = app(BuildingService::class);
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue($service->invest(self::COLONY_ID, self::CC_ID, 'add', 1, 1));
        }

        $this->assertFalse($service->invest(self::COLONY_ID, self::CC_ID, 'add', 1, 1));
        $this->invest()->assertStatus(422)->assertJsonPath('error', 'building_ap_cap');
    }
}
