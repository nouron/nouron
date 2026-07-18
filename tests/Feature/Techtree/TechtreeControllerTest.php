<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
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
     * The techtree action URL contains {type}/{id}/{order} — there is no
     * colony_id segment. This test verifies that the action endpoint only
     * modifies the authenticated user's own colony, not any other colony.
     */
    public function test_action_only_affects_own_colony(): void
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
            ->get(route('techtree.action', ['type' => 'building', 'id' => 27, 'order' => 'add']))
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
     * Regression: PersonellService neither extends AbstractTechnologyService nor has
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

    public function test_action_on_an_unknown_type_is_404_not_a_server_error(): void
    {
        // Used to throw InvalidArgumentException → 500.
        $this->actingAs(User::find($this->userIdBart))
            ->get(route('techtree.action', ['type' => 'bogus', 'id' => 27, 'order' => 'add']))
            ->assertNotFound();
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
     * is /techtree/{type}/{id}/{order}. This confirms by design that
     * colony selection is server-side only (session-based).
     */
    public function test_techtree_route_has_no_colony_id_parameter(): void
    {
        $route = route('techtree.action', ['type' => 'building', 'id' => 27, 'order' => 'add']);
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
}
