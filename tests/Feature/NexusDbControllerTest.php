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

}
