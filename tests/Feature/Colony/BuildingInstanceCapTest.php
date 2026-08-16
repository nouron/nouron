<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Enforcement split: `buildings.max_instances` (instance-count cap) vs.
 * `buildings.max_level` (per-instance level cap) are independent axes
 * (GDD §4c, Owner-Entscheidung 2026-08-03).
 *
 * ColonyController::availableBuildings() must gate the buildable-list entry
 * for instanced buildings on `max_instances`, not `max_level` — the two
 * happened to be numerically identical for housingComplex before the split,
 * which would let a stale `max_level` read pass unnoticed. Diverging the two
 * values in the fixtures below is what actually exercises the split.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), housingComplex id=28,
 * harvester id=27.
 */
class BuildingInstanceCapTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HOUSING_ID = 28;

    private const HARVESTER_ID = 27;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function placeHousingInstances(int $count): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HOUSING_ID)
            ->delete();

        for ($i = 1; $i <= $count; $i++) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => self::HOUSING_ID,
                'instance_id' => $i,
                'level' => 1,
                'status_points' => 20,
                'ap_spend' => 0,
                'tile_x' => $i,
                'tile_y' => 0,
            ]);
        }
    }

    private function availableBuildingIds(): array
    {
        $response = $this->actingAs($this->bart())->getJson(route('colony.buildings.available'));
        $response->assertOk();

        return collect($response->json('buildings'))->pluck('building_id')->all();
    }

    public function test_housing_complex_listed_when_max_instances_above_reached_count_even_if_max_level_would_exclude(): void
    {
        // Diverge the two axes: max_level=3 (would exclude at count=3 under the old
        // "read max_level for the instance count" bug), max_instances=6 (must not
        // exclude yet).
        DB::table('buildings')->where('id', self::HOUSING_ID)->update(['max_level' => 3, 'max_instances' => 6]);
        $this->placeHousingInstances(3);

        $this->assertContains(self::HOUSING_ID, $this->availableBuildingIds());
    }

    public function test_housing_complex_absent_once_max_instances_reached(): void
    {
        DB::table('buildings')->where('id', self::HOUSING_ID)->update(['max_level' => 6, 'max_instances' => 3]);
        $this->placeHousingInstances(3);

        $this->assertNotContains(self::HOUSING_ID, $this->availableBuildingIds());
    }

    public function test_housing_complex_still_listed_below_configured_instance_cap(): void
    {
        // Regression guard: housingComplex ships with max_level == max_instances == 6
        // — confirm the pre-existing "5 placed, 6th still offered" behaviour survives
        // the switch from max_level to max_instances.
        DB::table('buildings')->where('id', self::HOUSING_ID)->update(['max_level' => 6, 'max_instances' => 6]);
        $this->placeHousingInstances(5);

        $this->assertContains(self::HOUSING_ID, $this->availableBuildingIds());
    }

    public function test_harvester_level_up_still_blocked_by_max_level_one(): void
    {
        config(['game.bypass.ap_checks' => true]);

        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 1, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        $response = $this->actingAs($this->bart())->postJson(route('colony.building.invest'), [
            'building_id' => self::HARVESTER_ID,
            'instance_id' => 1,
        ]);

        $response->assertStatus(422)->assertJsonPath('ok', false);
        $this->assertSame('max_level_reached', $response->json('error'));
    }

    /**
     * Cantina (bar, id=52) is one of three parallel "path" buildings
     * (sciencelab/hangar/bar), all meant to unlock at CC Lv2 in parity
     * (ColonyController::PATH_BUILDING_IDS docblock, GDD §13 "Pfadwahl ab
     * Sol 3"). Bug found 2026-08-16 (Owner playtest): bar's DB row still had
     * `required_building_id=28` (housingComplex) `required_building_level=1`
     * from migration 2026_05_08_000004_techtree_dependencies_phase3g.php —
     * it was never updated when hangar's CC-gate was lowered from Lv3 to Lv2
     * in migration 2026_06_25_000100, so bar bypassed the CC-Lv2 gate
     * entirely (available from Sol 1 instead of CC Lv2, unlike its two
     * siblings).
     */
    public function test_cantina_requires_cc_level_2_like_sciencelab_and_hangar(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 25) // CommandCenter
            ->update(['level' => 1]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 41, 'instance_id' => 1], // bioFacility
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 0]
        );

        $availableAtLv1 = $this->availableBuildingIds();
        $this->assertNotContains(31, $availableAtLv1, 'sciencelab must not be available at CC Lv1');
        $this->assertNotContains(44, $availableAtLv1, 'hangar must not be available at CC Lv1');
        $this->assertNotContains(52, $availableAtLv1, 'bar/Cantina must not be available at CC Lv1 either — same gate as its two path-building siblings');

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 25)
            ->update(['level' => 2]);

        $availableAtLv2 = $this->availableBuildingIds();
        $this->assertContains(31, $availableAtLv2, 'sciencelab must be available at CC Lv2');
        $this->assertContains(44, $availableAtLv2, 'hangar must be available at CC Lv2');
        $this->assertContains(52, $availableAtLv2, 'bar/Cantina must be available at CC Lv2, matching its siblings');
    }
}
