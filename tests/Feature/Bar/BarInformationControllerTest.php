<?php

namespace Tests\Feature\Bar;

/**
 * BarController::resolveInformationEncounter() feature tests
 * (Deva & Lenn Vier-Ausgänge-Pool, A42).
 *
 * Covered scenarios:
 *  AUTH GUARD
 *    - test_resolve_requires_auth
 *
 *  RESOLVE
 *    - test_resolve_returns_ok
 *    - test_resolve_returns_error_for_nonexistent_encounter
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarInformationControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID_BART = 3;

    private const COLONY_ID_BART = 1;

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

    private function insertEncounter(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID_BART,
            'character_slug' => 'veteran',
            'outcome_key' => 'narrative_1',
            'created_tick' => 10,
            'expires_tick' => 20,
            'is_resolved' => false,
            'outcome' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_information_encounters')->insertGetId(array_merge($defaults, $overrides));
    }

    public function test_resolve_requires_auth(): void
    {
        $response = $this->postJson(route('colony.bar.resolve-information', ['encounter' => 1]));
        $response->assertStatus(401);
    }

    public function test_resolve_returns_ok(): void
    {
        $this->mockTick(10);
        $encounterId = $this->insertEncounter();

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.resolve-information', ['encounter' => $encounterId]));

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'character_slug', 'outcome_key']);
    }

    public function test_resolve_returns_error_for_nonexistent_encounter(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.resolve-information', ['encounter' => 999999]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error', 'message'])
            ->assertJsonPath('error', 'bar_information_not_found')
            ->assertJsonPath('message', __('colony.bar_information_not_found'));
    }
}
