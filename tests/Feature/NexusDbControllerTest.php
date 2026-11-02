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
}
