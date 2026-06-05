<?php

namespace Tests\Feature\Hangar;

/**
 * HangarController feature tests.
 *
 * Covered scenarios:
 *
 *  AUTH GUARD
 *    - test_index_requires_auth
 *    - test_request_ship_requires_auth
 *    - test_dispatch_requires_auth
 *    - test_recall_requires_auth
 *    - test_repair_requires_auth
 *
 *  INDEX
 *    - test_index_returns_200_with_required_view_data
 *    - test_index_view_has_pilot_false_when_no_advisor
 *    - test_index_view_slots_count_matches_hangar_bays
 *
 *  REQUEST SHIP
 *    - test_request_ship_returns_ok_with_slots_and_pending
 *    - test_request_ship_returns_422_for_invalid_ship_id
 *    - test_request_ship_returns_422_for_missing_ship_id
 *    - test_request_ship_returns_422_for_insufficient_credits
 *    - test_request_ship_returns_422_for_negative_consul_ap
 *
 *  DISPATCH
 *    - test_dispatch_returns_ok_with_slot
 *    - test_dispatch_returns_422_missing_destination
 *    - test_dispatch_returns_422_when_ship_not_docked
 *
 *  RECALL
 *    - test_recall_returns_ok_with_slot
 *    - test_recall_returns_422_when_no_active_mission
 *
 *  REPAIR
 *    - test_repair_returns_ok_with_slot
 *    - test_repair_returns_422_for_zero_ap_spent
 *    - test_repair_returns_422_when_ship_at_full_status
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HangarControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const USER_ID_BART   = 3;
    private const COLONY_ID_BART = 1;
    private const HANGAR_BUILDING = 44;
    private const SHIP_CORVETTE  = 37;
    private const SHIP_FREIGHTER = 47;
    private const SHIP_DRONE     = 85;
    private const FIXED_TICK     = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(self::FIXED_TICK));

        // Wipe hangar state from seeder so each test is independent
        $this->clearHangarFixtures();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    private function clearHangarFixtures(): void
    {
        DB::table('colony_hangar_missions')
            ->where('colony_id', self::COLONY_ID_BART)
            ->delete();

        DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID_BART)
            ->whereIn('ship_id', [self::SHIP_CORVETTE, self::SHIP_FREIGHTER, self::SHIP_DRONE])
            ->delete();

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID_BART)
            ->where('building_id', self::HANGAR_BUILDING)
            ->delete();
    }

    private function insertHangar(int $instanceId, float $statusPoints = 20.0): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id'     => self::COLONY_ID_BART,
            'building_id'   => self::HANGAR_BUILDING,
            'instance_id'   => $instanceId,
            'level'         => 1,
            'status_points' => $statusPoints,
            'ap_spend'      => 0,
        ]);
    }

    private function assignShip(int $instanceId, int $shipId, string $state, float $statusPoints = 20.0): void
    {
        // Use updateOrInsert because the seeder may have already inserted a row
        // for this (colony_id, ship_id) PK without hangar_instance_id set.
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'ship_id' => $shipId],
            [
                'hangar_instance_id' => $instanceId,
                'ship_state'         => $state,
                'level'              => 1,
                'status_points'      => $statusPoints,
                'ap_spend'           => 0,
            ]
        );
    }

    private function insertActiveMission(int $instanceId, int $shipId): int
    {
        return DB::table('colony_hangar_missions')->insertGetId([
            'colony_id'    => self::COLONY_ID_BART,
            'instance_id'  => $instanceId,
            'ship_id'      => $shipId,
            'destination'  => 'Kuiper Belt',
            'sol_distance' => 4,
            'dispatch_tick' => self::FIXED_TICK - 5,
            'recall_tick'  => null,
            'state'        => 'active',
            'created_at'   => now(),
        ]);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $this->get(route('colony.hangar'))
            ->assertRedirect(route('login'));
    }

    public function test_request_ship_requires_auth(): void
    {
        $this->postJson(route('colony.hangar.request'))
            ->assertUnauthorized();
    }

    public function test_dispatch_requires_auth(): void
    {
        $this->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]))
            ->assertUnauthorized();
    }

    public function test_recall_requires_auth(): void
    {
        $this->postJson(route('colony.hangar.recall', ['instanceId' => 1]))
            ->assertUnauthorized();
    }

    public function test_repair_requires_auth(): void
    {
        $this->postJson(route('colony.hangar.repair', ['instanceId' => 1]))
            ->assertUnauthorized();
    }

    // ── INDEX ─────────────────────────────────────────────────────────────────

    public function test_index_returns_200_with_required_view_data(): void
    {
        $this->insertHangar(1);

        $response = $this->actingAs($this->bart())
            ->get(route('colony.hangar'));

        $response->assertOk();
        $response->assertViewIs('colony.hangar');
        $response->assertViewHasAll([
            'slots',
            'shipTypes',
            'hasPilot',
            'pendingShips',
            'shipCosts',
            'canUseNexusCredit',
            'hasAktivierterKonsul',
            'verfuegbareVerhandlungsAP',
            'commissionedShipIds',
        ]);
    }

    public function test_index_view_has_pilot_false_when_no_advisor(): void
    {
        // Ensure no pilot advisor (personell_id=89) is assigned to colony 1
        DB::table('advisors')
            ->where('colony_id', self::COLONY_ID_BART)
            ->where('personell_id', 89)
            ->delete();

        $response = $this->actingAs($this->bart())
            ->get(route('colony.hangar'));

        $response->assertOk();
        $this->assertFalse($response->viewData('hasPilot'));
    }

    public function test_index_view_slots_count_matches_hangar_bays(): void
    {
        $this->insertHangar(1);
        $this->insertHangar(2);

        $response = $this->actingAs($this->bart())
            ->get(route('colony.hangar'));

        $response->assertOk();
        $slots = $response->viewData('slots');
        $this->assertCount(2, $slots);
    }

    // ── REQUEST SHIP ──────────────────────────────────────────────────────────

    public function test_request_ship_returns_ok_with_slots_and_pending(): void
    {
        $this->insertHangar(1);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), [
                'ship_id'          => self::SHIP_DRONE,
                'use_nexus_credit' => 0,
                'consul_ap_spent'  => 0,
            ]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slots', 'pending']);

        // Drone must appear in slots for hangar bay 1, with state=building
        $slots = $response->json('slots');
        $this->assertNotEmpty($slots);
        $droneSlot = collect($slots)->firstWhere('instance_id', 1);
        $this->assertNotNull($droneSlot);
        $this->assertSame(self::SHIP_DRONE, $droneSlot['ship']['ship_id']);
        $this->assertSame('building', $droneSlot['ship']['ship_state']);
    }

    public function test_request_ship_returns_422_for_invalid_ship_id(): void
    {
        $this->insertHangar(1);

        // ship_id=999 is not in the allowed list [37, 47, 85]
        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), [
                'ship_id' => 999,
            ]);

        // Laravel validation rejects unknown ship_id via `in:37,47,85` rule
        $response->assertStatus(422);
    }

    public function test_request_ship_returns_422_for_missing_ship_id(): void
    {
        $this->insertHangar(1);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), []);

        $response->assertStatus(422);
    }

    public function test_request_ship_returns_422_for_insufficient_credits(): void
    {
        $this->insertHangar(1);
        // Zero out Bart's credits so the purchase cannot proceed
        DB::table('user_resources')
            ->where('user_id', self::USER_ID_BART)
            ->update(['credits' => 0]);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), [
                'ship_id'          => self::SHIP_DRONE,
                'use_nexus_credit' => 0,
                'consul_ap_spent'  => 0,
            ]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_request_ship_returns_422_for_negative_consul_ap(): void
    {
        $this->insertHangar(1);

        // consul_ap_spent must be >= 0 (Laravel validation rule min:0)
        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), [
                'ship_id'         => self::SHIP_DRONE,
                'consul_ap_spent' => -1,
            ]);

        $response->assertStatus(422);
    }

    // ── DISPATCH ──────────────────────────────────────────────────────────────

    public function test_dispatch_returns_ok_with_slot(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
                'destination'  => 'Asteroid Belt',
                'sol_distance' => 3,
            ]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slot']);

        $slot = $response->json('slot');
        $this->assertNotNull($slot['ship']);
        $this->assertSame('dispatched', $slot['ship']['ship_state']);
        $this->assertNotNull($slot['ship']['active_mission']);
    }

    public function test_dispatch_returns_422_missing_destination(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
                'sol_distance' => 3,
                // destination omitted
            ]);

        $response->assertStatus(422);
    }

    public function test_dispatch_returns_422_missing_sol_distance(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
                'destination' => 'Somewhere',
                // sol_distance omitted
            ]);

        $response->assertStatus(422);
    }

    public function test_dispatch_returns_422_when_ship_not_docked(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched');
        $this->insertActiveMission(1, self::SHIP_DRONE);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
                'destination'  => 'Deep Space',
                'sol_distance' => 5,
            ]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_dispatch_returns_422_for_sol_distance_zero(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
                'destination'  => 'Somewhere',
                'sol_distance' => 0,
            ]);

        // Laravel validation rule min:1 blocks this before service is called
        $response->assertStatus(422);
    }

    // ── RECALL ────────────────────────────────────────────────────────────────

    public function test_recall_returns_ok_with_slot(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_DRONE, 'dispatched');
        $this->insertActiveMission(1, self::SHIP_DRONE);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.recall', ['instanceId' => 1]));

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slot']);

        $slot = $response->json('slot');
        $this->assertNotNull($slot['ship']);
        $this->assertSame('docked', $slot['ship']['ship_state']);
        $this->assertNull($slot['ship']['active_mission']);
    }

    public function test_recall_returns_422_when_no_active_mission(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked');
        // No mission inserted

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.recall', ['instanceId' => 1]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    // ── REPAIR ────────────────────────────────────────────────────────────────

    public function test_repair_returns_ok_with_slot(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 10.0);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.repair', ['instanceId' => 1]), [
                'ap_spent' => 3,
            ]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'slot']);

        $slot = $response->json('slot');
        $this->assertNotNull($slot['ship']);
        // 10.0 + 3*2 = 16.0
        $this->assertSame(16.0, (float) $slot['ship']['status_points']);
    }

    public function test_repair_returns_422_for_zero_ap_spent(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 10.0);

        // Laravel validation rule min:1 blocks ap_spent=0
        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.repair', ['instanceId' => 1]), [
                'ap_spent' => 0,
            ]);

        $response->assertStatus(422);
    }

    public function test_repair_returns_422_when_missing_ap_spent(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 10.0);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.repair', ['instanceId' => 1]), []);

        $response->assertStatus(422);
    }

    public function test_repair_returns_422_when_ship_at_full_status(): void
    {
        $this->insertHangar(1);
        $this->assignShip(1, self::SHIP_CORVETTE, 'docked', 20.0); // already full

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.repair', ['instanceId' => 1]), [
                'ap_spent' => 1,
            ]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    // ── Adversarial / cross-colony ────────────────────────────────────────────

    public function test_request_ship_only_deducts_credits_from_requesting_user(): void
    {
        // Bart requests a drone — only Bart's credits should decrease,
        // not the credits of any other user.
        $this->insertHangar(1);

        // Record credits for both users before the request.
        // user_id=1 (Homer), user_id=3 (Bart)
        $bartCreditsBefore  = (int) DB::table('user_resources')->where('user_id', self::USER_ID_BART)->value('credits');
        $otherUsersBefore   = DB::table('user_resources')
            ->where('user_id', '!=', self::USER_ID_BART)
            ->pluck('credits', 'user_id')
            ->all();

        $this->actingAs($this->bart())
            ->postJson(route('colony.hangar.request'), [
                'ship_id'          => self::SHIP_DRONE,
                'use_nexus_credit' => 0,
                'consul_ap_spent'  => 0,
            ])
            ->assertOk();

        // Bart's credits must have been reduced by drone cost (300 Cr).
        $bartCreditsAfter = (int) DB::table('user_resources')->where('user_id', self::USER_ID_BART)->value('credits');
        $this->assertSame($bartCreditsBefore - 300, $bartCreditsAfter);

        // All other users' credits must be unchanged.
        foreach ($otherUsersBefore as $userId => $creditsBefore) {
            $creditsAfter = (int) DB::table('user_resources')->where('user_id', $userId)->value('credits');
            $this->assertSame((int) $creditsBefore, $creditsAfter, "Credits for user {$userId} must not change");
        }
    }
}
