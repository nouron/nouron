<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Colony hex view — JSON data (GET colony.view) and the Svelte page wiring.
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

    private const COLONY_ID_FOR_REGOLITH = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    private function colonyPage(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/colony/+page.svelte'));
    }

    public function test_hexview_renders_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $response->assertOk();
        $response->assertJsonStructure(['tiles', 'colony' => ['id', 'name'], 'buildings', 'buildingCatalog', 'apAvailable']);
    }

    public function test_hexview_requires_auth(): void
    {
        $this->getJson(route('colony.view'))->assertUnauthorized();
    }

    public function test_hexview_contains_sidebar_and_repair_wiring(): void
    {
        $page = $this->colonyPage();

        // Sidebar context-title header + both action endpoints are wired into the page.
        $this->assertStringContainsString('tile-panel-title', $page);
        $this->assertStringContainsString("'/colony/building/repair'", $page);
        $this->assertStringContainsString("'/colony/building/invest'", $page);
        // The frontend paths are the API routes the server registers.
        $this->assertStringEndsWith('/colony/building/repair', route('colony.building.repair'));
        $this->assertStringEndsWith('/colony/building/invest', route('colony.building.invest'));
    }

    public function test_hexview_renders_ap_cost_chips_on_action_buttons(): void
    {
        // AP-cost chips (game-wide convention) sit inside the action buttons.
        $this->assertStringContainsString('ap-cost-chip', $this->colonyPage());
    }

    public function test_hexview_passes_resource_amounts_for_build_chip_affordability(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $response->assertOk();
        $this->assertIsInt($response->json('regolith'));
        $this->assertIsInt($response->json('werkstoffe'));
        $this->assertIsInt($response->json('freeSupply'));
    }

    public function test_hexview_renders_hint_completion_animation_markup(): void
    {
        // The hint bar moved from the colony page into the shell component.
        $hintBar = file_get_contents(base_path('frontend/src/lib/components/HintBar.svelte'));

        $this->assertStringContainsString('hint-bar-stack', $hintBar);
        $this->assertStringContainsString('{#if completed}', $hintBar);
    }

    public function test_hexview_buildings_include_tier_label(): void
    {
        // App-Locale explizit 'de' setzen (Tests laufen sonst mit config-Default 'en').
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $response->assertOk();

        $buildings = collect($response->json('buildings'));
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary, 'Krankenstation (id=46) muss in den Testdaten vorhanden sein');
        $this->assertSame(3, (int) $infirmary['level'], 'Testdaten-Annahme: Krankenstation steht auf Level 3');
        $this->assertSame('Vollausstattung', $infirmary['tier_label']);
    }

    // Regression (Owner-Playtest 2026-08-31): the sidebar showed build cost
    // and progress but never what the building actually does — the player
    // couldn't plan ahead. buildings.*_desc texts already existed, just
    // weren't wired into the tile-panel data.
    public function test_hexview_buildings_include_description(): void
    {
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $buildings = collect($response->json('buildings'));
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary);
        $this->assertSame(__('buildings.infirmary_desc'), $infirmary['description']);
        $this->assertNotEmpty($infirmary['description']);
    }

    // Regression (Owner-Playtest 2026-08-31, follow-up to the description
    // fix): "Voraussetzung" was already shown, but never the reverse — what
    // leveling THIS building up unlocks (e.g. "Hangar Lv1→2 unlocks
    // Frachter"). The test pins colony 1's hangars (building_id=44) to level 1.
    public function test_hexview_buildings_include_unlocks_next_level(): void
    {
        $this->app->setLocale('de');
        // The fixture's hangar 1 sits on Lv3 (active corvette, T20) — pin all hangars to Lv1.
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 44)->update(['level' => 1]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $buildings = collect($response->json('buildings'));
        $hangar = $buildings->firstWhere('building_id', 44);

        $this->assertNotNull($hangar);
        $this->assertSame(1, (int) $hangar['level'], 'Testdaten-Annahme: Hangar steht auf Level 1');
        $this->assertContains(
            __('techtree.ship_freighter'),
            array_column($hangar['unlocks_next_level'], 'text')
        );
    }

    // Regression (Owner-Playtest 2026-09-04): the sidebar showed "Effekte der
    // nächsten Stufe" but never the gate a building itself sits behind — same
    // format as TechtreeController::computeRequiredList() so both screens agree.
    // Infirmary (46) is seeded gated behind CommandCenter (25) Lv3.
    public function test_hexview_buildings_include_required_list(): void
    {
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $buildings = collect($response->json('buildings'));
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary);
        $this->assertSame([__('techtree.building_commandCenter').' Lv3'], $infirmary['required_list']);
    }

    // Regression (Owner-Playtest 2026-09-04): resource_amount on the tile depletes
    // every Sol a Harvester produces (GameTick::generateHarvesterYield()), but the
    // player never saw the remaining amount — a shrinking yield looked like a bug.
    public function test_hexview_tiles_include_regolith_remaining_for_placed_harvester(): void
    {
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 5,
            'r' => 5,
            'ring' => 3,
            'tile_type' => 'regolith_y2_d2',
            'is_colony_zone' => 0,
            'is_explored' => 1,
            'is_deep_scanned' => 0,
            'resource_amount' => 111,
            'resource_max' => 300,
        ]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID_FOR_REGOLITH)
            ->where('building_id', 27)
            ->update(['tile_x' => 5, 'tile_y' => 5]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $tiles = collect($response->json('tiles'));
        $tile = $tiles->first(fn ($t) => $t['q'] === 5 && $t['r'] === 5);

        $this->assertNotNull($tile);
        $this->assertSame(111, $tile['regolith_remaining']);
        // The view clamps resource_max live to the current
        // game.harvester.resource_max config value (regolith_y2_d2 = 450, T10
        // 2026-09-28) — the stale DB fixture (300, inserted above) is
        // deliberately different, to prove the clamp reads config, not the row.
        $this->assertSame(450, $tile['regolith_max']);
    }

    // A26: the "≈N Sole bis Erschöpfung" countdown markup renders (bound
    // client-side, so the actual number lives in the JSON — this guards the wiring exists).
    public function test_hexview_contains_sols_remaining_countdown_markup(): void
    {
        $page = $this->colonyPage();

        $this->assertStringContainsString('tile-building-sols-remaining', $page);
        $this->assertStringContainsString('tile.sols_remaining', $page);
        $this->assertStringContainsString("t('colony.harvester_sols_remaining_label')", $page);
        $this->assertNotSame('colony.harvester_sols_remaining_label', __('colony.harvester_sols_remaining_label'));
    }

    /**
     * A25: the active Harvester tile also carries a `sols_remaining` estimate
     * ("ca. N Sole bis Erschöpfung", A24/A25). Neutral trust + no geology so
     * the expected value matches the pure ColonyTileService::solsRemaining()
     * formula exactly: fresh_yield(regolith_y2_d2)=26, ceil(111/26)=5.
     */
    public function test_hexview_tiles_include_sols_remaining_for_placed_harvester(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_FOR_REGOLITH, 'resource_id' => 12],
            ['amount' => 0]
        );
        DB::table('trust_events')->where('colony_id', self::COLONY_ID_FOR_REGOLITH)->delete();
        DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID_FOR_REGOLITH)
            ->where('research_id', 92)
            ->delete();

        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 5,
            'r' => 5,
            'ring' => 3,
            'tile_type' => 'regolith_y2_d2',
            'is_colony_zone' => 0,
            'is_explored' => 1,
            'is_deep_scanned' => 0,
            'resource_amount' => 111,
            'resource_max' => 300,
        ]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID_FOR_REGOLITH)
            ->where('building_id', 27)
            ->update(['tile_x' => 5, 'tile_y' => 5]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $tiles = collect($response->json('tiles'));
        $tile = $tiles->first(fn ($t) => $t['q'] === 5 && $t['r'] === 5);

        $this->assertNotNull($tile);
        $this->assertSame(5, $tile['sols_remaining']);
    }

    /**
     * A tile with no active Harvester must not carry a sols_remaining value
     * (null → no countdown line rendered).
     */
    public function test_hexview_tiles_have_null_sols_remaining_without_active_harvester(): void
    {
        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $tiles = collect($response->json('tiles'));
        $tile = $tiles->first(fn ($t) => ! isset($t['regolith_remaining']));

        $this->assertNotNull($tile);
        $this->assertArrayNotHasKey('sols_remaining', $tile);
    }

    // Teil 2 (Owner-Playtest-Fund 2026-09-04, GDD §4c BALANCE CONCERN): the map
    // needs data about pre-scouted regolith relocation targets so the ui-specialist
    // follow-up task can highlight them once the active Harvester tile runs low.
    public function test_hexview_exposes_regolith_fallback_tiles_excluding_active_harvester_tile(): void
    {
        // Currently-used Harvester tile — must NOT appear in the fallback list.
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 5, 'r' => 5, 'ring' => 3,
            'tile_type' => 'regolith_y2_d2',
            'is_colony_zone' => 0, 'is_explored' => 1, 'is_deep_scanned' => 0,
            'resource_amount' => 111, 'resource_max' => 300,
        ]);
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID_FOR_REGOLITH)
            ->where('building_id', 27)
            ->update(['tile_x' => 5, 'tile_y' => 5]);

        // Explored, not-yet-depleted fallback tile — must appear.
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 7, 'r' => 2, 'ring' => 3,
            'tile_type' => 'regolith_y1_d1',
            'is_colony_zone' => 0, 'is_explored' => 1, 'is_deep_scanned' => 0,
            'resource_amount' => 160, 'resource_max' => 160,
        ]);

        // Fully-depleted regolith tile — must NOT appear (nothing left to relocate to).
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 8, 'r' => 2, 'ring' => 3,
            'tile_type' => 'regolith_y1_d1',
            'is_colony_zone' => 0, 'is_explored' => 1, 'is_deep_scanned' => 0,
            'resource_amount' => 0, 'resource_max' => 160,
        ]);

        // Unexplored regolith tile — must NOT appear (player hasn't scouted it).
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID_FOR_REGOLITH,
            'q' => 9, 'r' => 2, 'ring' => 3,
            'tile_type' => 'regolith_y3_d2',
            'is_colony_zone' => 0, 'is_explored' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 300, 'resource_max' => 300,
        ]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $response->assertOk();
        $fallbackTiles = collect($response->json('regolithFallbackTiles'))
            ->map(fn ($t) => [$t['q'], $t['r']])
            ->all();

        $this->assertContains([7, 2], $fallbackTiles);
        $this->assertNotContains([5, 5], $fallbackTiles);
        $this->assertNotContains([8, 2], $fallbackTiles);
        $this->assertNotContains([9, 2], $fallbackTiles);
    }

    public function test_pending_run_is_refused_with_run_not_started(): void
    {
        // A pending run is active but not yet started (started_at = null).
        DB::table('runs')
            ->where('user_id', self::BART_USER_ID)
            ->where('status', 'active')
            ->update(['started_at' => null]);

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'));

        $response->assertStatus(409);
        $response->assertJson(['ok' => false, 'error' => 'run_not_started']);
    }

    /**
     * "?build=ID" deep links (techtree, hints) for a building the build menu does
     * not offer right now must not silently open an empty build mode — the page
     * shows this toast instead (+page.svelte onMount()).
     */
    public function test_hexview_passes_build_link_unavailable_text(): void
    {
        $this->assertNotSame('colony.build_link_unavailable', __('colony.build_link_unavailable'));
        $this->assertStringContainsString("t('colony.build_link_unavailable')", $this->colonyPage());
    }

    public function test_hexview_renders_find_card_markup_from_lang_keys(): void
    {
        $terrain = file_get_contents(base_path('frontend/src/routes/(game)/colony/TileTerrain.svelte'));

        $this->assertStringContainsString("'/colony/tile/salvage'", $this->colonyPage());
        $this->assertStringEndsWith('/colony/tile/salvage', route('colony.tile.salvage'));
        $this->assertStringContainsString("t('colony.find_salvage')", $terrain);
        $this->assertStringContainsString("t('colony.find_false_notice')", $terrain);
        $this->assertNotSame('colony.find_salvage', __('colony.find_salvage'));
        $this->assertNotSame('colony.find_false_notice', __('colony.find_false_notice'));

        // Scan cost comes from config, never hardcoded in the page.
        $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->getJson(route('colony.view'))
            ->assertOk()
            ->assertJsonPath('deepScanAp', (int) config('game.finds.scan_ap'));
    }

    public function test_signal_dialog_has_description_text_per_find_size(): void
    {
        $page = $this->colonyPage();

        foreach (['small', 'medium', 'large'] as $size) {
            $this->assertNotSame("colony.find_{$size}_desc", __("colony.find_{$size}_desc"));
        }
        // The page looks the description up per find type: colony.find_<size>_desc.
        $this->assertStringContainsString("['find_small', 'find_medium', 'find_large'].includes(type) ? t(`colony.\${type}_desc`)", $page);
        $this->assertStringContainsString('discovery-event-desc', $page);
    }
}
