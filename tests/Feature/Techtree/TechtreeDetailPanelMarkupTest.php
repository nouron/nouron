<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A43 — techtree detail panel for buildings (GDD techtree §11.4): the instance
 * line counts placed instances against max_instances (never max_level), the
 * read-only instance list deep-links into the colony view, and "In der Kolonie
 * errichten" is only offered while another instance is possible (canBuildMore()
 * in public/js/techtree-view.js).
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). Housing (28): max_level 3,
 * max_instances 6.
 */
class TechtreeDetailPanelMarkupTest extends TestCase
{
    use RefreshDatabase;

    private const BART_USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function techtree(): TestResponse
    {
        return $this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->get(route('techtree.index'));
    }

    private function placeHousing(array $instanceTiles): void
    {
        DB::table('colony_buildings')
            ->where(['colony_id' => self::COLONY_ID, 'building_id' => 28])
            ->update(['tile_x' => null, 'tile_y' => null]);
        foreach ($instanceTiles as $instanceId => [$q, $r]) {
            DB::table('colony_buildings')->updateOrInsert(
                ['colony_id' => self::COLONY_ID, 'building_id' => 28, 'instance_id' => $instanceId],
                ['level' => 2, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => $q, 'tile_y' => $r],
            );
        }
    }

    /** The tech card chip of an instanced building: "placed / max_instances". */
    public function test_card_chip_counts_placed_instances_against_max_instances(): void
    {
        $this->placeHousing([1 => [0, 1], 4 => [1, -1]]);

        $html = $this->techtree()->assertOk()->getContent();

        $this->assertSame(1, preg_match(
            '/id="tech-building-28".*?<span class="tech-status-chip[^"]*">(.*?)<\/span>/s',
            $html,
            $match,
        ));
        $chip = preg_replace('/\s+/', ' ', trim($match[1]));
        $this->assertSame('2 / 6', $chip, 'instance cap is max_instances (6), not max_level (3)');
    }

    public function test_detail_panel_uses_instance_helpers_instead_of_max_level(): void
    {
        $response = $this->techtree();

        $response->assertSee('x-text="instanceCountLabel(selectedTech)"', false);
        $response->assertDontSee("selectedTech.instance_count + (selectedTech.max_level ? ' / ' + selectedTech.max_level : '')", false);
    }

    public function test_instance_list_links_each_instance_to_its_tile(): void
    {
        $this->placeHousing([1 => [0, 1]]);

        $response = $this->techtree();

        $response->assertSee('x-for="inst in selectedTech.instances"', false);
        $response->assertSee(':href="tileLink(selectedTech, inst)"', false);
        $response->assertSee(__('techtree.detail_instance_tile_link'));
        // tileLink() builds on the named colony.view route, not a hard-coded path.
        $response->assertSee('window.__techtreeData.colonyViewUrl = '.json_encode(route('colony.view'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), false);

        $housing = collect($response->viewData('pageData')['phases'])
            ->flatMap(fn ($phase) => $phase['items'])
            ->first(fn ($item) => $item['type'] === 'building' && (int) $item['id'] === 28);
        $this->assertSame([['instance_id' => 1, 'q' => 0, 'r' => 1]], array_map(
            fn ($i) => ['instance_id' => $i['instance_id'], 'q' => $i['q'], 'r' => $i['r']],
            $housing['instances'],
        ));
    }

    public function test_build_link_is_gated_by_can_build_more(): void
    {
        $response = $this->techtree();

        $response->assertSee('x-if="canBuildMore(selectedTech)"', false);
        $response->assertSee(':href="buildLink(selectedTech)"', false);
        $response->assertDontSee("'/colony/view?build='", false);
    }

    /** @return array<string, mixed> */
    private function buildingItem(int $buildingId): array
    {
        $item = collect($this->techtree()->viewData('pageData')['phases'])
            ->flatMap(fn ($phase) => $phase['items'])
            ->first(fn ($item) => $item['type'] === 'building' && (int) $item['id'] === $buildingId);
        $this->assertNotNull($item, "building {$buildingId} must be a techtree node");

        return $item;
    }

    /**
     * The Harvester never appears in the colony build menu — the first instance
     * exists from colony start, the second is earned via Orin or the salvage
     * mission and placed from the tile panel of a regolith tile (GDD §4c). A
     * "?build=27" link would open an empty build mode, so the panel shows how the
     * next instance is obtained instead.
     */
    public function test_harvester_is_not_offered_via_build_menu_and_explains_acquisition(): void
    {
        $harvester = $this->buildingItem(27);

        $this->assertFalse($harvester['menu_buildable']);
        $this->assertSame(__('techtree.detail_harvester_acquire_hint'), $harvester['acquire_hint']);
    }

    public function test_menu_buildable_buildings_keep_the_build_link_without_hint(): void
    {
        $housing = $this->buildingItem(28);

        $this->assertTrue($housing['menu_buildable']);
        $this->assertNull($housing['acquire_hint']);
    }

    public function test_detail_panel_renders_acquire_hint_for_non_menu_buildings(): void
    {
        $this->techtree()->assertSee('x-if="acquireHint(selectedTech)"', false);
    }
}
