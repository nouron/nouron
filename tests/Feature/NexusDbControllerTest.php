<?php

namespace Tests\Feature;

/**
 * NexusDbController feature tests — GET /nexus-db (route: nexusdb.index).
 *
 * Covered scenarios:
 *
 *  AUTH GUARD
 *    - test_unauthenticated_access_redirects_to_login
 *
 *  HAPPY PATH
 *    - test_authenticated_access_returns_200
 *    - test_view_nexusdb_index_is_rendered
 *
 *  VIEW DATA — $buildings
 *    - test_view_has_buildings_data
 *    - test_buildings_array_is_not_empty
 *    - test_known_building_keys_are_present
 *
 *  VIEW DATA — $ships
 *    - test_view_has_ships_data
 *    - test_ships_array_is_not_empty
 *    - test_known_ship_keys_are_present
 *
 *  VIEW DATA — $knowledge
 *    - test_view_has_knowledge_data
 *    - test_knowledge_array_is_not_empty
 *
 * Note on DB fixtures:
 *   The view composer in AppServiceProvider fires on every authenticated request
 *   and calls ResourcesService->getPossessionsByColonyId() for the default colony
 *   (session key 'activeIds.colonyId', fallback = 1).  Colony 1 must therefore
 *   exist in the DB.  TestSeeder provides colony 1 (Springfield, owned by Bart,
 *   user_id=3).  Tests act as Bart so the session default matches his colony.
 */

use App\Models\User;
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
     * An unauthenticated GET must redirect to the login page (302).
     * The `auth` middleware short-circuits before the controller runs.
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get(route('nexusdb.index'));

        $response->assertRedirect(route('login'));
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
     * The controller must render the `nexusdb.index` Blade view.
     */
    public function test_view_nexusdb_index_is_rendered(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertViewIs('nexusdb.index');
    }

    // ── VIEW DATA — $buildings ────────────────────────────────────────────────

    /**
     * The view must receive a `$buildings` variable.
     */
    public function test_view_has_buildings_data(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertViewHas('buildings');
    }

    /**
     * `$buildings` must not be empty — config/buildings.php defines at least
     * one entry in every environment.
     */
    public function test_buildings_array_is_not_empty(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $buildings = $response->viewData('buildings');

        $this->assertIsArray($buildings);
        $this->assertNotEmpty($buildings, '$buildings passed to the view must not be an empty array');
    }

    /**
     * The `commandCenter` and `harvester` keys must exist in `$buildings`.
     * These are core buildings present in every non-trivial game config.
     */
    public function test_known_building_keys_are_present(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $buildings = $response->viewData('buildings');

        $this->assertArrayHasKey('commandCenter', $buildings, 'commandCenter must be defined in config/buildings.php');
        $this->assertArrayHasKey('harvester',     $buildings, 'harvester must be defined in config/buildings.php');
    }

    // ── VIEW DATA — $ships ────────────────────────────────────────────────────

    /**
     * The view must receive a `$ships` variable.
     */
    public function test_view_has_ships_data(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertViewHas('ships');
    }

    /**
     * `$ships` must not be empty — config/ships.php defines at least one entry.
     */
    public function test_ships_array_is_not_empty(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $ships = $response->viewData('ships');

        $this->assertIsArray($ships);
        $this->assertNotEmpty($ships, '$ships passed to the view must not be an empty array');
    }

    /**
     * The `corvette`, `freighter`, and `drone` keys must exist in `$ships`.
     * These are the three ship types present in every baseline game config.
     */
    public function test_known_ship_keys_are_present(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $ships = $response->viewData('ships');

        $this->assertArrayHasKey('corvette',  $ships, 'corvette must be defined in config/ships.php');
        $this->assertArrayHasKey('freighter', $ships, 'freighter must be defined in config/ships.php');
        $this->assertArrayHasKey('drone',     $ships, 'drone must be defined in config/ships.php');
    }

    // ── VIEW DATA — $knowledge ────────────────────────────────────────────────

    /**
     * The view must receive a `$knowledge` variable.
     */
    public function test_view_has_knowledge_data(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $response->assertViewHas('knowledge');
    }

    /**
     * `$knowledge` must not be empty — game.knowledge_cc_level_cap must have
     * at least one entry mapping a knowledge level to a required CC level.
     */
    public function test_knowledge_array_is_not_empty(): void
    {
        $response = $this->actingAs($this->bart())
            ->get(route('nexusdb.index'));

        $knowledge = $response->viewData('knowledge');

        $this->assertIsArray($knowledge);
        $this->assertNotEmpty($knowledge, '$knowledge passed to the view must not be an empty array');
    }
}
