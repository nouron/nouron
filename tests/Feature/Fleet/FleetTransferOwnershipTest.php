<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ownership security tests for fleet JSON endpoints and order actions.
 *
 * Covers:
 * - CRIT-1: POST /fleet/json/addToFleet/{id} with a fleet that belongs to another user → 403
 * - CRIT-1: addToFleet with own fleet → succeeds (200 JSON)
 * - HIGH-4: convoy order targeting a fleet owned by another user → validation error
 * - HIGH-4: join order targeting a fleet owned by another user → validation error
 * - HIGH-4: convoy order targeting own fleet → success (order stored)
 * - HIGH-4: join order targeting own fleet → success (order stored)
 *
 * Fixture summary (TestSeeder):
 *   Fleet 8  → user_id=18 (Lenny), at (6828, 3016)
 *   Fleet 10 → user_id=3  (Bart),  at (6828, 3016)  ← same coords as colony 1
 *   Fleet 15 → user_id=0  (Homer), at (6828, 3016)
 *   Fleet 16 → user_id=3  (Bart),  at (6828, 3016)  ← second Bart fleet, same coords
 *   Colony 1 (Springfield) → user_id=3 (Bart), at (6828, 3016)
 *   Colony 2 (Shelbyville) → user_id=0 (Homer), at (6828, 3016)
 */
class FleetTransferOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected int $bartUserId  = 3;
    protected int $homerUserId = 0;

    // Fleet IDs from seed
    protected int $bartFleetId   = 10;  // Bart's fleet at (6828,3016)
    protected int $bartFleet2Id  = 16;  // Bart's second fleet at (6828,3016)
    protected int $lennyFleetId  = 8;   // Lenny's fleet — used as "foreign" target
    protected int $homerFleetId  = 15;  // Homer's fleet at (6828,3016)

    protected function setUp(): void
    {
        parent::setUp();
        config(['game.dev_mode' => true]);
        $this->app->make(TestSeeder::class)->run();
    }

    // ── CRIT-1: addToFleet ownership ─────────────────────────────────────────

    /**
     * A player must not be able to transfer items into a fleet they do not own.
     * POST /fleet/json/addToFleet/{id} where {id} belongs to a different user
     * must return 403 JSON.
     */
    public function test_add_to_fleet_foreign_fleet_returns_403(): void
    {
        // Bart (user 3) tries to load a resource into Lenny's fleet (8)
        $response = $this->actingAs($this->makeUser($this->bartUserId))
            ->postJson(route('fleet.json.addtofleet', $this->lennyFleetId), [
                'itemType' => 'resource',
                'itemId'   => 4,   // res_compounds (Werkstoffe)
                'amount'   => 50,
            ]);

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Zugriff verweigert.']);
    }

    /**
     * A player must not be able to transfer items into Homer's fleet when acting as Bart.
     */
    public function test_add_to_fleet_another_users_fleet_returns_403(): void
    {
        $response = $this->actingAs($this->makeUser($this->bartUserId))
            ->postJson(route('fleet.json.addtofleet', $this->homerFleetId), [
                'itemType' => 'resource',
                'itemId'   => 3,
                'amount'   => 10,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Transferring to own fleet must succeed and return 200 with a JSON body.
     * Fleet 10 (Bart) is at (6828,3016,spot=0) but colony 1 has spot=1.
     * We move fleet 10 to spot=1 so the coords match and the transfer is valid.
     */
    public function test_add_to_fleet_own_fleet_returns_200(): void
    {
        // Align fleet spot with colony 1's spot (spot=1) so getColonyByCoords matches
        DB::table('fleets')->where('id', $this->bartFleetId)->update(['spot' => 1]);

        // Colony 1 has res_compounds / Werkstoffe (resource_id=4) with amount=18598 in seed
        $response = $this->actingAs($this->makeUser($this->bartUserId))
            ->postJson(route('fleet.json.addtofleet', $this->bartFleetId), [
                'itemType' => 'resource',
                'itemId'   => 4,   // res_compounds (Werkstoffe)
                'amount'   => 10,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['fleetId', 'itemType', 'transferred']);
        $this->assertGreaterThan(0, $response->json('transferred'));
    }

    /**
     * Unauthenticated request to addToFleet must redirect to login (not 403/200).
     */
    public function test_add_to_fleet_requires_auth(): void
    {
        $this->postJson(route('fleet.json.addtofleet', $this->bartFleetId), [
            'itemType' => 'resource',
            'itemId'   => 3,
            'amount'   => 10,
        ])->assertUnauthorized();
    }

    // ── HIGH-4: convoy/join ownership ────────────────────────────────────────

    /**
     * convoy order with a target fleet that belongs to a different user must
     * return a validation error (not store the order).
     */
    public function test_convoy_order_foreign_fleet_returns_error(): void
    {
        // Homer issues a convoy order pointing at Lenny's fleet (8) — not Homer's
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->homerFleetId), [
                'order'           => 'convoy',
                'target_fleet_id' => $this->lennyFleetId,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('order');

        $this->assertDatabaseMissing('fleet_orders', [
            'fleet_id' => $this->homerFleetId,
            'order'    => 'convoy',
        ]);
    }

    /**
     * join order with a target fleet that belongs to a different user must
     * return a validation error (not store the order).
     */
    public function test_join_order_foreign_fleet_returns_error(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->homerFleetId), [
                'order'           => 'join',
                'target_fleet_id' => $this->lennyFleetId,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('order');

        $this->assertDatabaseMissing('fleet_orders', [
            'fleet_id' => $this->homerFleetId,
            'order'    => 'join',
        ]);
    }

    /**
     * A non-existent fleet ID in convoy must also return a validation error.
     */
    public function test_convoy_order_nonexistent_fleet_returns_error(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->homerFleetId), [
                'order'           => 'convoy',
                'target_fleet_id' => 99999,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    /**
     * convoy to own fleet must store the order (happy path).
     * Bart has two fleets (10 and 16) — convoy from 16 to 10.
     */
    public function test_convoy_order_own_fleet_stores_order(): void
    {
        $ordersBefore = DB::table('fleet_orders')
            ->where('fleet_id', $this->bartFleet2Id)
            ->where('order', 'convoy')
            ->count();

        $this->actingAs($this->makeUser($this->bartUserId))
            ->post(route('fleet.orders.store', $this->bartFleet2Id), [
                'order'           => 'convoy',
                'target_fleet_id' => $this->bartFleetId,
            ])
            ->assertRedirect(route('fleet.config', $this->bartFleet2Id))
            ->assertSessionHas('success');

        $this->assertEquals(
            $ordersBefore + 1,
            DB::table('fleet_orders')
                ->where('fleet_id', $this->bartFleet2Id)
                ->where('order', 'convoy')
                ->count()
        );
    }

    /**
     * join to own fleet must store the order (happy path).
     */
    public function test_join_order_own_fleet_stores_order(): void
    {
        $ordersBefore = DB::table('fleet_orders')
            ->where('fleet_id', $this->bartFleet2Id)
            ->where('order', 'join')
            ->count();

        $this->actingAs($this->makeUser($this->bartUserId))
            ->post(route('fleet.orders.store', $this->bartFleet2Id), [
                'order'           => 'join',
                'target_fleet_id' => $this->bartFleetId,
            ])
            ->assertRedirect(route('fleet.config', $this->bartFleet2Id))
            ->assertSessionHas('success');

        $this->assertEquals(
            $ordersBefore + 1,
            DB::table('fleet_orders')
                ->where('fleet_id', $this->bartFleet2Id)
                ->where('order', 'join')
                ->count()
        );
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): \App\Models\User
    {
        return \App\Models\User::where('user_id', $userId)->firstOrFail();
    }
}
