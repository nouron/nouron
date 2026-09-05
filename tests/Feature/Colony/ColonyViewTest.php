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

    public function test_hexview_buildings_include_tier_label(): void
    {
        // App-Locale explizit 'de' setzen (Tests laufen sonst mit config-Default 'en').
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $response->assertOk();

        $buildings = $response->viewData('buildings');
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary, 'Krankenstation (id=46) muss in den Testdaten vorhanden sein');
        $this->assertSame(3, (int) $infirmary->level, 'Testdaten-Annahme: Krankenstation steht auf Level 3');
        $this->assertSame('Vollausstattung', $infirmary->tier_label);
    }

    // Regression (Owner-Playtest 2026-08-31): the sidebar showed build cost
    // and progress but never what the building actually does — the player
    // couldn't plan ahead. buildings.*_desc texts already existed, just
    // weren't wired into the tile-panel data.
    public function test_hexview_buildings_include_description(): void
    {
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $buildings = $response->viewData('buildings');
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary);
        $this->assertSame(__('buildings.infirmary_desc'), $infirmary->description);
        $this->assertNotEmpty($infirmary->description);
    }

    // Regression (Owner-Playtest 2026-08-31, follow-up to the description
    // fix): "Voraussetzung" was already shown, but never the reverse — what
    // leveling THIS building up unlocks (e.g. "Hangar Lv1→2 unlocks
    // Frachter"). Colony 1's hangar (building_id=44) is seeded at level 1.
    public function test_hexview_buildings_include_unlocks_next_level(): void
    {
        $this->app->setLocale('de');

        $response = $this->actingAs($this->makeUser(self::BART_USER_ID))
            ->get(route('colony.view'));

        $buildings = $response->viewData('buildings');
        $hangar = $buildings->firstWhere('building_id', 44);

        $this->assertNotNull($hangar);
        $this->assertSame(1, (int) $hangar->level, 'Testdaten-Annahme: Hangar steht auf Level 1');
        $this->assertContains(
            __('techtree.ship_freighter'),
            array_column($hangar->unlocks_next_level, 'text')
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
            ->get(route('colony.view'));

        $buildings = $response->viewData('buildings');
        $infirmary = $buildings->firstWhere('building_id', 46);

        $this->assertNotNull($infirmary);
        $this->assertSame([__('techtree.building_commandCenter').' Lv3'], $infirmary->required_list);
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
            'tile_type' => 'regolith_normal',
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
            ->get(route('colony.view'));

        $tiles = $response->viewData('tiles');
        $tile = $tiles->first(fn ($t) => $t['q'] === 5 && $t['r'] === 5);

        $this->assertNotNull($tile);
        $this->assertSame(111, $tile['regolith_remaining']);
        $this->assertSame(300, $tile['regolith_max']);
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
