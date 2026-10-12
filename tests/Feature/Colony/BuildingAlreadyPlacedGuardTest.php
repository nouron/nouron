<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** building_already_placed: a non-instanced building stands once; placing it again must not silently move it. */
class BuildingAlreadyPlacedGuardTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => true, 'game.bypass.resource_costs' => true]);
        $this->stand(41, 4); // Agrardom: prerequisite for path buildings (Labor, Cantina)
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 6, 'r' => 0],
            ['ring' => 3, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0, 'event_type' => null]
        );
    }

    private function place(int $buildingId, int $q = 6, int $r = 0)
    {
        return $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->postJson(route('colony.building.place'), ['building_id' => $buildingId, 'q' => $q, 'r' => $r]);
    }

    private function stand(int $buildingId, ?int $q, int $level = 1): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID, 'building_id' => $buildingId, 'instance_id' => 1, 'level' => $level,
            'status_points' => 20, 'ap_spend' => 3, 'tile_x' => $q, 'tile_y' => $q === null ? null : 0,
        ]);
    }

    private function row(int $buildingId): object
    {
        return DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', $buildingId)->first();
    }

    /** @return array<string, array{int}> */
    public static function singletons(): array
    {
        return ['sciencelab' => [31], 'bar' => [52]];
    }

    #[DataProvider('singletons')]
    public function test_second_placement_is_refused_and_does_not_move_the_building(int $buildingId): void
    {
        $this->stand($buildingId, 5);

        $this->place($buildingId)->assertStatus(422)->assertJson(['error' => 'building_already_placed']);

        $row = $this->row($buildingId);
        $this->assertSame([5, 3], [(int) $row->tile_x, (int) $row->ap_spend]);
    }

    #[DataProvider('singletons')]
    public function test_replacing_after_level_down_to_zero_is_allowed(int $buildingId): void
    {
        $this->stand($buildingId, null, 0);

        $r = $this->place($buildingId);
        $this->assertTrue($r->status() === 200, $r->getContent());

        $this->assertSame(6, (int) $this->row($buildingId)->tile_x);
    }

    public function test_harvester_move_is_not_blocked_by_the_guard(): void
    {
        $this->stand(27, 5);
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 6)->where('r', 0)->update(['tile_type' => 'regolith_y2_d2']);

        $res = $this->place(27);

        $this->assertTrue($res->status() === 200, $res->getContent());
        $this->assertSame(6, (int) $this->row(27)->tile_x);
    }
}
