<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JsonController had 0% test coverage — this is also a regression guard for the
 * larastan fix (2026-07-21) where the constructor called parent::__construct()
 * without the required TickService, which would have thrown on every request.
 */
class JsonControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    public function test_get_resources_returns_all_resource_types_keyed_by_id(): void
    {
        $response = $this->actingAs($this->user())->getJson(route('resources.index'));

        $response->assertOk();
        $response->assertJsonPath('3.name', 'res_regolith');
    }

    public function test_get_colony_resources_returns_amounts_keyed_by_resource_id(): void
    {
        $response = $this->actingAs($this->user())
            ->getJson(route('resources.colony', ['id' => self::COLONY_ID]));

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey(3, $json);
        $this->assertSame(3, $json[3]['resource_id']);
        $this->assertArrayHasKey('amount', $json[3]);
    }

    public function test_reload_resourcebar_renders_partial_with_merged_metadata(): void
    {
        $response = $this->actingAs($this->user())->get(route('resources.resourcebar'));

        $response->assertOk();
        $response->assertHeader('X-IC-Refresh', 'false');
        $response->assertSee('Rg', false); // regolith icon label from resources.name
    }
}
