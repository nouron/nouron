<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\ColonyTileService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Decoupling of "colony zone" (build permission) from "exploration" (sight).
 *
 * Rules under test:
 *  - assignColonyZone() marks tiles buildable (is_colony_zone=1) but no longer
 *    auto-explores them — a zone tile stays fogged until explored or built on.
 *  - placeBuilding() allows building on a still-fogged colony-zone tile and
 *    reveals it (settle → see).
 *  - The harvester still requires an explored regolith target.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class ColonyZoneDecoupleTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HARVESTER_ID = 27;

    private const DEPOT_ID = 30;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function tile(int $q, int $r): ?object
    {
        return DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->where('q', $q)->where('r', $r)
            ->first();
    }

    public function test_assign_colony_zone_does_not_auto_explore(): void
    {
        // Controlled tile set: CC tile + a ring-2 fogged terrain tile.
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_tiles')->insert([
            ['colony_id' => self::COLONY_ID, 'q' => 0, 'r' => 0, 'ring' => 0, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => 1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0, 'ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 0, 'is_colony_zone' => 0, 'is_deep_scanned' => 0],
        ]);

        // CC level 3 → expansion covers ring 1 + ring 2 terrain tiles.
        $this->app->make(ColonyTileService::class)->assignColonyZone(self::COLONY_ID, 3);

        $ring2 = $this->tile(2, 0);
        $this->assertSame(1, (int) $ring2->is_colony_zone, 'ring-2 tile must become buildable');
        $this->assertSame(0, (int) $ring2->is_explored, 'zone tile must stay fogged (no auto-explore)');
    }

    public function test_building_on_fogged_zone_tile_reveals_it(): void
    {
        // A buildable but still-fogged colony-zone tile.
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 1, 'r' => -1],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 0, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), [
                'building_id' => self::DEPOT_ID,
                'q' => 1,
                'r' => -1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);

        $this->assertSame(1, (int) $this->tile(1, -1)->is_explored, 'building must reveal the fogged tile');
        $this->assertTrue(
            DB::table('colony_buildings')
                ->where('colony_id', self::COLONY_ID)
                ->where('tile_x', 1)->where('tile_y', -1)
                ->exists(),
            'building must be placed on the tile'
        );
    }

    public function test_next_zone_keys_are_terrain_and_empty_at_max_level(): void
    {
        $svc = $this->app->make(ColonyTileService::class);

        // The next CC charge only ever flags buildable terrain (never regolith/impassable).
        foreach (array_keys($svc->nextZoneTileKeys(self::COLONY_ID, 1)) as $key) {
            [$q, $r] = array_map('intval', explode(',', $key));
            $tile = $this->tile($q, $r);
            $this->assertNotNull($tile, "next-zone tile {$key} must exist");
            $this->assertStringStartsWith('terrain_', $tile->tile_type);
            $this->assertNotSame('terrain_impassable', $tile->tile_type);
        }

        // At max CC level there is no further charge → no "soon buildable" tiles.
        $this->assertSame([], $svc->nextZoneTileKeys(self::COLONY_ID, 5));
    }

    public function test_harvester_still_requires_explored_target(): void
    {
        // Unexplored regolith tile — harvester relocation must be refused.
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 3, 'r' => -1],
            ['ring' => 3, 'tile_type' => 'regolith_normal', 'is_explored' => 0, 'is_colony_zone' => 0, 'is_deep_scanned' => 0]
        );

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), [
                'building_id' => self::HARVESTER_ID,
                'q' => 3,
                'r' => -1,
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('error', __('colony.error_not_explored'));
    }
}
