<?php

namespace Tests\Unit;

/**
 * HangarService unit tests.
 *
 * Covered scenarios:
 *
 *  getHangarSlots
 *    - test_get_hangar_slots_returns_empty_when_no_hangars
 *    - test_get_hangar_slots_returns_sorted_by_instance_id
 *    - test_get_hangar_slots_empty_bay_has_null_ship
 *    - test_get_hangar_slots_occupied_bay_has_ship_data
 *    - test_get_hangar_slots_active_mission_is_populated
 *    - test_get_hangar_slots_recalled_mission_is_not_active_mission
 *
 *  requestShip
 *    - test_request_ship_creates_row_with_building_state
 *    - test_request_ship_throws_for_invalid_ship_id
 *    - test_request_ship_throws_for_insufficient_credits
 *    - test_request_ship_deducts_credits_on_success
 *    - test_request_ship_second_of_same_type_is_allowed
 *    - test_request_ship_creates_pending_when_no_free_slot
 *
 *  dispatchShip
 *    - test_dispatch_ship_sets_dispatched_state_and_creates_mission
 *    - test_dispatch_ship_throws_when_no_ship_in_bay
 *    - test_dispatch_ship_throws_when_ship_not_docked_dispatched
 *    - test_dispatch_ship_throws_when_ship_not_docked_building
 *    - test_dispatch_ship_throws_for_empty_destination
 *    - test_dispatch_ship_throws_for_zero_sol_distance
 *    - test_dispatch_ship_throws_for_negative_sol_distance
 *
 *  recallShip
 *    - test_recall_ship_sets_mission_recalled_and_ship_docked
 *    - test_recall_ship_throws_when_no_active_mission
 *
 *  repairShip
 *    - test_repair_ship_increments_status_points_by_ap_times_two
 *    - test_repair_ship_caps_status_points_at_max
 *    - test_repair_ship_throws_when_no_docked_ship
 *    - test_repair_ship_throws_when_ship_dispatched
 *    - test_repair_ship_throws_when_already_at_full_status
 */

