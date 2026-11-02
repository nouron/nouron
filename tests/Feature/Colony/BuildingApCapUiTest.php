<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wiring of the per-Sol AP cap in the Ausbauen button (segmented AP bar, disabled state,
 * tooltip) in the Svelte frontend (ApUpgradeButton.svelte, colony +page.svelte). The
 * segment states themselves are computed client-side by apSegClasses() in
 * frontend/src/lib/js/ap-bar.js; verified in a real browser.
 */
class BuildingApCapUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function frontend(string $path): string
    {
        return file_get_contents(base_path('frontend/src/'.$path));
    }

    public function test_hexview_renders_capped_button_wiring_and_tooltip(): void
    {
        $button = $this->frontend('lib/components/ApUpgradeButton.svelte');

        $this->assertStringContainsString('class:tile-action-btn--capped={blocked}', $button);
        $this->assertStringContainsString("aria-disabled={blocked ? 'true' : undefined}", $button);
        $this->assertStringContainsString('role="tooltip"', $button);
        $this->assertStringContainsString("capTip = t('colony.ap_cap_tooltip')", $button);
        $this->assertNotSame('colony.ap_cap_tooltip', __('colony.ap_cap_tooltip'));
        $this->assertStringContainsString('bar.apSegClasses(row, i)', $button);
        // The colony page feeds the selected building row into the shared button.
        $this->assertStringContainsString('row={b}', $this->frontend('routes/(game)/colony/+page.svelte'));
    }

    public function test_hexview_building_rows_carry_ap_cap_key(): void
    {
        $buildings = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->getJson(route('colony.view'))->assertOk()->json('buildings');

        $this->assertNotEmpty($buildings);
        foreach ($buildings as $row) {
            $this->assertArrayHasKey('ap_cap', $row);
        }
    }

    public function test_client_script_handles_cap_error_code(): void
    {
        $page = $this->frontend('routes/(game)/colony/+page.svelte');
        $this->assertStringContainsString("res.error === 'building_ap_cap'", $page);

        $js = $this->frontend('lib/js/ap-bar.js');
        $this->assertStringContainsString('btn-seg--locked', $js);
        $this->assertStringContainsString('btn-seg--today', $js);
    }

    public function test_bar_renders_decorative_day_limit_marker(): void
    {
        $button = $this->frontend('lib/components/ApUpgradeButton.svelte');

        $this->assertStringContainsString('btn-seg-limit', $button);
        $this->assertStringContainsString('bar.apCapLimitPlace(row)', $button);
    }

    public function test_tooltip_is_not_gated_on_capped_state_only(): void
    {
        $button = $this->frontend('lib/components/ApUpgradeButton.svelte');
        $this->assertStringContainsString('class:tile-action-btn--hascap={tipOn}', $button);

        $css = $this->frontend('lib/styles/ap-upgrade-button.css');
        $this->assertStringContainsString('.tile-action-btn--hascap:hover .tile-action-btn__tip', $css);
        $this->assertStringContainsString('.btn-seg-limit', $css);
        $this->assertStringContainsString('apCapLimitPos(', $this->frontend('lib/js/ap-bar.js'));
    }
}
