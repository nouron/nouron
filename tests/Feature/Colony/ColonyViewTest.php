<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Colony hex-view render smoke test — GET colony/view.
 *
 * Guards that the sidebar tab markup + Alpine bindings render server-side
 * without error and that the key wiring (repair endpoint, tab body, Alpine
 * component) is present in the output.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class ColonyViewTest extends TestCase
{
    use RefreshDatabase;

    private const BART_USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    public function test_hexview_renders_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        $response->assertSee('colonyHexView(window.__colonyViewData)', false);
    }

    public function test_hexview_contains_sidebar_tab_and_repair_wiring(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        // Sidebar tab container + both action endpoints are wired into the page.
        $response->assertSee('tile-tab-body', false);
        $response->assertSee(route('colony.building.repair'), false);
        $response->assertSee(route('colony.building.invest'), false);
    }
}
