<?php

namespace Tests\Feature\Colony;

/**
 * Agrardom (bioFacility, building_id 41) as a hard prerequisite for the CC
 * Lv1 -> Lv2 level-up (GDD §4 "Agrardom wird Pflichtgebäude vor CC Lv2",
 * ROADMAP C16). Previously only enforced for the three path buildings
 * (sciencelab/hangar/bar) in placeBuilding() — investBuilding() never
 * checked it, so the CC itself could reach Lv2 without an Agrardom ever
 * placed. See ColonyController::agrardomPlaced()/investBuilding().
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), CC building_id=25
 * (ap_for_levelup=10), Agrardom building_id=41.
 */

use App\Models\User;
use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgrardomCcGateTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const CC_ID = 25;

    private const AGRARDOM_ID = 41;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function setCc(array $attrs): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->update($attrs);
    }

    private function ccRow(): object
    {
        return DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->first();
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
    }

    private function placeAgrardom(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::AGRARDOM_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );
    }

    private function invest(int $buildingId = self::CC_ID)
    {
        return $this->actingAs($this->bart())
            ->postJson(route('colony.building.invest'), ['building_id' => $buildingId]);
    }

    public function test_cc_lv1_to_lv2_investment_rejected_without_agrardom(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::AGRARDOM_ID)->delete();
        $this->setCc(['level' => 1, 'ap_spend' => 0, 'status_points' => 20]);
        $apBefore = app(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
        $regolithBefore = $this->regolith();

        $response = $this->invest();

        $response->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'agrardom_required');

        $row = $this->ccRow();
        $this->assertSame(1, (int) $row->level, 'CC must not level up');
        $this->assertSame(0, (int) $row->ap_spend, 'no AP progress recorded on a rejected invest');
        $this->assertSame($regolithBefore, $this->regolith(), 'no Regolith deducted on a rejected invest');
        $this->assertSame(
            $apBefore,
            app(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID),
            'no AP locked on a rejected invest'
        );
    }

    public function test_cc_lv1_to_lv2_investment_succeeds_once_agrardom_is_placed(): void
    {
        $this->placeAgrardom();
        $this->setCc(['level' => 1, 'ap_spend' => 0, 'status_points' => 20]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, (int) $this->ccRow()->ap_spend);
    }

    public function test_cc_lv2_to_lv3_investment_not_gated_by_agrardom(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::AGRARDOM_ID)->delete();
        $this->setCc(['level' => 2, 'ap_spend' => 0, 'status_points' => 20]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, (int) $this->ccRow()->ap_spend);
    }

    public function test_cc_lv4_investment_not_gated_by_agrardom(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::AGRARDOM_ID)->delete();
        $this->setCc(['level' => 4, 'ap_spend' => 0, 'status_points' => 20]);

        $response = $this->invest();

        $response->assertOk()->assertJsonPath('ok', true);
    }

    // ── placeBuilding() DRY regression — path-building gate unchanged ──────────

    private function ensureBuildableTile(int $q, int $r): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );
    }

    public function test_place_building_still_rejects_path_building_without_agrardom(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::AGRARDOM_ID)->delete();
        $this->setCc(['level' => 2]);
        $this->ensureBuildableTile(1, 0);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => 31, 'q' => 1, 'r' => 0]);

        $response->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'agrardom_required');
    }

    public function test_place_building_allows_path_building_once_agrardom_is_placed(): void
    {
        $this->placeAgrardom();
        $this->setCc(['level' => 2]);
        $this->ensureBuildableTile(1, 0);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => 31, 'q' => 1, 'r' => 0]);

        $response->assertOk()->assertJsonPath('ok', true);
    }
}
