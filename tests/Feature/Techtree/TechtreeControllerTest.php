<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
use App\Services\Techtree\BuildingUnlockService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * I4: Cross-Colony-Build-Exploit
 *
 * Verifies that the TechtreeController always operates on the authenticated
 * user's own colony (resolved via session / getPrimeColony), and that no
 * URL or request parameter can redirect an action to a different user's colony.
 */
class TechtreeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected int $userIdBart = 3;  // owns colony 1 (Springfield)

    protected int $colonyIdBart = 1;

    protected int $colonyIdOther = 2;  // Shelbyville — no owner in test data

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Provide a construction advisor for colony 1 so techtree invest actions have AP.
        DB::table('advisors')->where('colony_id', $this->colonyIdBart)->delete();
        DB::table('advisors')->insert([
            'user_id' => $this->userIdBart,
            'personell_id' => 35, // engineer (construction AP)
            'colony_id' => $this->colonyIdBart,
            'rank' => 3,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);
    }

    /**
     * The techtree order URL contains {type}/{id} — there is no
     * colony_id segment. This test verifies that the order endpoint only
     * modifies the authenticated user's own colony, not any other colony.
     */
    public function test_order_only_affects_own_colony(): void
    {
        $bart = User::find($this->userIdBart);

        // Record colony 2 (other colony) ap_spend before the request
        $before = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdOther, 'building_id' => 27])
            ->value('ap_spend');

        // Bart invests AP in oremine — ap_spend on colony 1 must change
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdBart, 'building_id' => 27])
            ->update(['ap_spend' => 0]);

        $this->actingAs($bart)
            ->postJson(route('techtree.order', ['type' => 'building', 'id' => 27]), ['order' => 'add'])
            ->assertSuccessful();

        // Colony 2 must be untouched
        $afterOther = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdOther, 'building_id' => 27])
            ->value('ap_spend');

        $this->assertEquals($before, $afterOther, 'Colony 2 must not be affected by Bart\'s action');

        // Colony 1 must have changed (ap_spend increased by 1)
        $afterOwn = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdBart, 'building_id' => 27])
            ->value('ap_spend');

        $this->assertEquals(1, $afterOwn, 'Colony 1 (Bart\'s) must be updated');
    }

    // ── order(): rejections carry a reason ────────────────────────────────────

    /**
     * The techtree used to answer a bare `{success:false}` for every rejection, so
     * neither the player nor a client could tell "not enough AP" from "wrong CC level".
     */
    public function test_order_rejection_names_the_blocking_requirement(): void
    {
        config(['game.bypass.ap_checks' => false]);

        // housingComplex (28): ap_spend=0, ap_for_levelup=10 → the invested-AP gate blocks.
        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'building', 'id' => 28]), ['order' => 'levelup']);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'insufficient_ap_invested');

        // The message must be resolved text, not the lang key echoed back — a missing
        // translation would otherwise show up as "techtree.error_..." in the UI.
        $message = (string) $response->json('message');
        $this->assertNotEmpty($message, 'A rejection must carry player-facing text.');
        $this->assertStringNotContainsString('techtree.error_', $message);
        $this->assertSame(__('techtree.error_insufficient_ap_invested'), $message);
    }

    /**
     * Every blocker code the services can return must have a translation, in both
     * locales. Without this a new code silently surfaces as a raw lang key.
     */
    public function test_every_blocker_code_has_a_translation(): void
    {
        $codes = [
            'requires_building', 'requires_research', 'insufficient_resources',
            'insufficient_ap_invested', 'insufficient_supply', 'max_level',
            'knowledge_cc_gate', 'insufficient_ap', 'entity_not_found',
            'invalid_mode', 'unknown_type', 'unknown_order', 'order_failed',
        ];

        foreach (['de', 'en'] as $locale) {
            foreach ($codes as $code) {
                $key = "techtree.error_{$code}";
                $this->assertNotSame(
                    $key,
                    __($key, [], $locale),
                    "Missing {$locale} translation for {$key}"
                );
            }
        }
    }

    public function test_order_rejection_reports_the_knowledge_cc_gate(): void
    {
        // Knowledge Lv3→4 needs CC Lv4 (game.knowledge_cc_level_cap); Bart's CC is Lv3.
        $knowledgeId = 90; // construction
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyIdBart, 'research_id' => $knowledgeId],
            ['level' => 3, 'ap_spend' => 999, 'status_points' => 20]
        );

        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'research', 'id' => $knowledgeId]), ['order' => 'levelup']);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'knowledge_cc_gate');
    }

    public function test_order_rejects_an_unknown_order(): void
    {
        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'building', 'id' => 27]), ['order' => 'sabotage']);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'unknown_order');
    }

    /**
     * Regression: AdvisorService neither extends AbstractTechnologyService nor has
     * invest()/levelup(), yet the controller used to map 'personell' onto it — so this
     * request died with "call to undefined method" (HTTP 500). {type} is unconstrained
     * in the route, so it was reachable from outside. Advisors are hired via AdvisorController.
     */
    public function test_order_on_the_personell_type_is_rejected_not_a_server_error(): void
    {
        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'personell', 'id' => 35]), ['order' => 'add', 'ap' => 1]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'unknown_type');
    }

    public function test_order_on_an_unknown_type_is_a_client_error_not_a_server_error(): void
    {
        // Used to throw InvalidArgumentException → 500 in the now-removed GET action() path.
        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'bogus', 'id' => 27]), ['order' => 'add']);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'unknown_type');
    }

    public function test_index_returns200_with_page_data(): void
    {
        $bart = User::find($this->userIdBart);

        $response = $this->actingAs($bart)->get(route('techtree.index'));
        $response->assertOk();
        $response->assertViewHas('pageData');

        $pageData = $response->viewData('pageData');
        $this->assertArrayHasKey('phases', $pageData);
        $this->assertCount(5, $pageData['phases']);

        foreach (range(1, 5) as $n) {
            $this->assertArrayHasKey($n, $pageData['phases'], "Phase $n missing");
            $this->assertArrayHasKey('cc_level', $pageData['phases'][$n]);
            $this->assertArrayHasKey('items', $pageData['phases'][$n]);
            $this->assertArrayHasKey('lines', $pageData['phases'][$n]);
        }
    }

    public function test_index_phase_items_have_required_fields(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        foreach ($pageData['phases'] as $phaseNum => $phase) {
            foreach ($phase['items'] as $tech) {
                $this->assertArrayHasKey('id', $tech);
                $this->assertArrayHasKey('type', $tech);
                $this->assertArrayHasKey('row', $tech);
                $this->assertArrayHasKey('col', $tech);
                $this->assertArrayHasKey('status', $tech);
                $this->assertContains($tech['status'], ['built', 'available', 'locked'],
                    "Invalid status '{$tech['status']}' for {$tech['type']}/{$tech['id']} in phase {$phaseNum}");
            }
        }
    }

    // Regression (Owner-Playtest 2026-08-31): the techtree sidebar showed cost/
    // progress for a knowledge or building node but never what it actually
    // does — the player couldn't plan ahead. desc_techs_* texts already
    // existed for every building and knowledge, just weren't wired in.
    public function test_index_knowledge_and_building_items_include_description(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $construction = null;
        $sciencelab = null;
        foreach ($pageData['phases'] as $phase) {
            $construction ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 90);
            $sciencelab ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'building' && $t['key'] === 'building_sciencelab');
        }

        $this->assertNotNull($construction, 'construction (id=90) must be present in the phases');
        $this->assertSame(__('techtree.desc_techs_construction'), $construction['description']);
        $this->assertNotEmpty($construction['description']);

        $this->assertNotNull($sciencelab, 'sciencelab building must be present in the phases');
        $this->assertSame(__('techtree.desc_techs_sciencelab'), $sciencelab['description']);
        $this->assertNotEmpty($sciencelab['description']);
    }

    // Regression (Owner-Playtest 2026-09-04): ships were excluded from the
    // description wiring above even though desc_techs_drone/corvette/freighter
    // already existed in lang/de/techtree.php, unused.
    public function test_index_ship_items_include_description(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $drone = null;
        foreach ($pageData['phases'] as $phase) {
            $drone ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'ship' && $t['id'] === 85);
        }

        $this->assertNotNull($drone, 'ship_drone (id=85) must be present in the phases');
        $this->assertSame(__('techtree.desc_techs_drone'), $drone['description']);
        $this->assertNotEmpty($drone['description']);
    }

    // Regression (Owner-Playtest 2026-08-31, follow-up): reverse of
    // required_desc — what leveling up a building unlocks (e.g. "Hangar
    // Lv1→2 unlocks Frachter"). Colony 1's hangar (id=44) is seeded at Lv1.
    public function test_index_building_items_include_unlocks_next_level(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $hangar = null;
        foreach ($pageData['phases'] as $phase) {
            $hangar ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'building' && $t['key'] === 'building_hangar');
        }

        $this->assertNotNull($hangar, 'hangar building must be present in the phases');
        $this->assertSame(1, $hangar['level'], 'Testdaten-Annahme: Hangar steht auf Level 1');
        $this->assertContains(
            __('techtree.ship_freighter'),
            array_column($hangar['unlocks_next_level'], 'text')
        );
    }

    // Regression (Owner-Playtest 2026-08-31, follow-up to
    // test_index_building_items_include_unlocks_next_level): knowledge
    // levels reuse the SAME unlocks_next_level UI slot, populated from the
    // curve-based KnowledgeEffectDescriptionService instead of the discrete
    // BuildingUnlockService (knowledge has no discrete gate-unlocks).
    public function test_index_knowledge_items_include_curve_based_unlocks_next_level(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);

        // construction (id=90) at level 2 → next level 3 → ap_cost_reduction_per_lv[3]=4.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyIdBart, 'research_id' => 90],
            ['level' => 2, 'ap_spend' => 0, 'status_points' => 20]
        );

        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $construction = null;
        foreach ($pageData['phases'] as $phase) {
            $construction ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 90);
        }

        $this->assertNotNull($construction);
        $this->assertTrue(
            collect($construction['unlocks_next_level'])->contains(fn ($l) => $l['text'] === '-4% AP-Kosten' && $l['chip'] === null)
        );
    }

    // Owner-Playtest-Fund 2026-09-02: the sidebar only showed what the NEXT
    // level unlocks, never what the CURRENT level already does — same
    // service, called at the current level instead of level+1.
    public function test_index_building_items_include_effects_current_level(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $hangar = null;
        foreach ($pageData['phases'] as $phase) {
            $hangar ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'building' && $t['key'] === 'building_hangar');
        }

        $this->assertNotNull($hangar, 'hangar building must be present in the phases');
        $this->assertSame(1, $hangar['level'], 'Testdaten-Annahme: Hangar steht auf Level 1');
        $this->assertSame(
            $this->app->make(BuildingUnlockService::class)->unlocksAtLevel(44, 1),
            $hangar['effects_current_level']
        );
    }

    public function test_index_knowledge_items_include_effects_current_level(): void
    {
        $this->app->setLocale('de');
        $bart = User::find($this->userIdBart);

        // construction (id=90) at level 1 -> next level 2 (=4%) differs from current level 1 (=2%).
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyIdBart, 'research_id' => 90],
            ['level' => 1, 'ap_spend' => 0, 'status_points' => 20]
        );

        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $construction = null;
        foreach ($pageData['phases'] as $phase) {
            $construction ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 90);
        }

        $this->assertNotNull($construction);
        $this->assertNotSame($construction['unlocks_next_level'], $construction['effects_current_level']);
    }

    public function test_knowledge_ap_for_levelup_matches_config_not_stale_db_value(): void
    {
        // Playtest regression (2026-07-14): researches.ap_for_levelup is a static
        // Lv0->1 seed value (3 in test/dev data) that was never kept in sync with
        // config/knowledge.php's per-level levelup_costs — the techtree UI capped
        // every knowledge's progress bar at that stale 3 AP. The controller must
        // now resolve the cost dynamically for the knowledge's NEXT level.
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $research = null;
        foreach ($pageData['phases'] as $phase) {
            $found = collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['level'] === 0);
            if ($found) {
                $research = $found;
                break;
            }
        }

        $this->assertNotNull($research, 'Expected at least one not-yet-started research item');
        $expected = (int) (collect(config('knowledge'))->firstWhere('id', $research['id'])['levelup_costs'][1] ?? 0);
        $this->assertGreaterThan(0, $expected, 'precondition: config must define a Lv1 cost');
        $this->assertSame($expected, $research['ap_for_levelup'],
            'ap_for_levelup must come from config/knowledge.php levelup_costs, not the stale DB column');
    }

    public function test_knowledge_ap_for_levelup_escalates_with_level(): void
    {
        // A knowledge already at level 1 must show the Lv1->2 cost, not the Lv0->1 one.
        $bart = User::find($this->userIdBart);
        $colonyId = $this->colonyIdBart;

        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => 90],
            ['level' => 1, 'ap_spend' => 0, 'status_points' => 20]
        );

        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $research = null;
        foreach ($pageData['phases'] as $phase) {
            $found = collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 90);
            if ($found) {
                $research = $found;
                break;
            }
        }

        $this->assertNotNull($research);
        $expected = (int) config('knowledge.construction.levelup_costs.2');
        $this->assertSame($expected, $research['ap_for_levelup']);
    }

    public function test_all_phases_contain_items(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        foreach (range(1, 3) as $n) {
            $this->assertNotEmpty($pageData['phases'][$n]['items'], "Phase $n must have items");
        }
    }

    public function test_required_desc_shows_dual_prerequisites(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        // knowledge_cartography (ID 91) has dual prereq: Analytik-Labor Lv1 + Hangar Lv1
        $cartography = null;
        foreach ($pageData['phases'] as $phase) {
            $found = collect($phase['items'])->first(fn ($t) => $t['id'] === 91 && $t['type'] === 'research');
            if ($found) {
                $cartography = $found;
                break;
            }
        }

        $this->assertNotNull($cartography, 'knowledge_cartography (ID 91) must be in a phase');
        $this->assertNotNull($cartography['required_desc'], 'knowledge_cartography must have a required_desc');
        $this->assertStringContainsString('+', $cartography['required_desc'],
            'Dual prerequisites must be joined by "+"');
    }

    // Owner-Playtest-Fund 2026-08-31 (follow-up): the detail panel now renders
    // prerequisites as a bullet list instead of one "Benötigt X + Y" line, so
    // the controller must expose the parts separately.
    public function test_required_list_shows_dual_prerequisites_as_separate_parts(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        // knowledge_cartography (ID 91) has dual prereq: Analytik-Labor Lv1 + Hangar Lv1
        $cartography = null;
        foreach ($pageData['phases'] as $phase) {
            $found = collect($phase['items'])->first(fn ($t) => $t['id'] === 91 && $t['type'] === 'research');
            if ($found) {
                $cartography = $found;
                break;
            }
        }

        $this->assertNotNull($cartography, 'knowledge_cartography (ID 91) must be in a phase');
        $this->assertCount(2, $cartography['required_list']);
        $this->assertStringNotContainsString('Benötigt', $cartography['required_list'][0],
            'bullet parts must not repeat the "Benötigt" prefix already implied by the heading');
    }

    public function test_knowledge_cartography_is_in_phase3(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $found = collect($pageData['phases'][3]['items'])
            ->first(fn ($t) => $t['id'] === 91 && $t['type'] === 'research');

        $this->assertNotNull($found, 'knowledge_cartography (ID 91) must be in phase 3');
    }

    public function test_phase3_lines_include_hangar_arrow(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $lines = $pageData['phases'][3]['lines'];
        $hangarLines = array_filter($lines, fn ($l) => $l['from'] === 'tech-building-44');

        $this->assertNotEmpty($hangarLines, 'Phase 3 must have arrows originating from hangar (ID 44)');
    }

    /**
     * There is no colony_id parameter in the URL — the route signature
     * is /techtree/{type}/{id}/order. This confirms by design that
     * colony selection is server-side only (session-based).
     */
    public function test_techtree_route_has_no_colony_id_parameter(): void
    {
        $route = route('techtree.order', ['type' => 'building', 'id' => 27]);
        $this->assertStringNotContainsString('colony', $route);
    }

    public function test_infirmary_is_in_phase2(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $found = collect($pageData['phases'][2]['items'])
            ->first(fn ($t) => $t['id'] === 46 && $t['type'] === 'building');

        $this->assertNotNull($found, 'infirmary (building ID 46) must be in phase 2');
    }

    public function test_bar_is_in_phase2(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $found = collect($pageData['phases'][2]['items'])
            ->first(fn ($t) => $t['id'] === 52 && $t['type'] === 'building');

        $this->assertNotNull($found, 'bar/cantina (building ID 52) must be in phase 2');
    }

    public function test_knowledge_geology_is_in_phase3(): void
    {
        $bart = User::find($this->userIdBart);
        $pageData = $this->actingAs($bart)->get(route('techtree.index'))->viewData('pageData');

        $found = collect($pageData['phases'][3]['items'])
            ->first(fn ($t) => $t['id'] === 92 && $t['type'] === 'research');

        $this->assertNotNull($found, 'knowledge_geology (research ID 92) must be in phase 3');
    }

    // ── order('add'): auto-levelup in the same request (no page reload) ───────

    /**
     * Regression: the techtree UI used to send only order:'add', never order:'levelup'
     * — invest() alone only ever advances ap_spend, so a knowledge reaching the
     * threshold got stuck at that level forever (fixed in TechtreeController::order()
     * by auto-triggering levelup() in the same request once ap_spend reaches it).
     */
    public function test_order_add_auto_triggers_levelup_when_threshold_reached(): void
    {
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => true]);

        // cartography (research id 91): requires Analytik-Labor Lv1 (building 31) +
        // Hangar Lv1 (building 44) — both already built for colony 1 in test data.
        // levelup_costs[1] = 20 (config/knowledge.php).
        DB::table('advisors')->where('colony_id', $this->colonyIdBart)->where('personell_id', 36)->delete();
        DB::table('advisors')->insert([
            'user_id' => $this->userIdBart,
            'personell_id' => 36, // scientist (research AP), rank 3 = 12 AP/tick advisor bonus
            'colony_id' => $this->colonyIdBart,
            'rank' => 3,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);

        // Available research AP (base 6 + rank-3 advisor 12 = 18, at neutral trust) is
        // below the 20-AP threshold in a single request — pre-seed 19 already invested
        // so the final AP (1) is what pushes it over, exactly what auto-levelup proves.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyIdBart, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 19, 'status_points' => 20]
        );

        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'research', 'id' => 91]), ['order' => 'add', 'ap' => 1]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('leveled_up', true);
        $response->assertJsonPath('levelup_blocked_reason', null);
        $response->assertJsonPath('tech.level', 1);
        $response->assertJsonPath('tech.ap_spend', 0);
        // Next level (1->2) costs 28 per config/knowledge.php — the bar must rescale,
        // not stay capped at the Lv0->1 threshold (20).
        $response->assertJsonPath('tech.ap_for_levelup', 28);

        $this->assertSame(1, DB::table('colony_researches')
            ->where('colony_id', $this->colonyIdBart)->where('research_id', 91)->value('level'));
    }

    // ── order(): cross-node status refresh (phases_update) ────────────────────

    /**
     * Owner-Playtest-Fund 2026-09-04: a levelup used to only patch the ONE invested
     * tech client-side — a dependent tech elsewhere in the tree stayed 'locked' in
     * the UI until a full page reload, even though the server-side gate had already
     * flipped. knowledge_geology (research id=92) requires sciencelab (building
     * id=31) at Lv2 — colony 1's sciencelab starts at Lv1 (locked) and building 27
     * (its secondary Lv1 requirement) is already met. Leveling sciencelab to Lv2
     * must report geology's fresh status via 'phases_update' in the same response.
     */
    public function test_order_response_includes_phases_update_for_a_newly_unlocked_dependent_tech(): void
    {
        // Precondition: geology is locked while sciencelab is still Lv1.
        $before = $this->actingAs(User::find($this->userIdBart))
            ->get(route('techtree.index'))->viewData('pageData');
        $geologyBefore = null;
        foreach ($before['phases'] as $phase) {
            $geologyBefore ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 92);
        }
        $this->assertSame('locked', $geologyBefore['status'], 'precondition: geology must start locked');

        // sciencelab (building 31): ap_spend=0, ap_for_levelup=10 → invest exactly enough to auto-levelup to Lv2.
        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'building', 'id' => 31]), ['order' => 'add', 'ap' => 10]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('leveled_up', true);
        $response->assertJsonPath('tech.level', 2);

        $phasesUpdate = $response->json('phases_update');
        $this->assertIsArray($phasesUpdate, 'response must carry a phases_update payload');

        $geologyUpdate = null;
        foreach ($phasesUpdate as $phase) {
            $geologyUpdate ??= collect($phase['items'])->first(fn ($t) => $t['type'] === 'research' && $t['id'] === 92);
        }

        $this->assertNotNull($geologyUpdate, 'phases_update must include geology (research id=92)');
        $this->assertSame('available', $geologyUpdate['status'],
            'geology must flip to available in the same response that unlocks it, without a page reload');
    }

    /**
     * When ap_spend reaches the threshold but levelup() is blocked by an unmet
     * requirement (not just "not enough AP yet"), the invest must still report
     * success (the AP was validly spent) but surface *why* the level didn't move —
     * previously this was a silent no-op the player had no way to explain.
     */
    public function test_order_add_reports_blocked_reason_when_levelup_requirement_unmet(): void
    {
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => true]);

        // geology (research id 92): requires Analytik-Labor Lv2 (building 31) — colony 1
        // only has it at Lv1, so levelup() will be blocked by 'requires_building'.
        DB::table('advisors')->where('colony_id', $this->colonyIdBart)->where('personell_id', 36)->delete();
        DB::table('advisors')->insert([
            'user_id' => $this->userIdBart,
            'personell_id' => 36,
            'colony_id' => $this->colonyIdBart,
            'rank' => 3,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);

        $response = $this->actingAs(User::find($this->userIdBart))
            ->postJson(route('techtree.order', ['type' => 'research', 'id' => 92]), ['order' => 'add', 'ap' => 12]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('leveled_up', false);
        $response->assertJsonPath('levelup_blocked_reason', 'requires_building');
        $response->assertJsonPath('tech.level', 0);

        // The AP was spent (capped at the threshold) — not silently lost, but not
        // reinvestable either until the missing building is built.
        $this->assertSame(12, DB::table('colony_researches')
            ->where('colony_id', $this->colonyIdBart)->where('research_id', 92)->value('ap_spend'));
    }
}
