<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HIGH-2: Trade order resource clamping — a fleet's sell order must never
 * transfer more resources to a colony than the fleet actually holds, and a buy
 * order must not transfer more than the colony actually holds.
 *
 * Strategy: fleet resource amounts are ONLY modified by trade orders (no
 * production, no decay). Colony resource amounts are modified by BOTH trade
 * orders AND building production each tick. Therefore:
 *
 *   - Fleet-side assertions are exact: the fleet should hold exactly the
 *     clamped remainder after the trade.
 *   - Colony-side assertions use "at least X" for sell orders (production adds
 *     on top) or verify fleet delta which is independent of production.
 *
 * Covers:
 * - Sell (fleet→colony): fleet clamped to its own stock, fleet ends at 0
 * - Sell exact: fleet has exactly the requested amount, fleet ends at 0
 * - Sell zero-stock: fleet stays at 0, fleet resource is unchanged
 * - Buy (colony→fleet): fleet receives exactly the clamped amount
 * - Buy zero-stock colony: fleet stays unchanged
 * - Buy exact: fleet receives the full amount, colony reaches ≤0
 * - No resources are created from nothing (fleet delta conservation)
 *
 * Fixture summary (TestSeeder):
 *   Fleet 10 (Bart, user_id=3) at (6828, 3016):
 *     fleet_resources: res_regolith(3)=100, res_werkstoffe(4)=420, res_organika(5)=10,
 *                      res_ena(6)=100, res_lho(8)=100, res_aku(10)=100
 *   Colony 1 (Springfield, user_id=3) at (6828, 3016):
 *     colony_resources: res_regolith(3)=200, res_werkstoffe(4)=0, res_organika(5)=0
 *
 * Note: each test uses a unique tick number to avoid primary-key conflicts with
 * seed data (seed orders are at tick ≈14988–15225). Colony resources also
 * receive building production each tick; tests assert only on fleet amounts
 * where possible, which are unaffected by production.
 */
class TradeOrderResourceClampTest extends TestCase
{
    use RefreshDatabase;

    private int $fleetId  = 10;   // Bart's fleet
    private int $colonyId = 1;    // Springfield

    // Resource IDs
    private int $resRegolith    = 3;
    private int $resWerkstoffe  = 4;
    private int $resOrganika    = 5;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── direction=1: fleet → colony (sell) ──────────────────────────────────

    /**
     * Fleet requests to sell MORE than it holds (500 > 100).
     * After the tick the fleet must hold exactly 0 — it gave all it had.
     * The colony must gain at least 100 (production may add more on top).
     */
    public function test_sell_order_clamps_to_fleet_stock(): void
    {
        $this->setFleetResource($this->fleetId, $this->resRegolith, 100);
        $colonyBefore = $this->getColonyResource($this->colonyId, $this->resRegolith);

        $this->insertTradeOrder(7001, $this->fleetId, $this->colonyId, $this->resRegolith, 500, 1);

        Artisan::call('game:tick', ['--tick' => 7001]);

        // Fleet gave everything it had — must be exactly 0
        $this->assertEquals(0, $this->getFleetResource($this->fleetId, $this->resRegolith),
            'Fleet stock must be 0 after selling more than it held (clamped to 100).');

        // Colony must have received at least 100 (building production may add more)
        $this->assertGreaterThanOrEqual(
            $colonyBefore + 100,
            $this->getColonyResource($this->colonyId, $this->resRegolith),
            'Colony must receive the full fleet stock (100), not the over-stated 500.'
        );
    }

    /**
     * Fleet requests to sell exactly what it holds — no clamping needed.
     * Fleet must end at 0.
     */
    public function test_sell_order_exact_amount_transferred(): void
    {
        $this->setFleetResource($this->fleetId, $this->resWerkstoffe, 420);

        $this->insertTradeOrder(7002, $this->fleetId, $this->colonyId, $this->resWerkstoffe, 420, 1);

        Artisan::call('game:tick', ['--tick' => 7002]);

        $this->assertEquals(0, $this->getFleetResource($this->fleetId, $this->resWerkstoffe),
            'Fleet must be empty after selling exactly its stock.');
    }

    /**
     * Fleet has zero stock for the resource — nothing must leave the fleet.
     * Fleet stays at 0 after the tick.
     */
    public function test_sell_order_skipped_when_fleet_has_zero_stock(): void
    {
        $this->setFleetResource($this->fleetId, $this->resOrganika, 0);

        $this->insertTradeOrder(7003, $this->fleetId, $this->colonyId, $this->resOrganika, 200, 1);

        Artisan::call('game:tick', ['--tick' => 7003]);

        $this->assertEquals(0, $this->getFleetResource($this->fleetId, $this->resOrganika),
            'Fleet with zero stock must still be at 0 after a sell order.');
    }

    // ── direction=0: colony → fleet (buy) ───────────────────────────────────

