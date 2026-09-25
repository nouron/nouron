<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\HarvesterEntitlementService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Owner rule 2026-09-25 (T9): the Regolith a building costs is always deducted in
 * full when it is placed. Placing therefore pays the erect cost PLUS the flat
 * level-up Regolith of the 0 -> 1 step (GDD §13.7 "auch der Sprung 0→1 nach der
 * Errichtung") in one payment; the 0 -> 1 invest cycle afterwards costs no
 * Regolith. Later level-ups (1 -> 2, ...) keep charging on cycle start.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), CC level 3.
 * infirmary=46 (60 Rg + 25 Wk), hangar=44 (95 Rg, instanced), harvester=27.
 */
class PlacementPrepaysFirstLevelTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const INFIRMARY = 46;

    private const HANGAR = 44;

    private const HARVESTER = 27;

    private const FIRST_LEVEL_REGOLITH = 25;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.bypass.resource_costs' => false,
            'game.bypass.supply_checks' => true,
        ]);

        // Agrardom placed — path buildings (hangar) require it.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 41, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );

        // The fixture seeds an unplaced infirmary row at level 3 (legacy data) —
        // remove it so every placement here is a genuine new build on level 0.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::INFIRMARY)->delete();

        $this->setColonyRes(self::RES_REGOLITH, 500);
        $this->setColonyRes(self::RES_COMPOUNDS, 100);
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function colonyRes(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function setColonyRes(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function ensureBuildableTile(int $q, int $r): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );
    }

    private function place(int $buildingId, int $q, int $r, array $extra = []): TestResponse
    {
        return $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => $buildingId, 'q' => $q, 'r' => $r, ...$extra]);
    }

    private function invest(int $buildingId, int $instanceId = 1): TestResponse
    {
        return $this->actingAs($this->bart())
            ->postJson(route('colony.building.invest'), ['building_id' => $buildingId, 'instance_id' => $instanceId]);
    }

    private function row(int $buildingId, int $instanceId = 1): object
    {
        return DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->first();
    }

    // ── Placement charges erect cost + first level ─────────────────────────────

    public function test_placing_deducts_erect_cost_plus_first_level_regolith(): void
    {
        $this->ensureBuildableTile(1, 0);

        $this->place(self::INFIRMARY, 1, 0)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(500 - 60 - self::FIRST_LEVEL_REGOLITH, $this->colonyRes(self::RES_REGOLITH));
        $this->assertSame(100 - 25, $this->colonyRes(self::RES_COMPOUNDS), 'Werkstoffe: erect cost only');
    }

    public function test_placing_is_rejected_when_regolith_covers_erect_cost_but_not_the_sum(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->setColonyRes(self::RES_REGOLITH, 60 + self::FIRST_LEVEL_REGOLITH - 1);

        $this->place(self::INFIRMARY, 1, 0)
            ->assertStatus(422)
            ->assertJsonPath('error', 'resource_limit')
            ->assertJsonPath('cost.'.self::RES_REGOLITH, 60 + self::FIRST_LEVEL_REGOLITH);

        $this->assertSame(84, $this->colonyRes(self::RES_REGOLITH), 'no Regolith deducted');
        $this->assertSame(100, $this->colonyRes(self::RES_COMPOUNDS), 'no Werkstoffe deducted');
        $this->assertFalse(
            DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('tile_x', 1)->where('tile_y', 0)->exists()
        );
    }

    public function test_placing_a_new_instance_of_an_instanced_building_charges_the_sum(): void
    {
        $this->ensureBuildableTile(1, 0);

        $this->place(self::HANGAR, 1, 0)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(500 - 95 - self::FIRST_LEVEL_REGOLITH, $this->colonyRes(self::RES_REGOLITH));
    }

    // ── The 0 -> 1 cycle is prepaid, later cycles are not ───────────────────────

    public function test_first_level_invest_after_placement_deducts_no_regolith(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->invest(self::INFIRMARY)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame($before, $this->colonyRes(self::RES_REGOLITH));
    }

    public function test_first_level_invest_deducts_nothing_even_when_ap_spend_is_zero(): void
    {
        // Defensive: a placed level-0 site must never pay the first level twice,
        // even if its ap_spend reads 0 (legacy/seeded rows).
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY)
            ->update(['ap_spend' => 0]);
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->invest(self::INFIRMARY)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame($before, $this->colonyRes(self::RES_REGOLITH));
    }

    public function test_first_level_invest_is_not_blocked_by_an_empty_regolith_stock(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();
        $this->setColonyRes(self::RES_REGOLITH, 0);

        $this->invest(self::INFIRMARY)->assertOk()->assertJsonPath('ok', true);
    }

    public function test_second_level_invest_still_deducts_flat_regolith_on_cycle_start(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY)
            ->update(['level' => 1, 'ap_spend' => 0]);
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->invest(self::INFIRMARY)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame($before - self::FIRST_LEVEL_REGOLITH, $this->colonyRes(self::RES_REGOLITH));
    }

    // ── Cancel + re-place is a new build ────────────────────────────────────────

    public function test_replacing_after_construction_cancel_pays_the_full_sum_again(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->ensureBuildableTile(1, 1);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();

        $this->actingAs($this->bart())
            ->postJson(route('colony.building.leveldown'), ['building_id' => self::INFIRMARY, 'instance_id' => 1])
            ->assertOk()->assertJsonPath('construction_cancelled', true);
        $before = $this->colonyRes(self::RES_REGOLITH);

        $this->place(self::INFIRMARY, 1, 1)->assertOk()->assertJsonPath('ok', true);
        $this->assertSame($before - 60 - self::FIRST_LEVEL_REGOLITH, $this->colonyRes(self::RES_REGOLITH));

        $afterPlace = $this->colonyRes(self::RES_REGOLITH);
        $this->invest(self::INFIRMARY)->assertOk();
        $this->assertSame($afterPlace, $this->colonyRes(self::RES_REGOLITH), 'the re-placed 0 -> 1 step is prepaid too');
    }

    // ── Harvester: second instance + relocation stay Regolith-free ─────────────

    public function test_harvester_second_instance_placement_and_first_level_cost_no_regolith(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]
        );
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER)->where('instance_id', 2)->delete();
        DB::table('colony_tiles')->insertOrIgnore([
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
            ['colony_id' => self::COLONY_ID, 'q' => -3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_poor', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 160, 'resource_max' => 160],
        ]);
        $this->app->make(HarvesterEntitlementService::class)->grantPurchase(self::BART_USER_ID);

        $this->place(self::HARVESTER, -3, 0, ['instance_id' => 2])->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(500, $this->colonyRes(self::RES_REGOLITH), 'second instance placement is Regolith-free (GDD §4c)');

        $this->invest(self::HARVESTER, 2)->assertOk();
        $this->assertSame(500, $this->colonyRes(self::RES_REGOLITH), 'Harvester level-ups are Regolith-free');
    }

    public function test_harvester_relocation_costs_no_regolith(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]
        );
        DB::table('colony_tiles')->insertOrIgnore([
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
            ['colony_id' => self::COLONY_ID, 'q' => 4, 'r' => 0, 'ring' => 4, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300],
        ]);

        $this->place(self::HARVESTER, 4, 0)->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(500, $this->colonyRes(self::RES_REGOLITH));
        $row = $this->row(self::HARVESTER);
        $this->assertSame(1, (int) $row->level, 'relocation keeps the level');
        $this->assertSame(0, (int) $row->ap_spend, 'relocation leaves ap_spend unchanged');
    }

    // ── Display data: angezeigte Zahl = wirkende Zahl ───────────────────────────

    public function test_build_list_reports_the_full_placement_cost_with_breakdown(): void
    {
        $response = $this->actingAs($this->bart())->getJson(route('colony.buildings.available'))->assertOk();

        $infirmary = collect($response->json('buildings'))->firstWhere('building_id', self::INFIRMARY);
        $this->assertNotNull($infirmary, 'infirmary must be buildable in the fixture');
        $this->assertSame([self::RES_REGOLITH => 60, self::RES_COMPOUNDS => 25], $infirmary['build_cost']);
        $this->assertSame(self::FIRST_LEVEL_REGOLITH, $infirmary['first_level_regolith']);
        $this->assertSame([self::RES_REGOLITH => 85, self::RES_COMPOUNDS => 25], $infirmary['placement_cost']);
    }

    public function test_placed_level_zero_building_row_reports_first_level_as_prepaid(): void
    {
        $this->ensureBuildableTile(1, 0);

        $building = $this->place(self::INFIRMARY, 1, 0)->assertOk()->json('building');

        $this->assertSame(0, $building['levelup_cost'], 'no Regolith due for the 0 -> 1 step');
        $this->assertTrue($building['first_level_prepaid']);
    }

    public function test_level_one_building_row_reports_the_next_level_cost(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY)
            ->update(['level' => 1, 'ap_spend' => 0]);

        // A repair click returns the refreshed row without touching level/cost state.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::INFIRMARY)
            ->update(['status_points' => 10]);
        $building = $this->actingAs($this->bart())
            ->postJson(route('colony.building.repair'), ['building_id' => self::INFIRMARY])
            ->assertOk()->json('building');

        $this->assertSame(self::FIRST_LEVEL_REGOLITH, $building['levelup_cost']);
        $this->assertFalse($building['first_level_prepaid']);
    }

    public function test_hexview_sidebar_data_marks_level_zero_site_as_prepaid(): void
    {
        $this->ensureBuildableTile(1, 0);
        $this->place(self::INFIRMARY, 1, 0)->assertOk();

        $buildings = $this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->viewData('buildings');

        $site = collect($buildings)->first(fn ($b) => (int) $b->building_id === self::INFIRMARY);
        $this->assertSame(0, $site->levelup_cost);
        $this->assertTrue($site->first_level_prepaid);
    }

    public function test_hexview_markup_shows_placement_total_breakdown_and_prepaid_note(): void
    {
        $html = $this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->getContent();

        // Build list: the charged total, broken down into erect cost + level 1.
        $this->assertStringContainsString('b.placement_cost?.[3]', $html);
        $this->assertStringContainsString('b.first_level_regolith', $html);
        $this->assertStringContainsString(e(__('colony.build_cost_first_level')), $html);
        // Sidebar: a placed level-0 site states that level 1 is already paid.
        $this->assertStringContainsString('selectedBuilding.first_level_prepaid', $html);
        $this->assertStringContainsString(e(__('colony.first_level_prepaid')), $html);
    }

    public function test_build_chip_affordability_uses_the_placement_cost(): void
    {
        $js = file_get_contents(public_path('js/colony-hexgrid.js'));

        $this->assertMatchesRegularExpression('/canAffordBuilding\(b\)\s*\{[^}]*placement_cost/s', $js);
    }

    // ── CC centre (0,0) is never buildable ──────────────────────────────────────

    public function test_placing_on_the_cc_centre_tile_is_rejected(): void
    {
        // The CC has no tile row (tile_x = null) — it is drawn at (0,0) by the
        // client. The server must not trust the client to keep (0,0) free.
        $this->ensureBuildableTile(0, 0);

        $this->place(self::INFIRMARY, 0, 0)->assertStatus(422)->assertJsonPath('error', 'tile_occupied');

        $this->assertSame(500, $this->colonyRes(self::RES_REGOLITH));
        $this->assertFalse(
            DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('tile_x', 0)->where('tile_y', 0)->exists()
        );
    }

    public function test_placing_a_harvester_on_the_cc_centre_tile_is_rejected(): void
    {
        // Even if (0,0) were a regolith tile, neither the instance-1 move nor a
        // fresh second instance may land on the CC centre.
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 0, 'r' => 0],
            ['ring' => 0, 'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'resource_amount' => 300, 'resource_max' => 300]
        );
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER)->where('instance_id', 2)->delete();
        $this->app->make(HarvesterEntitlementService::class)->grantPurchase(self::BART_USER_ID);

        $this->place(self::HARVESTER, 0, 0)->assertStatus(422)->assertJsonPath('error', 'tile_occupied');
        $this->place(self::HARVESTER, 0, 0, ['instance_id' => 2])->assertStatus(422)->assertJsonPath('error', 'tile_occupied');

        $this->assertFalse(
            DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('tile_x', 0)->where('tile_y', 0)->exists()
        );
    }
}
