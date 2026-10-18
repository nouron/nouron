<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One shared "upgrade button with segmented AP bar" (partials/ap-upgrade-button.blade.php +
 * public/js/ap-bar.js + public/css/ap-upgrade-button.css) renders both the colony
 * "Ausbauen" button and the techtree Kenntnis level-up button. No duplicated bar
 * logic or styles may remain in the screen-specific files.
 */
class ApUpgradeButtonSharedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function page(string $route): string
    {
        return $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route($route))->assertOk()->getContent();
    }

    public function test_shared_partial_exists_and_old_bar_partial_is_gone(): void
    {
        $this->assertFileExists(resource_path('views/partials/ap-upgrade-button.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/colony/partials/ap-bar.blade.php'));
    }

    public function test_both_screens_render_the_shared_button(): void
    {
        foreach (['colony.view', 'techtree.index'] as $route) {
            $html = $this->page($route);
            $this->assertStringContainsString('ap-upgrade-btn', $html, $route);
            $this->assertStringContainsString('btn-seg-limit', $html, $route);
            $this->assertStringContainsString('role="tooltip"', $html, $route);
            $this->assertStringContainsString('js/ap-bar.js', $html, $route);
            $this->assertStringContainsString('css/ap-upgrade-button.css', $html, $route);
        }
    }

    public function test_techtree_knowledge_uses_bar_button_not_old_segments(): void
    {
        $html = $this->page('techtree.index');
        $this->assertStringNotContainsString('ap-seg--done', $html);
        $this->assertStringContainsString("investAp(selectedTech, 'research', 1)", html_entity_decode($html, ENT_QUOTES));
    }

    public function test_bar_logic_and_styles_live_only_in_the_shared_files(): void
    {
        $shared = file_get_contents(public_path('js/ap-bar.js'));
        foreach (['apCapReached', 'apSegClasses', 'apCapLimitPos'] as $fn) {
            $this->assertStringContainsString($fn, $shared);
            $this->assertStringNotContainsString($fn.'(building'.($fn === 'apSegClasses' ? ', i) {' : ') {'), file_get_contents(public_path('js/colony-hexgrid.js')));
        }

        $css = file_get_contents(public_path('css/ap-upgrade-button.css'));
        foreach (['.btn-seg--today', '.btn-seg--locked', '.btn-seg-limit', '.tile-action-btn__tip'] as $sel) {
            $this->assertStringContainsString($sel, $css);
            $this->assertStringNotContainsString($sel.' ', file_get_contents(public_path('css/colony.css')));
            $this->assertStringNotContainsString($sel, file_get_contents(public_path('css/techtree-view.css')));
        }
        $this->assertStringNotContainsString('.ap-seg', file_get_contents(public_path('css/techtree-view.css')));
    }

    public function test_red_primary_variant_has_dedicated_bar_styles(): void
    {
        $css = file_get_contents(public_path('css/ap-upgrade-button.css'));

        foreach (['', '--filled', '--today', '--free', '--locked'] as $state) {
            $this->assertStringContainsString(
                '.ap-upgrade-btn:not(.tile-action-btn--secondary) .btn-seg'.$state,
                $css
            );
        }
        $this->assertStringContainsString('.ap-upgrade-btn:not(.tile-action-btn--secondary) .btn-seg-limit', $css);
        // The capped red button keeps its red background (no pale pink that hides the bar).
        $this->assertStringNotContainsString('#e8c4bf', $css);
    }

    public function test_cap_decoration_only_shows_once_ap_was_invested(): void
    {
        $js = file_get_contents(public_path('js/ap-bar.js'));

        // Level start (ap_spend 0): plain bar and no marker; reached-state stays independent.
        $this->assertStringContainsString('if (!cap || spend === 0)', $js);
        $this->assertStringContainsString('row.ap_spend === 0', $js);
        $this->assertMatchesRegularExpression('/apCapReached\(row\) \{\s*return !!row\?\.ap_cap && row\.ap_cap\.remaining <= 0;/', $js);
    }

    public function test_gate_blocked_state_with_reason_precedence_over_cap_tooltip(): void
    {
        $js = file_get_contents(public_path('js/ap-bar.js'));
        $this->assertStringContainsString('apCapTipApplies', $js);

        $partial = file_get_contents(resource_path('views/partials/ap-upgrade-button.blade.php'));
        $this->assertStringContainsString('gateReasonExpr', $partial);
        // Gate reason wins over the cap text in the tooltip.
        $this->assertMatchesRegularExpression('/\{\{ \$gate \}\} \|\| /', $partial);

        $hex = file_get_contents(public_path('js/colony-hexgrid.js'));
        $this->assertStringContainsString('ccGateReason(building)', $hex);
    }

    public function test_gate_tooltip_text_is_the_servers_gate_error_text(): void
    {
        $html = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();

        $text = __('colony.error_building_cc_gate', ['level' => ':level']);
        $this->assertStringContainsString(json_encode($text), $html);
        $this->assertStringContainsString('buildingCcLevelOffset', $html);
        // Single rule definition: the server uses the same lang key and config offset.
        $controller = file_get_contents(app_path('Http/Controllers/Colony/ColonyController.php'));
        $this->assertStringContainsString("__('colony.error_building_cc_gate'", $controller);
    }
}
