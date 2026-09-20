<?php

namespace Tests\Feature\Bar;

/**
 * BarController::resolveConcern() feature tests (Charakter-Anliegen, A41).
 *
 * Covered scenarios:
 *  AUTH GUARD
 *    - test_resolve_requires_auth
 *
 *  RESOLVE
 *    - test_resolve_returns_ok_and_resourcebar_sync_fields
 *    - test_resolve_returns_error_for_nonexistent_concern
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarConcernControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID_BART = 3;

    private const COLONY_ID_BART = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => false]);
    }

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function insertConcern(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID_BART,
            'character_slug' => 'mercenary',
            'created_tick' => 10,
            'expires_tick' => 20,
            'is_resolved' => false,
            'success' => null,
            'outcome' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_concerns')->insertGetId(array_merge($defaults, $overrides));
    }

    public function test_resolve_requires_auth(): void
    {
        $response = $this->postJson(route('colony.bar.resolve-concern', ['concern' => 1]));
        $response->assertStatus(401);
    }

    public function test_resolve_returns_ok_and_resourcebar_sync_fields(): void
    {
        $this->mockTick(10);
        $concernId = $this->insertConcern();

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.resolve-concern', ['concern' => $concernId]));

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'ap_available']);
    }

    public function test_resolve_returns_error_for_nonexistent_concern(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.resolve-concern', ['concern' => 999999]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }
}
