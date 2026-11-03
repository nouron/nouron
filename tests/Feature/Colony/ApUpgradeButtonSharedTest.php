<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One shared "upgrade button with segmented AP bar" (frontend ApUpgradeButton.svelte +
 * lib/js/ap-bar.js + lib/styles/ap-upgrade-button.css) renders both the colony
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

    private function frontend(string $path): string
    {
        return file_get_contents(base_path('frontend/src/'.$path));
    }

    private function button(): string
    {
        return $this->frontend('lib/components/ApUpgradeButton.svelte');
    }

    public function test_shared_component_exists_and_wires_shared_logic_and_styles(): void
    {
        $this->assertFileExists(base_path('frontend/src/lib/components/ApUpgradeButton.svelte'));
        $button = $this->button();

        $this->assertStringContainsString('ap-upgrade-btn', $button);
        $this->assertStringContainsString('btn-seg-limit', $button);
        $this->assertStringContainsString('role="tooltip"', $button);
        $this->assertStringContainsString("import '#lib/js/ap-bar.js'", $button);
        $this->assertStringContainsString("import '#lib/styles/ap-upgrade-button.css'", $button);
    }

    public function test_both_screens_render_the_shared_button(): void
    {
        foreach (['routes/(game)/colony/+page.svelte', 'routes/(game)/techtree/+page.svelte'] as $page) {
            $svelte = $this->frontend($page);
            $this->assertStringContainsString("import ApUpgradeButton from '#lib/components/ApUpgradeButton.svelte'", $svelte, $page);
            $this->assertStringContainsString('<ApUpgradeButton', $svelte, $page);
        }
    }

    public function test_techtree_knowledge_uses_bar_button_not_old_segments(): void
    {
        $techtree = $this->frontend('routes/(game)/techtree/+page.svelte');

        $this->assertStringNotContainsString('ap-seg--done', $techtree);
        $this->assertStringContainsString('onclick={() => investAp(tech)}', $techtree);
        $this->assertStringContainsString('chip="research"', $techtree);
    }

    public function test_bar_logic_and_styles_live_only_in_the_shared_files(): void
    {
        $shared = $this->frontend('lib/js/ap-bar.js');
        $hexgrid = $this->frontend('lib/js/colony-hexgrid.js');
        $colonyPage = $this->frontend('routes/(game)/colony/+page.svelte');
        foreach (['apCapReached', 'apSegClasses', 'apCapLimitPos'] as $fn) {
            $this->assertStringContainsString($fn, $shared);
            $this->assertStringNotContainsString($fn, $hexgrid);
            $this->assertStringNotContainsString($fn, $colonyPage);
        }

        $css = $this->frontend('lib/styles/ap-upgrade-button.css');
        $colonyCss = $this->frontend('lib/styles/colony-view.css').$this->frontend('lib/styles/colony-shared.css');
        $techtreeCss = $this->frontend('lib/styles/techtree-view.css');
        foreach (['.btn-seg--today', '.btn-seg--locked', '.btn-seg-limit', '.tile-action-btn__tip'] as $sel) {
            $this->assertStringContainsString($sel, $css);
            $this->assertStringNotContainsString($sel, $colonyCss);
            $this->assertStringNotContainsString($sel, $techtreeCss);
        }
        $this->assertStringNotContainsString('.ap-seg', $techtreeCss);
    }

    public function test_red_primary_variant_has_dedicated_bar_styles(): void
    {
        $css = $this->frontend('lib/styles/ap-upgrade-button.css');

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
        $js = $this->frontend('lib/js/ap-bar.js');

        // Level start (ap_spend 0): plain bar and no marker; reached-state stays independent.
        // Knowledge opts in via cap_at_start (T49) and shows it from the start.
        $this->assertStringContainsString('if (!cap || (spend === 0 && !row.cap_at_start))', $js);
        $this->assertStringContainsString('row.ap_spend === 0 && !row.cap_at_start', $js);
        $this->assertMatchesRegularExpression('/apCapReached\(row\) \{\s*return !!row\?\.ap_cap && row\.ap_cap\.remaining <= 0;/', $js);
    }

    public function test_gate_blocked_state_with_reason_precedence_over_cap_tooltip(): void
    {
        $js = $this->frontend('lib/js/ap-bar.js');
        $this->assertStringContainsString('apCapTipApplies', $js);

        $button = $this->button();
        // A gate reason blocks the button and switches the tooltip on.
        $this->assertStringContainsString('const blocked = $derived(!!gateReason || bar.apCapReached(row) || extraBlocked);', $button);
        $this->assertStringContainsString('const tipOn = $derived(!!gateReason || bar.apCapTipApplies(row));', $button);
        // Gate reason wins over the cap text in the tooltip.
        $this->assertStringContainsString('{gateReason || capTip}', $button);

        $colonyPage = $this->frontend('routes/(game)/colony/+page.svelte');
        $this->assertStringContainsString('gateReason={ccGateReason(b)}', $colonyPage);
    }

    public function test_gate_tooltip_text_is_the_servers_gate_error_text(): void
    {
        $response = $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->getJson(route('colony.view'))->assertOk();

        $response->assertJsonPath('ccGateTemplate', __('colony.error_building_cc_gate', ['level' => ':level']));
        $response->assertJsonPath('buildingCcLevelOffset', (int) config('game.building_cc_level_offset', 0));
        $this->assertStringContainsString("d.ccGateTemplate.replace(':level'", $this->frontend('routes/(game)/colony/+page.svelte'));
        // Single rule definition: the server uses the same lang key and config offset.
        $controller = file_get_contents(app_path('Http/Controllers/Colony/ColonyController.php'));
        $this->assertStringContainsString("__('colony.error_building_cc_gate'", $controller);
    }
}
