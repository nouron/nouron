<?php

namespace Tests\Feature\Colony;

use App\Enums\BuildingId;
use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * T43-A2: finishing a Wohnhabitat (build or Ausbau) raises the supply cap / housing
 * immediately, not only at the next Sol.
 */
class HousingCapImmediateTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => true, 'game.bypass.supply_checks' => true]);
    }

    private function storedCap(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::BART_USER_ID)->value('supply');
    }

    private function syncCapAndPlace(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => BuildingId::Housing->value, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0]
        );
        DB::table('user_resources')->where('user_id', self::BART_USER_ID)->update([
            'supply' => app(ResourcesService::class)->calculateSupplyCap(self::COLONY_ID),
        ]);
    }

    private function completeOneLevel(): TestResponse
    {
        $apNeeded = (int) DB::table('buildings')->where('id', BuildingId::Housing->value)->value('ap_for_levelup');
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', BuildingId::Housing->value)
            ->update(['ap_spend' => max(0, $apNeeded - 1)]);

        return $this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->postJson(route('colony.building.invest'), [
                'building_id' => BuildingId::Housing->value,
                'instance_id' => 1,
            ]);
    }

    public function test_housing_upgrade_raises_cap_at_once(): void
    {
        $this->syncCapAndPlace(1);
        $before = $this->storedCap();
        $perLevel = (int) config('buildings.housingComplex.supply_cap');

        $res = $this->completeOneLevel();

        $res->assertOk()->assertJsonPath('leveled_up', true);
        $this->assertSame($before + $perLevel, $this->storedCap());
        $res->assertJsonPath('colonists.cap', $before + $perLevel);
    }

    public function test_housing_new_build_raises_cap_at_once(): void
    {
        $this->syncCapAndPlace(0);
        $before = $this->storedCap();
        $perLevel = (int) config('buildings.housingComplex.supply_cap');

        $res = $this->completeOneLevel();

        $res->assertOk()->assertJsonPath('leveled_up', true);
        $this->assertSame($before + $perLevel, $this->storedCap());
        $res->assertJsonPath('colonists.cap', $before + $perLevel);
    }
}
