<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Advisor screen JSON API (ADR 0006 stage 4a): same controller as the Blade carousel.
 * Hire/fire/promote logic is covered by the Blade feature tests — here the wiring.
 *
 * Fixture colony 1 (Bart, user 3), active started run.
 */
class AdvisorApiTest extends TestCase
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
        $this->getJson('/api/v1/advisors')->assertUnauthorized();
    }

    public function test_returns_slots(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/advisors')->assertOk()->assertJsonStructure([
            'slots' => [0 => ['position', 'key', 'name', 'state', 'hire_cost', 'junior_ap', 'advisor']],
            'slotInfo' => ['used', 'max', 'cc_level'],
            'junior_upkeep',
        ]);
    }

    public function test_fire_hire_and_promote_are_wired(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        $advisor = DB::table('advisors')->where('user_id', self::USER_ID)->first();
        $this->assertNotNull($advisor);

        $this->deleteJson("/api/v1/advisors/{$advisor->id}")->assertOk()->assertJsonPath('ok', true);
        $this->assertNull(DB::table('advisors')->where('id', $advisor->id)->value('colony_id'));

        // Same personell again in the same Sol → controller's own rule, as JSON.
        $this->postJson('/api/v1/advisors/hire', ['personell_id' => $advisor->personell_id])
            ->assertUnprocessable()->assertJsonPath('ok', false);

        $this->postJson('/api/v1/advisors/999999/promote')->assertNotFound();
    }
}