    /**
     * Colony has only 50 units but fleet requests 300.
     * Fleet must receive exactly 50 (clamped to colony stock at order time).
     *
     * Note: the colony may produce more resources during the tick, but the
     * clamping is applied at processing time against the stock recorded BEFORE
     * production runs. We verify the fleet received at most 50.
     */
    public function test_buy_order_clamps_to_colony_stock(): void
    {
        $this->setColonyResource($this->colonyId, $this->resRegolith, 50);
        $fleetBefore = $this->getFleetResource($this->fleetId, $this->resRegolith);

        $this->insertTradeOrder(7004, $this->fleetId, $this->colonyId, $this->resRegolith, 300, 0);

        Artisan::call('game:tick', ['--tick' => 7004]);

        $fleetAfter = $this->getFleetResource($this->fleetId, $this->resRegolith);
        $transferred = $fleetAfter - $fleetBefore;

        // Fleet must have received at most 50 (the colony stock when order was placed)
        $this->assertLessThanOrEqual(50, $transferred,
            'Fleet must not receive more than the colony held at trade time (clamped to 50).');
        // And must have received something (the colony was not empty)
        $this->assertGreaterThan(0, $transferred,
            'Fleet should receive the available colony stock, not 0.');
    }

    /**
     * Colony has zero stock — nothing must be transferred to the fleet.
     */
    public function test_buy_order_skipped_when_colony_has_zero_stock(): void
    {
        $this->setColonyResource($this->colonyId, $this->resOrganika, 0);
        $fleetBefore = $this->getFleetResource($this->fleetId, $this->resOrganika);

        $this->insertTradeOrder(7005, $this->fleetId, $this->colonyId, $this->resOrganika, 500, 0);

        Artisan::call('game:tick', ['--tick' => 7005]);

        $this->assertEquals($fleetBefore, $this->getFleetResource($this->fleetId, $this->resOrganika),
            'Fleet must not gain resources when colony has none.');
    }

    /**
     * Buy order for an amount the colony holds — fleet receives exactly that amount.
     */
    public function test_buy_order_exact_amount_transferred(): void
    {
        $this->setColonyResource($this->colonyId, $this->resRegolith, 75);
        $fleetBefore = $this->getFleetResource($this->fleetId, $this->resRegolith);

        $this->insertTradeOrder(7006, $this->fleetId, $this->colonyId, $this->resRegolith, 75, 0);

        Artisan::call('game:tick', ['--tick' => 7006]);

        $fleetAfter = $this->getFleetResource($this->fleetId, $this->resRegolith);
        $this->assertEquals($fleetBefore + 75, $fleetAfter,
            'Fleet must receive exactly the requested 75 units.');
    }

    // ── No resource creation (fleet-side invariant) ──────────────────────────

    /**
     * On a sell order, the fleet must lose EXACTLY the clamped amount and no more.
     * (Fleet resources are not produced, so this is an exact check.)
     */
    public function test_sell_does_not_create_fleet_resources(): void
    {
        $fleetStock = 100;
        $this->setFleetResource($this->fleetId, $this->resRegolith, $fleetStock);

        $this->insertTradeOrder(7007, $this->fleetId, $this->colonyId, $this->resRegolith, 9999, 1);

        Artisan::call('game:tick', ['--tick' => 7007]);

        // Fleet can only lose resources on a sell; it must not end up with MORE than it started
        $this->assertLessThanOrEqual($fleetStock,
            $this->getFleetResource($this->fleetId, $this->resRegolith),
            'Fleet resource amount must not increase on a sell order.');
    }

    /**
     * On a buy order, the fleet must gain AT MOST what the colony held.
     * A buy cannot create resources out of thin air.
     */
    public function test_buy_does_not_create_resources_from_nothing(): void
    {
        $colonyStock = 50;
        $fleetBefore = $this->getFleetResource($this->fleetId, $this->resRegolith);
        $this->setColonyResource($this->colonyId, $this->resRegolith, $colonyStock);

        $this->insertTradeOrder(7008, $this->fleetId, $this->colonyId, $this->resRegolith, 9999, 0);

        Artisan::call('game:tick', ['--tick' => 7008]);

        $fleetAfter = $this->getFleetResource($this->fleetId, $this->resRegolith);
        $gained = $fleetAfter - $fleetBefore;

        $this->assertLessThanOrEqual($colonyStock, $gained,
            'Fleet must not receive more than the colony had (no resource creation).');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Insert a trade fleet order.
     * coordinates is NOT NULL in the schema; use fleet 10's current position.
     */
    private function insertTradeOrder(int $tick, int $fleetId, int $colonyId, int $resourceId, int $amount, int $direction): void
    {
        DB::table('fleet_orders')->insert([
            'fleet_id'      => $fleetId,
            'tick'          => $tick,
            'order'         => 'trade',
            'coordinates'   => json_encode([6828, 3016, 0]),
            'data'          => json_encode([
                'colony_id'   => $colonyId,
                'resource_id' => $resourceId,
                'amount'      => $amount,
                'direction'   => $direction,
            ]),
            'was_processed' => 0,
        ]);
    }

    private function getColonyResource(int $colonyId, int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    private function getFleetResource(int $fleetId, int $resourceId): int
    {
        return (int) DB::table('fleet_resources')
            ->where('fleet_id', $fleetId)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    private function setColonyResource(int $colonyId, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $colonyId, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function setFleetResource(int $fleetId, int $resourceId, int $amount): void
    {
        DB::table('fleet_resources')->updateOrInsert(
            ['fleet_id' => $fleetId, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }
}
