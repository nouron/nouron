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
