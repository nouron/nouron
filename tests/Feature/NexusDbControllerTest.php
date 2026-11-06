<?php

namespace Tests\Feature;

/**
 * NexusDbController feature tests — GET /api/v1/nexus-db (route: nexusdb.index).
 *
 * Covered scenarios:
 *
 *  AUTH GUARD
 *    - test_unauthenticated_access_is_rejected
 *
 *  HAPPY PATH
 *    - test_authenticated_access_returns_200
 *    - test_response_carries_the_first_visit_flag
 *
 * Fixture: TestSeeder provides colony 1 (Springfield, owned by Bart, user_id=3)
 * with a started run.
 */

use App\Models\User;
use App\Services\BuildingCostService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NexusDbControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    /** Bart — user_id=3, owns colony_id=1 (Springfield). */
    private const BART_ID = 3;

    // ── Setup ─────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bart(): User
    {
        return User::find(self::BART_ID);
    }

    // ── AUTH GUARD ────────────────────────────────────────────────────────────

    /**
     * An unauthenticated GET must be rejected with 401.
     * The `auth` middleware short-circuits before the controller runs.
     */
    public function test_unauthenticated_access_is_rejected(): void
    {
        $response = $this->get(route('nexusdb.index'));

        $response->assertUnauthorized();
    }

    // ── HAPPY PATH ────────────────────────────────────────────────────────────

    /**
     * An authenticated GET must return HTTP 200.
     */
    public function test_authenticated_access_returns_200(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertOk();
    }

    /**
     * The page data tells the frontend whether to show the first-visit popup.
     */
    public function test_response_carries_the_first_visit_flag(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertOk();
        $this->assertIsBool($response->json('first_visit'));
    }

    public function test_catalog_lists_every_active_building_regardless_of_build_state(): void
    {
        $json = $this->actingAs($this->bart())->getJson(route('nexusdb.index'))->assertOk()->json('buildings');
        $b = collect($json)->keyBy('key');

        $this->assertCount(13, $json);

        // Kolonialdenkmal needs CC Lv5: listed all the same, with its requirement.
        $this->assertSame(5, $b['building_monument']['requires']['cc_level']);
        $this->assertNull($b['building_monument']['requires']['building']);

        // Agrardom hangs off the Harvester, not the CC.
        $this->assertNull($b['building_bioFacility']['requires']['cc_level']);
        $this->assertSame(1, $b['building_bioFacility']['requires']['building']['level']);
        $this->assertSame(__('techtree.building_harvester'), $b['building_bioFacility']['requires']['building']['label']);

        $hangar = $b['building_hangar'];
        $costs = app(BuildingCostService::class);
        $this->assertSame(__('techtree.building_hangar'), $hangar['label']);
        $this->assertNotSame('', $hangar['description']);
        $this->assertSame($costs->placementCost(44), array_map('intval', $hangar['placement_cost']));
        $this->assertSame(3, $hangar['max_level']);
        $this->assertCount(3, $hangar['levels']);
        $this->assertTrue($hangar['is_path']);
        $this->assertSame([], $b['building_harvester']['placement_cost']);
    }

    public function test_catalog_lists_knowledge_ships_and_advisors_with_costs_and_requirements(): void
    {
        $json = $this->actingAs($this->bart())->getJson(route('nexusdb.index'))->assertOk()->json();

        $knowledge = collect($json['knowledge'])->keyBy('key');
        $this->assertCount(7, $knowledge);
        $geology = $knowledge['knowledge_geology'];
        $this->assertSame(__('techtree.knowledge_geology'), $geology['label']);
        $this->assertNotSame('', $geology['description']);
        $this->assertSame(
            [['label' => __('techtree.building_sciencelab'), 'level' => 2], ['label' => __('techtree.building_harvester'), 'level' => 1]],
            $geology['requires']
        );
        $this->assertSame(config('knowledge.geology.levelup_costs'), collect($geology['levels'])->pluck('ap', 'level')->all());

        $ships = collect($json['ships'])->keyBy('key');
        $this->assertCount(3, $ships);
        $this->assertSame(config('ships.corvette.nexus_cost'), $ships['ship_corvette']['nexus_cost']);
        $this->assertSame([['label' => __('techtree.building_hangar'), 'level' => 3]], $ships['ship_corvette']['requires']);

        $advisors = collect($json['advisors'])->keyBy('key');
        // The Strategist is hidden (postponed, may return with the Uplink building).
        $this->assertCount(4, $advisors);
        $this->assertFalse($advisors->has('techs_strategist'));
        $this->assertSame(config('advisors.pilot.credits'), $advisors['techs_pilot']['hire_cost']);
        $this->assertSame(__('advisors.pilot'), $advisors['techs_pilot']['label']);
    }

    public function test_advisor_ranks_carry_upkeep_hire_cost_and_bonuses(): void
    {
        $advisors = collect($this->actingAs($this->bart())->getJson(route('nexusdb.index'))->assertOk()->json('advisors'))->keyBy('key');

        $engineer = $advisors['techs_engineer'];
        $this->assertCount(3, $engineer['ranks']);
        $this->assertSame(config('game.advisor.upkeep'), collect($engineer['ranks'])->pluck('upkeep', 'rank')->all());
        $this->assertSame(config('advisors.engineer.credits'), $engineer['ranks'][0]['cost']);
        $this->assertSame(config('game.advisor.promotion_costs.2'), $engineer['ranks'][1]['cost']);
        // AP per Sol plus the engineer-only AP cap bonus.
        $this->assertCount(2, $engineer['ranks'][1]['bonuses']);
        $this->assertCount(2, $engineer['ranks'][0]['bonuses']);
        // The cap is shown as the total (base + rank bonus), not as a surcharge.
        $total = (int) config('game.build.ap_cap_per_sol.base') + (int) config('game.build.ap_cap_per_sol.engineer_bonus_per_rank.2');
        $this->assertStringContainsString((string) $total, $engineer['ranks'][1]['bonuses'][1]);
        $this->assertStringNotContainsString('+', $engineer['ranks'][1]['bonuses'][1]);
        // The scientist has the AP line only.
        $this->assertCount(1, $advisors['techs_scientist']['ranks'][2]['bonuses']);
        // Pilot rank 3: 3 x pilot_rank_bonus_pct (5 %) = +15 %.
        $this->assertStringContainsString('15', $advisors['techs_pilot']['ranks'][2]['bonuses'][1]);
        $this->assertSame('building_sciencelab', $advisors['techs_scientist']['focus_building']);
        $this->assertNull($engineer['focus_building']);
    }

    public function test_knowledge_levels_list_their_effects(): void
    {
        $k = collect($this->actingAs($this->bart())->getJson(route('nexusdb.index'))->assertOk()->json('knowledge'))->keyBy('key');

        // construction: AP reduction on level 1 (2 %).
        $this->assertNotEmpty($k['knowledge_construction']['levels'][0]['effects']);
        // agronomy: trust per level on every level.
        foreach ($k['knowledge_agronomy']['levels'] as $l) {
            $this->assertNotEmpty($l['effects']);
        }
    }
}
