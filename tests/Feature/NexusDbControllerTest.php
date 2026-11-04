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
        $this->assertCount(5, $advisors);
        $this->assertSame(config('advisors.pilot.credits'), $advisors['techs_pilot']['hire_cost']);
        $this->assertSame(__('advisors.pilot'), $advisors['techs_pilot']['label']);
    }
}
