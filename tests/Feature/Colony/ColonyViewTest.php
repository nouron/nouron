<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_hexview_contains_sidebar_and_repair_wiring(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        // Sidebar context-title header + both action endpoints are wired into the page.
        $response->assertSee('tile-panel-title', false);
        $response->assertSee(route('colony.building.repair'), false);
        $response->assertSee(route('colony.building.invest'), false);
    }

    public function test_hexview_renders_ap_cost_chips_on_action_buttons(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        // AP-cost chips (game-wide convention) sit inside the action buttons.
        $response->assertSee('ap-cost-chip', false);
    }

    public function test_hexview_passes_resource_amounts_for_build_chip_affordability(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        $response->assertSee('regolith:', false);
        $response->assertSee('werkstoffe:', false);
        $response->assertSee('freeSupply:', false);
    }

    public function test_hexview_renders_hint_completion_animation_markup(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();
        $response->assertSee('hint-bar-stack', false);
        $response->assertSee('x-show="completedHint"', false);
        $response->assertSee('x-ref="hintBar"', false);
    }

    public function test_pending_run_redirects_to_lobby(): void
    {
        // A pending run is active but not yet started (started_at = null).
        DB::table('runs')
            ->where('user_id', self::BART_USER_ID)
            ->where('status', 'active')
            ->update(['started_at' => null]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertRedirect(route('lobby'));
    }
}
