<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Path-building build gate (GDD §16 "Slot-System", Gate-Logik; Owner decision
 * 2026-10-14: server rule): a NEW path building (sciencelab 31, hangar 44,
 * bar 52) may only be placed while
 *   placed path buildings < CC level − game.path_building_gate.cc_level_offset.
 * CC Lv2 → one, CC Lv3 → two, CC Lv4 → all three. Already placed path
 * buildings are never affected (CC Rückbau keeps them standing).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). The fixture's path-building
 * rows have no tile (tile_x NULL) — they count as not placed.
 */
class PathBuildingGateTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const CC_ID = 25;

    private const SCIENCELAB = 31;

    private const HANGAR = 44;

    private const BAR = 52;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Agrardom stands — the older, independent path-building prerequisite.
        $this->stand(41, 4, 1);
        foreach ([self::SCIENCELAB, self::HANGAR, self::BAR] as $id) {
            DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $id)->delete();
        }
        foreach ([[5, 0], [6, 0], [-5, 0], [-6, 0]] as [$q, $r]) {
            DB::table('colony_tiles')->updateOrInsert(
                ['colony_id' => self::COLONY_ID, 'q' => $q, 'r' => $r],
                ['ring' => 3, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'event_type' => null]
            );
        }
        // Plenty of Regolith/supply so only the gate can refuse a placement.
        DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->update(['amount' => 5000]);
        config(['game.bypass.supply_checks' => true]);
    }

    private function setCcLevel(int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::CC_ID)->update(['level' => $level]);
    }

    private function stand(int $buildingId, ?int $q, int $level = 1): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1, 'level' => $level,
            'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $q, 'tile_y' => $q === null ? null : 1,
        ]);
    }

    private function place(int $buildingId, int $q = 6, int $r = 0): TestResponse
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.place'), ['building_id' => $buildingId, 'q' => $q, 'r' => $r]);
    }

    /** @return array<int, array<string, mixed>> building_id => available-buildings entry */
    private function menu(): array
    {
        $body = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->getJson(route('colony.buildings.available'))->assertOk()->json('buildings');

        return collect($body)->keyBy('building_id')->all();
    }

    private function regolith(): int
    {
        return (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 3)->value('amount');
    }

    // ── placeBuilding() ───────────────────────────────────────────────────────

    public function test_cc2_allows_the_first_path_building(): void
    {
        $this->setCcLevel(2);

        $res = $this->place(self::BAR);

        $this->assertSame(200, $res->status(), $res->getContent());
    }

    public function test_cc2_refuses_a_second_path_building_and_changes_nothing(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::SCIENCELAB, 5);
        $apBefore = app(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
        $regolithBefore = $this->regolith();

        $this->place(self::BAR)
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error', 'path_building_gate')
            ->assertJsonPath('required_cc_level', 3)
            ->assertJsonPath('message', __('colony.error_path_building_gate', ['level' => 3]));

        $this->assertFalse(DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR)->exists());
        $this->assertSame($regolithBefore, $this->regolith());
        $this->assertSame($apBefore, app(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID));
    }

    public function test_cc3_allows_the_second_but_refuses_the_third_path_building(): void
    {
        $this->setCcLevel(3);
        $this->stand(self::SCIENCELAB, 5);

        $res = $this->place(self::HANGAR, 6, 0);
        $this->assertSame(200, $res->status(), $res->getContent());

        $this->place(self::BAR, -5, 0)
            ->assertStatus(422)
            ->assertJsonPath('error', 'path_building_gate')
            ->assertJsonPath('required_cc_level', 4);
    }

    public function test_cc4_allows_the_third_path_building(): void
    {
        $this->setCcLevel(4);
        $this->stand(self::SCIENCELAB, 5);
        $this->stand(self::HANGAR, -5);

        $res = $this->place(self::BAR);

        $this->assertSame(200, $res->status(), $res->getContent());
    }

    public function test_cc1_refuses_any_path_building(): void
    {
        $this->setCcLevel(1);

        $this->place(self::SCIENCELAB)
            ->assertStatus(422)
            ->assertJsonPath('error', 'path_building_gate')
            ->assertJsonPath('required_cc_level', 2);
    }

    public function test_non_path_buildings_are_not_gated(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::SCIENCELAB, 5);

        $res = $this->place(54); // uplinkStation, CC Lv2

        $this->assertSame(200, $res->status(), $res->getContent());
    }

    /** CC Rückbau below the gate never touches path buildings that already stand. */
    public function test_existing_path_buildings_keep_working_after_cc_level_down(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::SCIENCELAB, 5);
        $this->stand(self::HANGAR, -5); // placed at CC3, CC since levelled down

        $res = $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => self::HANGAR]);

        $this->assertSame(200, $res->status(), $res->getContent());
        $this->assertSame(-5, (int) DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::HANGAR)->value('tile_x'));
    }

    /** A path building levelled down to 0 left its tile — placing it again is a new placement. */
    public function test_re_placing_a_released_path_building_is_gated(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::SCIENCELAB, 5);
        $this->stand(self::BAR, null, 0);

        $this->place(self::BAR)->assertStatus(422)->assertJsonPath('error', 'path_building_gate');
    }

    public function test_re_placing_a_released_path_building_is_allowed_below_the_gate(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::BAR, null, 0);

        $res = $this->place(self::BAR);

        $this->assertSame(200, $res->status(), $res->getContent());
    }

    /** @return array<string, array{int}> */
    public static function pathBuildings(): array
    {
        return ['sciencelab' => [self::SCIENCELAB], 'hangar' => [self::HANGAR], 'bar' => [self::BAR]];
    }

    /** Uniqueness wins over the gate: a standing path building is refused as already placed, at any CC level. */
    #[DataProvider('pathBuildings')]
    public function test_a_standing_path_building_cannot_be_placed_twice(int $buildingId): void
    {
        $this->setCcLevel(5);
        $this->stand($buildingId, 5);

        $this->place($buildingId)->assertStatus(422)->assertJsonPath('error', 'building_already_placed');
        $this->assertSame(1, DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->count());
    }

    public function test_the_offset_comes_from_config(): void
    {
        config(['game.path_building_gate.cc_level_offset' => 2]);
        $this->setCcLevel(3);
        $this->stand(self::SCIENCELAB, 5);

        $this->place(self::BAR)
            ->assertStatus(422)
            ->assertJsonPath('error', 'path_building_gate')
            ->assertJsonPath('required_cc_level', 4);
    }

    // ── availableBuildings() ──────────────────────────────────────────────────

    public function test_menu_lists_gated_path_buildings_as_locked_with_reason(): void
    {
        $this->setCcLevel(2);
        $this->stand(self::SCIENCELAB, 5);

        $menu = $this->menu();

        $this->assertArrayNotHasKey(self::SCIENCELAB, $menu, 'a placed path building stays out of the menu');
        foreach ([self::HANGAR, self::BAR] as $id) {
            $this->assertArrayHasKey($id, $menu);
            $this->assertTrue($menu[$id]['locked']);
            $this->assertSame(__('colony.path_building_locked', ['level' => 3]), $menu[$id]['locked_reason']);
        }
        $this->assertFalse($menu[54]['locked'], 'non-path buildings are never path-gated');
        $this->assertNull($menu[54]['locked_reason']);
    }

    /** The build menu greys out a locked entry, shows the reason and refuses to select it (also via ?build= deep link). */
    public function test_build_menu_client_honours_the_lock(): void
    {
        $js = file_get_contents(public_path('js/colony-hexgrid.js'));
        $blade = file_get_contents(resource_path('views/colony/hexview.blade.php'));

        $this->assertMatchesRegularExpression('/canPlaceBuilding\(b\)\s*\{[^}]*b\.locked[^}]*canAffordBuilding\(b\)/s', $js);
        $this->assertMatchesRegularExpression('/match && !match\.locked/', $js);
        $this->assertStringContainsString('@click="canPlaceBuilding(b) && selectPendingBuilding(b)"', $blade);
        $this->assertStringContainsString('buildMissingReasons(b)', $blade);
        $this->assertStringContainsString("{ kind: 'locked', text: b.locked_reason }", $js);
    }

    public function test_menu_unlocks_the_next_path_building_at_the_next_cc_level(): void
    {
        $this->setCcLevel(3);
        $this->stand(self::SCIENCELAB, 5);

        $menu = $this->menu();

        $this->assertFalse($menu[self::HANGAR]['locked']);
        $this->assertNull($menu[self::HANGAR]['locked_reason']);
        $this->assertFalse($menu[self::BAR]['locked']);
    }

    public function test_menu_lists_buildings_above_the_cc_level_as_locked_with_the_needed_level(): void
    {
        $this->setCcLevel(1);

        $menu = $this->menu();

        $this->assertArrayHasKey(self::SCIENCELAB, $menu);
        $this->assertTrue($menu[self::SCIENCELAB]['locked']);
        $this->assertSame(__('colony.build_locked_cc', ['level' => 2]), $menu[self::SCIENCELAB]['locked_reason']);
    }

    public function test_menu_locks_path_buildings_without_agrardom_and_says_why(): void
    {
        $this->setCcLevel(3);
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 41)->delete();

        $menu = $this->menu();

        $this->assertTrue($menu[self::HANGAR]['locked']);
        $this->assertSame(__('colony.error_agrardom_required'), $menu[self::HANGAR]['locked_reason']);
    }

    public function test_placing_above_the_cc_level_is_refused_server_side(): void
    {
        $this->setCcLevel(1);
        $rgBefore = $this->regolith();

        $res = $this->place(46); // infirmary: needs CC Lv3, not a path building

        $this->assertSame(422, $res->status());
        $this->assertSame('build_cc_required', $res->json('error'));
        $this->assertSame($rgBefore, $this->regolith());
    }
}
