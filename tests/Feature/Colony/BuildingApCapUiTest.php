<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Server-rendered wiring of the per-Sol AP cap in the Ausbauen button (segmented AP bar,
 * disabled state, tooltip). The segment states themselves are computed client-side by
 * apSegClasses() in public/js/colony-hexgrid.js; verified in a real browser.
 */
class BuildingApCapUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_hexview_renders_capped_button_wiring_and_tooltip(): void
    {
        $html = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('tile-action-btn--capped', $html);
        $this->assertStringContainsString('aria-disabled', $html);
        $this->assertStringContainsString('role="tooltip"', $html);
        $this->assertStringContainsString(__('colony.ap_cap_tooltip'), $html);
        $this->assertStringContainsString('apSegClasses(selectedBuilding, i)', $html);
    }

    public function test_hexview_building_rows_carry_ap_cap_key(): void
    {
        $html = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('ap_cap', $html);
    }

    public function test_client_script_handles_cap_error_code(): void
    {
        $js = file_get_contents(public_path('js/colony-hexgrid.js'));
        $this->assertStringContainsString("'building_ap_cap'", $js);

        $js = file_get_contents(public_path('js/ap-bar.js'));
        $this->assertStringContainsString('btn-seg--locked', $js);
        $this->assertStringContainsString('btn-seg--today', $js);
    }

    public function test_bar_renders_decorative_day_limit_marker(): void
    {
        $html = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('btn-seg-limit', $html);
        $this->assertStringContainsString('apCapLimitPos(selectedBuilding)', $html);
    }

    public function test_tooltip_is_not_gated_on_capped_state_only(): void
    {
        $html = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('tile-action-btn--hascap', $html);
        $css = file_get_contents(public_path('css/ap-upgrade-button.css'));
        $this->assertStringContainsString('.tile-action-btn--hascap:hover .tile-action-btn__tip', $css);
        $this->assertStringContainsString('.btn-seg-limit', $css);
        $this->assertStringContainsString('apCapLimitPos(', file_get_contents(public_path('js/ap-bar.js')));
    }
}
