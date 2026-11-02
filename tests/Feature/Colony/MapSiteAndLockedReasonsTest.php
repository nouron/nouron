<?php

namespace Tests\Feature\Colony;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T43-C5 (construction marker on the hex map) and T43-C8 (one reason chip per missing
 * build requirement). Reasons/markers are computed client-side from live state: the reason
 * list in the colony +page.svelte, the map marker in frontend/src/lib/js/colony-hexgrid.js.
 */
class MapSiteAndLockedReasonsTest extends TestCase
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

    private function colonyPage(): string
    {
        return $this->frontend('routes/(game)/colony/+page.svelte');
    }

    public function test_build_list_renders_reason_chips_with_missing_cause_only(): void
    {
        $page = $this->colonyPage();

        $this->assertStringContainsString('buildMissingReasons(b)', $page);
        $this->assertStringContainsString('building-list-reason--missing', $page);
        $this->assertStringContainsString('building-list-reasons', $page);
    }

    public function test_missing_colonists_reason_is_colonists_not_regolith(): void
    {
        $page = $this->colonyPage();
        $this->assertStringContainsString('function buildMissingReasons(b: AvailableBuilding)', $page);
        $this->assertStringContainsString("kind: 'colonists'", $page);
        $this->assertStringContainsString("kind: 'regolith'", $page);
        // Each reason is gated on its own shortfall.
        $this->assertMatchesRegularExpression('/supply_cost[^;]*>\s*data\.freeSupply[^}]*kind: \'colonists\'/s', $page);
        $this->assertMatchesRegularExpression('/\[3\][^;]*>\s*data\.regolith[^}]*kind: \'regolith\'/s', $page);

        foreach (['de', 'en'] as $loc) {
            $lang = require lang_path("$loc/colony.php");
            foreach (['reason_colonists', 'reason_regolith', 'reason_werkstoffe', 'reason_ap', 'site_marker_tooltip'] as $k) {
                $this->assertArrayHasKey($k, $lang, "$loc missing $k");
            }
        }
    }

    public function test_site_marker_outline_does_not_depend_on_css_for_its_fill(): void
    {
        // An SVG polygon without fill defaults to black when the stylesheet is stale or missing.
        $js = $this->frontend('lib/js/colony-hexgrid.js');
        $this->assertMatchesRegularExpression("/dash\.setAttribute\('fill', 'none'\)/", $js);
        $this->assertMatchesRegularExpression("/dash\.setAttribute\('stroke', '#f59e0b'\)/", $js);
    }

    public function test_map_marks_construction_sites_with_tooltip(): void
    {
        $js = $this->frontend('lib/js/colony-hexgrid.js');
        $this->assertStringContainsString('tile-site-marker', $js);
        $this->assertStringContainsString('isConstructionSite', $js);
        $this->assertStringContainsString('opts.siteMarkerTooltip', $js);
        $this->assertStringContainsString('tile-site-marker', $this->frontend('lib/styles/colony-view.css'));
        $this->assertStringContainsString("siteMarkerTooltip: t('colony.site_marker_tooltip')", $this->colonyPage());
    }
}
