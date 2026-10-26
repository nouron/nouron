<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T43-C5 (construction marker on the hex map) and T43-C8 (one reason chip per missing
 * build requirement). Reasons/markers are computed client-side from live state; the
 * server renders the wiring, the lang strings and the reason markup.
 */
class MapSiteAndLockedReasonsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function html(): string
    {
        return $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->get(route('colony.view'))->assertOk()->getContent();
    }

    public function test_build_list_renders_reason_chips_with_missing_cause_only(): void
    {
        $html = $this->html();

        $this->assertStringContainsString('buildMissingReasons(b)', $html);
        $this->assertStringContainsString('building-list-reason--missing', $html);
        $this->assertStringContainsString('building-list-reasons', $html);
    }

    public function test_missing_colonists_reason_is_colonists_not_regolith(): void
    {
        $js = file_get_contents(public_path('js/colony-hexgrid.js'));
        $this->assertStringContainsString('buildMissingReasons(b)', $js);
        $this->assertStringContainsString("kind: 'colonists'", $js);
        $this->assertStringContainsString("kind: 'regolith'", $js);
        // Each reason is gated on its own shortfall.
        $this->assertMatchesRegularExpression('/supply_cost[^;]*>\s*this\.freeSupply[^}]*kind: \'colonists\'/s', $js);
        $this->assertMatchesRegularExpression('/\[3\][^;]*>\s*this\.regolith[^}]*kind: \'regolith\'/s', $js);

        foreach (['de', 'en'] as $loc) {
            $lang = require lang_path("$loc/colony.php");
            foreach (['reason_colonists', 'reason_regolith', 'reason_werkstoffe', 'reason_ap', 'site_marker_tooltip'] as $k) {
                $this->assertArrayHasKey($k, $lang, "$loc missing $k");
            }
        }
    }

    public function test_map_marks_construction_sites_with_tooltip(): void
    {
        $js = file_get_contents(public_path('js/colony-hexgrid.js'));
        $this->assertStringContainsString('tile-site-marker', $js);
        $this->assertStringContainsString('isConstructionSite', $js);
        $this->assertStringContainsString('tile-site-marker', file_get_contents(public_path('css/colony.css')));
        $this->assertStringContainsString('siteMarkerTooltip', $this->html());
    }
}
