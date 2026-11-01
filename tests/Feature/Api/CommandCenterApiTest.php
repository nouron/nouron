<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Kommandozentrale JSON API (ADR 0006 stage 4b): same data as the Blade dashboard,
 * stipend and Nexus import actions on the ColonyController. Action logic is covered
 * by the Blade feature tests — here the wiring.
 */
class CommandCenterApiTest extends TestCase
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
        $this->getJson('/api/v1/command-center')->assertUnauthorized();
    }

    public function test_returns_dashboard_data(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/command-center')->assertOk()->assertJsonStructure([
            'colonists' => ['colonists', 'employed', 'free', 'unfilled'],
            'phaseProgress' => ['phase'],
            'stipendTiers' => [0 => ['key', 'cost', 'trust']],
            'solLimit', 'currentSol', 'nexusDebt', 'nexusDebtMax', 'nexusDebtTone',
            'totalBuildings', 'damagedBuildings', 'lastSolDeltas', 'advisors',
            'trustEvents', 'trustEventsSum', 'trustEventCap', 'uplinkLevel',
            'nexusImport' => ['resources', 'advantage_view' => ['lines', 'hints']],
        ]);
    }

    public function test_actions_are_wired(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        // Empty body → controller validation (422), proving each route reaches its action.
        foreach (['colony/stipend', 'colony/nexus/import-compounds', 'colony/nexus/import-delayed'] as $path) {
            $this->postJson("/api/v1/$path", [])->assertUnprocessable();
        }
    }
}
