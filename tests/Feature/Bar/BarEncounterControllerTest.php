<?php

namespace Tests\Feature\Bar;

/**
 * BarController::acceptEncounter feature tests (Cantina-Begegnungspool, A35).
 *
 * Covered scenarios:
 *  - test_accept_encounter_requires_auth
 *  - test_accept_encounter_returns_json_ok
 *  - test_accept_encounter_returns_error_for_nonexistent_encounter
 *  - test_accept_encounter_does_not_allow_foreign_colony_encounter
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarEncounterControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID_BART = 3;

    private const COLONY_ID_BART = 1;

    private const USER_ID_HOMER = 2;

    private const RES_ORGANICS = 5;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function insertEncounter(int $colonyId, array $overrides = []): int
    {
        $defaults = [
            'colony_id' => $colonyId,
            'type' => 'auction',
            'give_resource_id' => self::RES_ORGANICS,
            'give_amount' => 10,
            'win_chance' => null,
            'credits_amount' => 20,
            'duration_ticks' => null,
            'expires_tick' => 9999,
            'is_accepted' => false,
            'resolved' => false,
            'won' => null,
            'ends_tick' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_encounters')->insertGetId(array_merge($defaults, $overrides));
    }

    private function setColonyResource(int $colonyId, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $colonyId, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    public function test_accept_encounter_requires_auth(): void
    {
        $response = $this->post(route('colony.bar.accept-encounter', ['encounter' => 1]));
        $response->assertRedirect(route('login'));
    }

    public function test_accept_encounter_returns_json_ok(): void
    {
        $this->mockTick(10);
        $this->setColonyResource(self::COLONY_ID_BART, self::RES_ORGANICS, 100);
        $id = $this->insertEncounter(self::COLONY_ID_BART);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept-encounter', ['encounter' => $id]));

        $response->assertOk()->assertJson(['ok' => true]);
    }

    public function test_accept_encounter_returns_error_for_nonexistent_encounter(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept-encounter', ['encounter' => 9999]));

        $response->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_accept_encounter_does_not_allow_foreign_colony_encounter(): void
    {
        $this->mockTick(10);
        $foreignColonyId = 2; // Homer's colony
        $id = $this->insertEncounter($foreignColonyId);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept-encounter', ['encounter' => $id]));

        $response->assertStatus(422)->assertJson(['ok' => false]);
    }
}
