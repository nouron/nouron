<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\ResourcesService;
use App\Services\Techtree\BuildingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * T43-G9 (Option A2): housing loss lowers the colonists at once; unfilled
 * workplaces cut raw-material production by the employed share; the build gate
 * stays closed until enough free colonists are back. No grace period, no
 * departure, no "Wegschicken" — and no stored state for any of it.
 */
class HousingLossTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HARVESTER = 27;

    private const HOUSING = 28;

    private const SCIENCELAB = 31;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.supply_checks' => false]);
    }

    private function resources(): ResourcesService
    {
        return $this->app->make(ResourcesService::class);
    }

    private function setCap(int $cap): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $cap]);
    }

    private function workplaces(): int
    {
        $b = $this->resources()->getSupplyBreakdown(self::COLONY_ID);

        return $b['cap'] - $b['free'];
    }

    public function test_housing_leveldown_lowers_the_colonists_at_once(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::HOUSING)->where('instance_id', 1)->update(['level' => 2, 'tile_x' => 1, 'tile_y' => -1]);
        $this->resources()->syncSupplyCap(self::COLONY_ID);
        $before = $this->resources()->colonistsOverview(self::COLONY_ID)['colonists'];

        $this->assertTrue($this->app->make(BuildingService::class)->leveldown(self::COLONY_ID, self::HOUSING, 1));

        $perLevel = (int) config('buildings.housingComplex.supply_cap');
        $this->assertSame($before - $perLevel, $this->resources()->colonistsOverview(self::COLONY_ID)['colonists'], 'no waiting for the next Sol');
    }

    public function test_unfilled_workplaces_cut_production_by_the_employed_share(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 20]);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 12]);

        $this->assertSame(0.6, $this->resources()->staffingShare(self::COLONY_ID));
        $this->assertSame(8, $this->resources()->colonistsOverview(self::COLONY_ID)['unfilled']);

        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 30]);
        $this->assertSame(1.0, $this->resources()->staffingShare(self::COLONY_ID), 'free colonists do not boost production');
    }

    public function test_build_gate_stays_closed_until_enough_free_colonists_are_back(): void
    {
        DB::table('buildings')->update(['supply_cost' => 0]);
        DB::table('researches')->update(['supply_cost' => 0]);
        DB::table('buildings')->where('id', self::HARVESTER)->update(['supply_cost' => 20]);
        DB::table('buildings')->where('id', self::SCIENCELAB)->update(['supply_cost' => 3]);
        $service = $this->app->make(BuildingService::class);

        $workplaces = $this->workplaces();

        $this->setCap($workplaces - 8); // 8 unfilled
        $this->assertFalse($service->checkRequiredSupplyByEntityId(self::COLONY_ID, self::SCIENCELAB));

        $this->setCap($workplaces + 2); // 2 free < 3
        $this->assertFalse($service->checkRequiredSupplyByEntityId(self::COLONY_ID, self::SCIENCELAB));

        $this->setCap($workplaces + 3); // 3 free
        $this->assertTrue($service->checkRequiredSupplyByEntityId(self::COLONY_ID, self::SCIENCELAB));
    }

    public function test_rueckbau_preview_names_colonists_lost_and_unfilled_workplaces(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::HOUSING)->where('instance_id', 1)->update(['level' => 2, 'tile_x' => 1, 'tile_y' => -1]);
        $this->resources()->syncSupplyCap(self::COLONY_ID);
        $cap = $this->resources()->colonistsOverview(self::COLONY_ID)['colonists'];
        $perLevel = (int) config('buildings.housingComplex.supply_cap');
        // Workplaces fill the cap exactly: the loss leaves $perLevel workplaces unfilled.
        DB::table('buildings')->where('id', self::HARVESTER)->increment('supply_cost', $this->resources()->colonistsOverview(self::COLONY_ID)['free']);
        $this->assertSame(0, $this->resources()->colonistsOverview(self::COLONY_ID)['free']);

        $this->actingAs(User::where('user_id', self::USER_ID)->firstOrFail())
            ->getJson(route('colony.building.leveldown-preview', ['building_id' => self::HOUSING, 'instance_id' => 1]))
            ->assertOk()
            ->assertJsonPath('colonists_lost', $perLevel)
            ->assertJsonPath('unfilled_now', 0)
            ->assertJsonPath('unfilled_after', $perLevel)
            ->assertJsonMissingPath('homeless_after')
            ->assertJsonMissingPath('capacity_loss');
        $this->assertSame($cap, $this->resources()->colonistsOverview(self::COLONY_ID)['colonists'], 'preview changes nothing');
    }

    public function test_grace_period_departure_and_dismissal_are_gone(): void
    {
        $this->assertFileDoesNotExist(app_path('Services/OvercapService.php'));
        $this->assertFalse(Schema::hasColumn('glx_colonies', 'overcap_departed'));
        $this->assertFalse(Schema::hasColumn('glx_colonies', 'overcap_streak'));
        $this->assertNull(config('game.overcap'));
        $this->assertNull(config('game.trust.events.colonists_left'));
        $this->assertNull(config('game.trust.events.colonists_dismissed'));
        $this->assertFalse(Route::has('colony.colonists.dismiss'));
    }
}
