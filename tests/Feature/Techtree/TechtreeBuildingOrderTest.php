<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A43 "Techtree = Übersicht + Forschung": the techtree order endpoint only runs
 * research orders. Every building action lives in the colony view, ships in the
 * hangar — building and ship orders are rejected with `use_colony_view` and change
 * nothing. The instance/Rückbau semantics are covered on service level
 * (BuildingServiceInstanceOrderTest) and at the colony endpoint (BuildingLeveldownTest).
 */
class TechtreeBuildingOrderTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Placed, so the old code path would have accepted every order below.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 28)
            ->where('instance_id', 4)
            ->update(['tile_x' => 1, 'tile_y' => -1]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 31)
            ->update(['tile_x' => 2, 'tile_y' => -1]);
    }

    /**
     * @return array<string, array{string, int, array<string, mixed>}>
     */
    public static function rejectedOrders(): array
    {
        return [
            'building add' => ['building', 31, ['order' => 'add', 'ap' => 1]],
            'building levelup' => ['building', 31, ['order' => 'levelup']],
            'building leveldown' => ['building', 31, ['order' => 'leveldown']],
            'building leveldown with instance' => ['building', 28, ['order' => 'leveldown', 'instance_id' => 4]],
            'building repair' => ['building', 28, ['order' => 'repair', 'ap' => 1, 'instance_id' => 4]],
            'command center leveldown' => ['building', 25, ['order' => 'leveldown']],
            'ship add' => ['ship', 85, ['order' => 'add', 'ap' => 1]],
            'ship levelup' => ['ship', 85, ['order' => 'levelup']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('rejectedOrders')]
    public function test_building_and_ship_orders_point_to_the_colony_view(string $type, int $id, array $payload): void
    {
        $buildingsBefore = DB::table('colony_buildings')->orderBy('colony_id')->orderBy('building_id')->orderBy('instance_id')->get()->toArray();
        $shipsBefore = DB::table('colony_ships')->get()->toArray();

        $this->actingAs(User::find(self::USER_ID))
            ->postJson(route('techtree.order', ['type' => $type, 'id' => $id]), $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'use_colony_view')
            ->assertJsonPath('message', __('techtree.error_use_colony_view'));

        $this->assertEquals($buildingsBefore, DB::table('colony_buildings')->orderBy('colony_id')->orderBy('building_id')->orderBy('instance_id')->get()->toArray());
        $this->assertEquals($shipsBefore, DB::table('colony_ships')->get()->toArray());
    }

    public function test_use_colony_view_has_translations(): void
    {
        foreach (['de', 'en'] as $locale) {
            $key = 'techtree.error_use_colony_view';
            $this->assertNotSame($key, __($key, [], $locale), "Missing {$locale} translation for {$key}");
        }
    }

    public function test_instance_codes_are_no_longer_techtree_codes(): void
    {
        foreach (['de', 'en'] as $locale) {
            foreach (['instance_required', 'instance_not_found'] as $code) {
                $key = "techtree.error_{$code}";
                $this->assertSame($key, __($key, [], $locale), "{$key} is unused since A43 and should be removed");
            }
        }
    }
}
