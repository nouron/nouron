<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for GameTick step 1 — Fleet move orders.
 *
 * Covered scenarios:
 *  - Happy path: move order updates fleet x/y/spot and marks was_processed=1
 *  - Event created when fleet arrives
 *  - Order already processed is not re-applied
 *  - Invalid / missing coordinates skips the fleet (no update)
 *  - Fleet from wrong tick is not moved (tick gate)
 *
 * Fixture summary (TestSeeder):
 *   Fleet 11 (user_id=3, Bart) at (6827, 3014, spot=0) — chosen because it has
 *   no pre-seeded orders in the high-tick range we use here.
 */
class GameTickMovementTest extends TestCase
{
    use RefreshDatabase;

    private const FLEET_ID = 11; // Test Flotte 2 — user 3, no conflicting seed orders

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function insertMoveOrder(int $tick, int $fleetId, string $coords): void
    {
        DB::table('fleet_orders')->insert([
            'fleet_id'      => $fleetId,
            'tick'          => $tick,
            'order'         => 'move',
            'coordinates'   => $coords,
            'data'          => null,
            'was_processed' => 0,
        ]);
    }

    private function getFleetPosition(int $fleetId): object
    {
        return DB::table('fleets')->where('id', $fleetId)->first();
    }

    private function getOrderProcessed(int $tick, int $fleetId): int
    {
        return (int) DB::table('fleet_orders')
            ->where('tick', $tick)
            ->where('fleet_id', $fleetId)
            ->where('order', 'move')
            ->value('was_processed');
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    /**
     * A valid move order updates fleet coordinates and marks the order processed.
     */
    public function test_move_order_updates_fleet_position(): void
    {
        $tick = 10001;
        $this->insertMoveOrder($tick, self::FLEET_ID, '[7000, 3200]');

        Artisan::call('game:tick', ['--tick' => $tick]);

        $fleet = $this->getFleetPosition(self::FLEET_ID);
        $this->assertEquals(7000, $fleet->x, 'Fleet x must be updated to ordered coordinate');
        $this->assertEquals(3200, $fleet->y, 'Fleet y must be updated to ordered coordinate');
    }

    /**
     * After processing, was_processed must be set to 1.
     */
    public function test_move_order_is_marked_processed(): void
    {
        $tick = 10002;
        $this->insertMoveOrder($tick, self::FLEET_ID, '[7100, 3300]');

        Artisan::call('game:tick', ['--tick' => $tick]);

        $this->assertEquals(1, $this->getOrderProcessed($tick, self::FLEET_ID),
            'Move order was_processed must be 1 after tick');
    }

    /**
     * A fleet_arrived event must be created for the moving fleet's user.
     */
    public function test_move_order_creates_fleet_arrived_event(): void
    {
        $tick = 10003;
        $this->insertMoveOrder($tick, self::FLEET_ID, '[7200, 3400]');

        Artisan::call('game:tick', ['--tick' => $tick]);

        $event = DB::table('colony_log')
            ->where('user', 3) // Bart owns fleet 11
            ->where('event', 'galaxy.fleet_arrived')
            ->where('tick', $tick)
            ->first();

        $this->assertNotNull($event, 'galaxy.fleet_arrived event must be created after move');
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    /**
     * An order at a different tick number must NOT be processed when the tick does not match.
     */
    public function test_move_order_at_wrong_tick_is_not_processed(): void
    {
        $orderTick = 10010;
        $runTick   = 10011; // one off
        $this->insertMoveOrder($orderTick, self::FLEET_ID, '[9000, 9000]');

        $positionBefore = $this->getFleetPosition(self::FLEET_ID);
        Artisan::call('game:tick', ['--tick' => $runTick]);

        $positionAfter = $this->getFleetPosition(self::FLEET_ID);
        $this->assertEquals($positionBefore->x, $positionAfter->x,
            'Fleet must not move when tick does not match order tick');
        $this->assertEquals(0, $this->getOrderProcessed($orderTick, self::FLEET_ID),
            'Order at wrong tick must not be marked processed');
    }

    /**
     * An order that was already processed (was_processed=1) must not re-apply.
     */
    public function test_already_processed_move_order_is_not_reapplied(): void
    {
        $tick = 10012;
        // Insert already-processed order
        DB::table('fleet_orders')->insert([
            'fleet_id'      => self::FLEET_ID,
            'tick'          => $tick,
            'order'         => 'move',
            'coordinates'   => '[9999, 9999]',
            'data'          => null,
            'was_processed' => 1, // already done
        ]);

        $positionBefore = $this->getFleetPosition(self::FLEET_ID);
        Artisan::call('game:tick', ['--tick' => $tick]);

        $positionAfter = $this->getFleetPosition(self::FLEET_ID);
        $this->assertEquals($positionBefore->x, $positionAfter->x,
            'Fleet must not move when order was already processed');
    }

    // ── Adversarial ───────────────────────────────────────────────────────────

    /**
     * An order with invalid (non-array) coordinates must not crash and must skip the fleet.
     */
    public function test_move_order_with_invalid_coordinates_is_skipped(): void
    {
        $tick = 10020;
        $this->insertMoveOrder($tick, self::FLEET_ID, 'not-valid-json');

        $positionBefore = $this->getFleetPosition(self::FLEET_ID);
        Artisan::call('game:tick', ['--tick' => $tick]);

        $positionAfter = $this->getFleetPosition(self::FLEET_ID);
        $this->assertEquals($positionBefore->x, $positionAfter->x,
            'Fleet must not move when coordinates JSON is invalid');
    }

    /**
     * An order with too few coordinate values (< 2) must skip the fleet.
     */
    public function test_move_order_with_incomplete_coordinates_is_skipped(): void
    {
        $tick = 10021;
        $this->insertMoveOrder($tick, self::FLEET_ID, '[7000]'); // only 1 element

        $positionBefore = $this->getFleetPosition(self::FLEET_ID);
        Artisan::call('game:tick', ['--tick' => $tick]);

        $positionAfter = $this->getFleetPosition(self::FLEET_ID);
        $this->assertEquals($positionBefore->x, $positionAfter->x,
            'Fleet must not move when coordinates array has fewer than 2 elements');
    }

    /**
     * Multiple move orders for different fleets in the same tick must all be processed.
     */
    public function test_multiple_move_orders_in_same_tick_are_all_processed(): void
    {
        $tick = 10030;
        // Fleet 11 and Fleet 12 — both belong to user 3
        $this->insertMoveOrder($tick, 11, '[7100, 3100]');
        $this->insertMoveOrder($tick, 12, '[7200, 3200]');

        Artisan::call('game:tick', ['--tick' => $tick]);

        $fleet11 = $this->getFleetPosition(11);
        $fleet12 = $this->getFleetPosition(12);

        $this->assertEquals(7100, $fleet11->x, 'Fleet 11 must move to ordered coordinates');
        $this->assertEquals(7200, $fleet12->x, 'Fleet 12 must move to ordered coordinates');
    }
}
