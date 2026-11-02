<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Hangar JSON API (ADR 0006 stage 4c): same data as the Blade carousel, ship and
 * training actions on the existing controllers. Action logic is covered by the Blade
 * feature tests — here the wiring.
 */
class HangarApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_requires_token(): void
    {
        $this->getJson('/api/v1/hangar')->assertUnauthorized();
    }

    public function test_returns_hangar_data(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/hangar')->assertOk()->assertJsonStructure([
            'slots', 'pendingShips', 'shipTypes', 'hasPilot', 'shipCosts', 'consulApDiscount',
            'canUseNexusCredit', 'hasAktivierterKonsul', 'verfuegbareVerhandlungsAP',
            'commissionedShipIds', 'firstVisit', 'missionCatalog', 'hangarMaxLevel', 'maxShipStatus',
            'trainingFamilies', 'yieldPerLevelPct', 'trainingMaxLevel', 'trainingBuildingGate',
        ]);
    }

    public function test_actions_are_wired(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        // Empty body → controller validation (422), proving each route reaches its action.
        $this->postJson('/api/v1/colony/hangar/request', [])->assertUnprocessable();
        $this->postJson('/api/v1/colony/hangar/assign', [])->assertUnprocessable();
        $this->postJson('/api/v1/colony/path-depth/mission_family/nope/invest', [])->assertStatus(422);
        // Empty bay: the controller answers with its own JSON error, not a 404 route miss.
        foreach (['recall', 'repair'] as $action) {
            $res = $this->postJson("/api/v1/colony/hangar/99/{$action}");
            $this->assertNotSame(405, $res->status());
            $this->assertFalse((bool) $res->json('ok'));
        }
    }
}
