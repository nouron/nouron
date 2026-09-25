<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Building log events carry the instance they refer to, so the comm-log building
 * chip can deep-link to exactly that tile (GDD entity-chips "Zum Tile",
 * /colony/view?building=ID&instance=N).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). Housing (28) instances 1, 4, 5.
 */
class BuildingLogInstanceParamsTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HOUSING_ID = 28;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        DB::table('colony_log')->where('user', self::BART_USER_ID)->delete();
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function lastEventParams(string $event): array
    {
        $raw = DB::table('colony_log')
            ->where('user', self::BART_USER_ID)
            ->where('event', $event)
            ->orderByDesc('id')
            ->value('parameters');
        $this->assertNotNull($raw, "{$event} must be logged");

        return json_decode($raw, true);
    }

    public function test_building_placed_event_records_instance_id(): void
    {
        DB::table('colony_tiles')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'q' => 1, 'r' => 0],
            ['ring' => 1, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 1, 'is_deep_scanned' => 0]
        );
        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'tile_x' => 1, 'tile_y' => 0])
            ->update(['tile_x' => null, 'tile_y' => null]);

        $this->actingAs($this->bart())
            ->postJson(route('colony.building.place'), ['building_id' => self::HOUSING_ID, 'q' => 1, 'r' => 0])
            ->assertOk()->assertJsonPath('ok', true);

        $placedInstance = (int) DB::table('colony_buildings')
            ->where(['colony_id' => self::COLONY_ID, 'building_id' => self::HOUSING_ID, 'tile_x' => 1, 'tile_y' => 0])
            ->value('instance_id');
        $this->assertSame($placedInstance, $this->lastEventParams('colony.building_placed')['instance_id']);
    }

    public function test_building_invested_event_records_instance_id(): void
    {
        $this->actingAs($this->bart())
            ->postJson(route('colony.building.invest'), ['building_id' => self::HOUSING_ID, 'instance_id' => 5])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(5, $this->lastEventParams('colony.building_invested')['instance_id']);
    }

    public function test_building_repaired_event_records_instance_id(): void
    {
        DB::table('colony_buildings')
            ->where(['colony_id' => self::COLONY_ID, 'building_id' => self::HOUSING_ID, 'instance_id' => 5])
            ->update(['status_points' => 10]);

        $this->actingAs($this->bart())
            ->postJson(route('colony.building.repair'), ['building_id' => self::HOUSING_ID, 'instance_id' => 5])
            ->assertOk()->assertJsonPath('ok', true);

        $this->assertSame(5, $this->lastEventParams('colony.building_repaired')['instance_id']);
    }

    public function test_building_level_down_event_records_instance_id(): void
    {
        DB::table('colony_buildings')
            ->where(['colony_id' => self::COLONY_ID, 'building_id' => self::HOUSING_ID, 'instance_id' => 4])
            ->update(['level' => 3, 'status_points' => 0.1]);

        Artisan::call('game:tick', ['--tick' => 11003]);

        $levelDowns = DB::table('colony_log')
            ->where('user', self::BART_USER_ID)
            ->where('event', 'techtree.level_down')
            ->where('tick', 11003)
            ->pluck('parameters')
            ->map(fn ($raw) => json_decode($raw, true))
            ->filter(fn ($p) => ($p['tech_id'] ?? null) === self::HOUSING_ID);

        $this->assertContains(4, $levelDowns->pluck('instance_id')->all());
    }
}