use App\Services\HangarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HangarServiceTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    /** Springfield colony — user_id = 3 (Bart) */
    private const COLONY_ID = 1;

    private const HANGAR_BUILDING = 44;

    private const SHIP_CORVETTE = 37;

    private const SHIP_FREIGHTER = 47;

    private const SHIP_DRONE = 85;

    private const FIXED_TICK = 100;

    private HangarService $hangarService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Pin tick to a deterministic value — TickService accepts it via constructor
        $this->app->instance(TickService::class, new TickService(self::FIXED_TICK));
        $this->hangarService = $this->app->make(HangarService::class);

        // Start each test with a clean hangar slate for colony 1
        $this->clearHangarFixtures();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Remove all hangar buildings, hangar-assigned ships and missions for
     * colony 1 so every test builds its own state from scratch.
     */
    private function clearHangarFixtures(): void
    {
        DB::table('colony_hangar_missions')
            ->where('colony_id', self::COLONY_ID)
            ->delete();

        DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->whereIn('ship_id', [self::SHIP_CORVETTE, self::SHIP_FREIGHTER, self::SHIP_DRONE])
            ->delete();

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HANGAR_BUILDING)
            ->delete();
    }

    /**
     * Insert a hangar bay row and return its instance_id.
     */
    private function insertHangar(int $instanceId, int $level = 1, float $statusPoints = 20.0): int
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => self::COLONY_ID,
            'building_id' => self::HANGAR_BUILDING,
            'instance_id' => $instanceId,
            'level' => $level,
            'status_points' => $statusPoints,
            'ap_spend' => 0,
        ]);

        return $instanceId;
    }

    /**
     * Assign a ship to a hangar bay.
     */
    private function assignShip(
        int $instanceId,
        int $shipId,
        string $state = 'docked',
        float $statusPoints = 20.0,
        int $level = 1
    ): void {
        // Use updateOrInsert because the seeder may have inserted the ship without instance_id
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => $shipId],
            [
                'hangar_instance_id' => $instanceId,
                'ship_state' => $state,
                'level' => $level,
                'status_points' => $statusPoints,
                'ap_spend' => 0,
            ]
        );
    }

    /**
     * Insert an active mission row and return its id.
     */
    private function insertMission(int $instanceId, int $shipId, string $state = 'active', ?int $recallTick = null): int
    {
        return DB::table('colony_hangar_missions')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'instance_id' => $instanceId,
            'ship_id' => $shipId,
            'destination' => 'Test Sector',
            'sol_distance' => 3,
            'dispatch_tick' => self::FIXED_TICK - 10,
            'recall_tick' => $recallTick,
            'state' => $state,
            'created_at' => now(),
        ]);
    }

    // ── getHangarSlots ────────────────────────────────────────────────────────

    public function test_get_hangar_slots_returns_empty_when_no_hangars(): void
    {
        // clearHangarFixtures() already removed all hangars for colony 1
        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $this->assertSame([], $slots, 'Colony with no hangars must return empty array');
    }

    public function test_get_hangar_slots_returns_sorted_by_instance_id(): void
    {
        // Insert in reverse order — service must sort ascending
        $this->insertHangar(10);
        $this->insertHangar(5);
        $this->insertHangar(1);

        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $this->assertCount(3, $slots);
        $this->assertSame(1, $slots[0]['instance_id']);
        $this->assertSame(5, $slots[1]['instance_id']);
        $this->assertSame(10, $slots[2]['instance_id']);
    }

    public function test_get_hangar_slots_empty_bay_has_null_ship(): void
    {
        $this->insertHangar(1);

        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $this->assertCount(1, $slots);
        $this->assertNull($slots[0]['ship'], 'Empty bay must have ship = null');
    }

    public function test_get_hangar_slots_occupied_bay_has_ship_data(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 15.0);

        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $this->assertCount(1, $slots);
        $ship = $slots[0]['ship'];
        $this->assertNotNull($ship, 'Occupied bay must have non-null ship');
        $this->assertSame(self::SHIP_CORVETTE, $ship['ship_id']);
        $this->assertSame('docked', $ship['ship_state']);
        $this->assertSame(15.0, $ship['status_points']);
        $this->assertArrayHasKey('name', $ship);
        $this->assertArrayHasKey('level', $ship);
        $this->assertArrayHasKey('ap_spend', $ship);
        $this->assertArrayHasKey('active_mission', $ship);
    }

    public function test_get_hangar_slots_active_mission_is_populated(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched');
        $missionId = $this->insertMission(1, self::SHIP_DRONE, 'active');

        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $ship = $slots[0]['ship'];
        $this->assertNotNull($ship['active_mission'], 'Dispatched ship must have active_mission populated');
        $this->assertSame((int) $missionId, (int) $ship['active_mission']['id']);
        $this->assertSame('active', $ship['active_mission']['state']);
    }

    public function test_get_hangar_slots_recalled_mission_is_not_active_mission(): void
    {
        $this->insertHangar(2);
        $this->assignShip(2, self::SHIP_FREIGHTER, 'docked');
        // Insert a past mission that was recalled — must NOT appear as active_mission
        $this->insertMission(2, self::SHIP_FREIGHTER, 'recalled', self::FIXED_TICK - 2);

        $slots = $this->hangarService->getHangarSlots(self::COLONY_ID);

        $ship = $slots[0]['ship'];
        $this->assertNull($ship['active_mission'], 'Recalled mission must not populate active_mission');
    }

    // ── requestShip ───────────────────────────────────────────────────────────

    public function test_request_ship_creates_row_with_building_state(): void
    {
        // Free slot available — ship must be auto-assigned to it.
        $this->insertHangar(1);

        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);

        $row = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('ship_id', self::SHIP_DRONE)
            ->first();

        $this->assertNotNull($row, 'colony_ships row must be created after requestShip');
        $this->assertSame('building', $row->ship_state);
        $this->assertSame(1, (int) $row->hangar_instance_id);
        // deliver_at_tick = currentTick + delivery_ticks (drone = 2)
        $this->assertSame(self::FIXED_TICK + 2, (int) $row->deliver_at_tick);
    }

    public function test_request_ship_throws_for_invalid_ship_id(): void
    {
        $this->insertHangar(1);

        $this->expectException(\RuntimeException::class);

        $this->hangarService->requestShip(self::COLONY_ID, 999, false, 0);
    }

    public function test_request_ship_throws_for_insufficient_credits(): void
    {
        $this->insertHangar(1);
        // Zero out credits for Bart (user_id=3)
        DB::table('user_resources')
            ->where('user_id', 3)
            ->update(['credits' => 0]);

        $this->expectException(\RuntimeException::class);

        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);
    }

    public function test_request_ship_deducts_credits_on_success(): void
    {
        $this->insertHangar(1);
        // Drone costs 300 Cr; test seeder gives Bart 2700 Cr
        $before = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');

        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);

        $after = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');
        $this->assertSame($before - 300, $after, 'Credits must be reduced by nexus_cost (drone=300)');
    }

    public function test_request_ship_second_of_same_type_is_allowed(): void
    {
        // requestShip has no per-type uniqueness constraint — two drones are valid.
        $this->insertHangar(1);
        $this->insertHangar(2);

        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);
        // Second request of same ship type must not throw.
        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);

        $count = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('ship_id', self::SHIP_DRONE)
            ->count();

        $this->assertSame(2, $count, 'Two separate colony_ships rows must exist for two drones');
    }

    public function test_request_ship_creates_pending_when_no_free_slot(): void
    {
        // All hangar slots occupied — ship must be created in pending state with no hangar.
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');
        // No further free slots.

        $this->hangarService->requestShip(self::COLONY_ID, self::SHIP_DRONE, false, 0);

        $row = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('ship_id', self::SHIP_DRONE)
            ->whereNull('hangar_instance_id')
            ->first();

        $this->assertNotNull($row, 'Pending ship must be created when no free hangar slot');
        $this->assertSame('pending', $row->ship_state);
        $this->assertNotNull($row->pending_until_tick, 'pending_until_tick must be set');
    }

    // ── dispatchShip ──────────────────────────────────────────────────────────

    public function test_dispatch_ship_sets_dispatched_state_and_creates_mission(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Kuiper Station', 5);

        $ship = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', 1)
            ->first();

        $this->assertSame('dispatched', $ship->ship_state);

        $mission = DB::table('colony_hangar_missions')
            ->where('colony_id', self::COLONY_ID)
            ->where('instance_id', 1)
            ->where('state', 'active')
            ->first();

        $this->assertNotNull($mission, 'An active mission row must be created after dispatch');
        $this->assertSame('Kuiper Station', $mission->destination);
        $this->assertSame(5, (int) $mission->sol_distance);
        $this->assertSame(self::FIXED_TICK, (int) $mission->dispatch_tick);
        $this->assertNull($mission->recall_tick);
    }

    public function test_dispatch_ship_throws_when_no_ship_in_bay(): void
    {
        $this->insertHangar(1);
        // Bay is empty

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Somewhere', 3);
    }

    public function test_dispatch_ship_throws_when_ship_not_docked_dispatched(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Somewhere', 3);
    }

    public function test_dispatch_ship_throws_when_ship_not_docked_building(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'building');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Somewhere', 3);
    }

    public function test_dispatch_ship_throws_for_empty_destination(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, '', 3);
    }

    public function test_dispatch_ship_throws_for_whitespace_only_destination(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, '   ', 3);
    }

    public function test_dispatch_ship_throws_for_zero_sol_distance(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Kuiper Station', 0);
    }

    public function test_dispatch_ship_throws_for_negative_sol_distance(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $this->expectException(\RuntimeException::class);

        $this->hangarService->dispatchShip(self::COLONY_ID, 1, 'Kuiper Station', -5);
    }

    // ── recallShip ────────────────────────────────────────────────────────────

    public function test_recall_ship_sets_mission_recalled_and_ship_docked(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched');
        $missionId = $this->insertMission(1, self::SHIP_DRONE, 'active');

        $this->hangarService->recallShip(self::COLONY_ID, 1);

        $mission = DB::table('colony_hangar_missions')->find($missionId);
        $this->assertSame('recalled', $mission->state);
        $this->assertSame(self::FIXED_TICK, (int) $mission->recall_tick);

        $ship = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', 1)
            ->first();
        $this->assertSame('docked', $ship->ship_state);
    }

    public function test_recall_ship_throws_when_no_active_mission(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_FREIGHTER, 'docked');
        // No mission inserted

        $this->expectException(\RuntimeException::class);

        $this->hangarService->recallShip(self::COLONY_ID, 1);
    }

    public function test_recall_ship_throws_when_only_recalled_mission_exists(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');
        // Insert an already-recalled mission (not 'active')
        $this->insertMission(1, self::SHIP_CORVETTE, 'recalled', self::FIXED_TICK - 5);

        $this->expectException(\RuntimeException::class);

        $this->hangarService->recallShip(self::COLONY_ID, 1);
    }

    // ── repairShip ────────────────────────────────────────────────────────────

    public function test_repair_ship_increments_status_points_by_ap_times_two(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 10.0);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 3);

        $row = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', 1)
            ->first();

        // 10.0 + (3 * 2) = 16.0
        $this->assertSame(16.0, (float) $row->status_points);
        $this->assertSame(3, (int) $row->ap_spend);
    }

    public function test_repair_ship_caps_status_points_at_max(): void
    {
        $this->insertHangar(1);
        // status_points = 18 — spending 5 AP would add 10, but max is 20
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 18.0);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 5);

        $row = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', 1)
            ->first();

        $this->assertSame(20.0, (float) $row->status_points, 'status_points must not exceed 20');
    }

    public function test_repair_ship_large_ap_still_caps_at_max(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 5.0);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 100);

        $row = DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', 1)
            ->first();

        $this->assertSame(20.0, (float) $row->status_points, 'Even with huge AP input status_points must not exceed 20');
    }

    public function test_repair_ship_throws_when_no_ship_in_bay(): void
    {
        $this->insertHangar(1);
        // Bay is empty

        $this->expectException(\RuntimeException::class);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 2);
    }

    public function test_repair_ship_throws_when_ship_dispatched(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched', 10.0);

        $this->expectException(\RuntimeException::class);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 2);
    }

    public function test_repair_ship_throws_when_already_at_full_status(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 20.0); // already full

        $this->expectException(\RuntimeException::class);

        $this->hangarService->repairShip(self::COLONY_ID, 1, 1);
    }
}
