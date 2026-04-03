<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use App\Models\FleetOrder;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for fleet order creation via POST /fleet/{id}/orders.
 *
 * Fleet 15 belongs to user 0 (Homer), currently at (6828, 3016).
 * System 1 center (6800, 3000) — range 100, radius 50 → system spans ~(6750-6850, 2950-3050).
 * All objects 1-5, 10, 11 are within system 1.
 * Object 12 at (9190, 7790) is in a different area (no system → treated as out-of-system).
 */
class FleetOrderTest extends TestCase
{
    use RefreshDatabase;

    protected int $homerUserId = 0;
    protected int $bartUserId  = 3;
    protected int $fleetId     = 15; // Homer's fleet (user_id=0)

    protected function setUp(): void
    {
        parent::setUp();
        config(['game.dev_mode' => true]);
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_store_order_requires_auth(): void
    {
        $this->post(route('fleet.orders.store', $this->fleetId), ['order' => 'move'])
            ->assertRedirect(route('login'));
    }

    public function test_store_order_forbidden_for_wrong_user(): void
    {
        // Bart tries to issue an order on Homer's fleet
        $this->actingAs($this->makeUser($this->bartUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'         => 'move',
                'destination_x' => 6820,
                'destination_y' => 3020,
            ])
            ->assertForbidden();
    }

    // ── Move order ────────────────────────────────────────────────────────────

    public function test_move_order_within_system_stores_orders(): void
    {
        $countBefore = FleetOrder::where('fleet_id', $this->fleetId)->count();

        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'         => 'move',
                'destination_x' => 6820,
                'destination_y' => 3020,
            ])
            ->assertRedirect(route('fleet.config', $this->fleetId));

        // At least one new order should be created (old ones cleared, new path stored)
        $countAfter = FleetOrder::where('fleet_id', $this->fleetId)->count();
        $this->assertGreaterThan(0, $countAfter);

        // Coordinates should be stored as JSON, not PHP serialize
        $order = FleetOrder::where('fleet_id', $this->fleetId)->orderBy('tick')->first();
        $decoded = json_decode($order->coordinates, true);
        $this->assertIsArray($decoded);
        $this->assertCount(3, $decoded);
    }

    public function test_move_order_out_of_system_is_rejected(): void
    {
        // Fleet 8 is near system 1; coordinates (9190, 7790) are far away / different system
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'         => 'move',
                'destination_x' => 9190,
                'destination_y' => 7790,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    public function test_move_order_validates_coordinates(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'         => 'move',
                'destination_x' => 'not-a-number',
                'destination_y' => 3020,
            ])
            ->assertSessionHasErrors('destination_x');
    }

    // ── Trade order ───────────────────────────────────────────────────────────

    public function test_trade_order_stores_correctly(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'       => 'trade',
                'colony_id'   => 2,
                'resource_id' => 3,
                'amount'      => 50,
                'direction'   => 1,
            ])
            ->assertRedirect(route('fleet.config', $this->fleetId));

        $order = FleetOrder::where('fleet_id', $this->fleetId)
            ->where('order', 'trade')
            ->first();
        $this->assertNotNull($order);

        // data should be stored as JSON
        $data = json_decode($order->data, true);
        $this->assertIsArray($data);
        $this->assertEquals(2,  $data['colony_id']);
        $this->assertEquals(3,  $data['resource_id']);
        $this->assertEquals(50, $data['amount']);
        $this->assertEquals(1,  $data['direction']);
    }

    // ── Attack order ──────────────────────────────────────────────────────────

    public function test_attack_order_stores_correctly(): void
    {
        // Fleet 10 belongs to user 3 (Bart) — Homer attacks it
        $targetFleet = Fleet::find(10);
        $this->assertNotNull($targetFleet);

        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'           => 'attack',
                'target_fleet_id' => $targetFleet->id,
            ])
            ->assertRedirect(route('fleet.config', $this->fleetId));

        $order = FleetOrder::where('fleet_id', $this->fleetId)
            ->where('order', 'attack')
            ->first();
        $this->assertNotNull($order);
    }

    public function test_attack_order_nonexistent_target_fails(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.orders.store', $this->fleetId), [
                'order'           => 'attack',
                'target_fleet_id' => 99999,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('order');
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): \App\Models\User
    {
        return \App\Models\User::where('user_id', $userId)->firstOrFail();
    }
}
