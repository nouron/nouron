<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Colony hex view JSON API (ADR 0006 stage 3): same data and actions as the Blade
 * hex view, reached with a Sanctum bearer token. Action logic itself is covered by
 * the Blade feature tests — here only the wiring.
 *
 * Fixture colony 1 (Bart, user 3), active started run.
 */
class ColonyApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_colony_requires_token(): void
    {
        $this->getJson('/api/v1/colony')->assertUnauthorized();
    }

    public function test_colony_returns_hexview_data(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $response = $this->getJson('/api/v1/colony')->assertOk();

        $response->assertJsonStructure([
            'tiles' => [0 => ['q', 'r', 'tile_type']],
            'colony' => ['id', 'name'],
            'ccLevel', 'buildingCcLevelOffset', 'ccGateTemplate', 'ccBuildingId',
            'buildings', 'buildingCatalog', 'apAvailable', 'regolith', 'werkstoffe', 'organika',
            'freeSupply', 'trust', 'currentSol', 'solLimit', 'activeHint', 'merchantVisit',
            'merchantItems', 'exploreCostPerRing', 'exploreCostDefault', 'tileYields',
            'repairDisplayThreshold', 'damagedThresholdPct', 'criticalThresholdPct',
            'deepScanAp', 'relocateApPerHex', 'phaseProgress', 'regolithFallbackTiles', 'supplyCapFull', 'findTotal',
        ]);
        $this->assertSame(__('techtree.building_commandCenter'), $response->json('buildingCatalog.building_commandCenter.label'));
        $this->assertNotEmpty($response->json('buildingCatalog.building_commandCenter.tooltip.description'));
    }

    public function test_available_buildings(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/colony/buildings/available')->assertOk();
    }

    public function test_actions_are_wired_to_the_controller(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        // Empty body → controller validation (422), proving each route reaches its action.
        foreach (['tile/explore', 'tile/deep-scan', 'tile/salvage', 'building/place', 'building/invest', 'building/repair', 'building/leveldown'] as $path) {
            $this->postJson("/api/v1/colony/$path", [])->assertUnprocessable();
        }
        $this->getJson('/api/v1/colony/building/leveldown-preview')->assertUnprocessable();
        $this->postJson('/api/v1/colony/hangar/1/dispatch', [])->assertUnprocessable();
    }
}
