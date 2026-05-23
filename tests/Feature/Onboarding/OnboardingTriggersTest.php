<?php

namespace Tests\Feature\Onboarding;

use App\Models\InnnEvent;
use App\Services\OnboardingTriggerService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for the three GameTick onboarding triggers.
 *
 * Each trigger test runs a real game tick against a minimal, isolated DB state
 * that is crafted to hit (or miss) the trigger condition.
 *
 * Covered scenarios:
 *
 * Trigger 1 — onboarding_decay (building SP < 80 % of max_status_points)
 *   - Happy path:  SP drops below 80 % threshold → INNN event created + markFired called
 *   - Already fired: trigger already in fired_triggers → no second event emitted
 *   - Level-down path: SP reaches 0 → level-down event only, no onboarding_decay event
 *
 * Trigger 2 — supply_cap_full (used supply >= cap)
 *   - Happy path:  used supply equals the cap → fired_triggers updated (no INNN event)
 *   - Already fired: trigger in fired_triggers → no duplicate update
 *   - Below cap: used supply < cap → trigger does NOT fire
 *
 * Trigger 3 — onboarding_trust (trust crosses from >= 0 to < 0)
 *   - Happy path:  trust was 0, MoralService drives it negative → INNN event + markFired
 *   - Already fired: trigger in fired_triggers → no second event
 *   - Already negative before tick: trust was -1 before tick → no event (transition already happened)
 *   - NPC colony (user_id = null): trust going negative must NOT fire trigger
 */
class OnboardingTriggersTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingTriggerService $triggerService;

    /** Isolated user / colony IDs that don't collide with testdata fixtures. */
    private int $userId   = 8001;
    private int $colonyId = 8001;
    private int $runId;

    /** system_object_id 3 is free in testdata (not used by colonies 1 or 2). */
    private int $systemObjectId = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->triggerService = $this->app->make(OnboardingTriggerService::class);

        // Pin the tick to a known value so moral_events rows can be inserted at
        // the correct tick number before calling game:tick.
        $this->app->make(\App\Services\TickService::class)->setTickCount(500);

        // Create a minimal user and colony pair for isolated trigger testing.
        DB::table('user')->insertOrIgnore([
            'user_id'        => $this->userId,
            'username'       => 'TriggerTestUser',
            'display_name'   => 'Trigger Test User',
            'role'           => 'player',
            'password'       => bcrypt('pw'),
            'email'          => 'trigger@test.local',
            'activation_key' => 'triggerkey',
            'faction_id'     => 7,
        ]);

        // Use system_object_id 3 (exists in testdata, no colony assigned to it).
        DB::table('glx_colonies')->insertOrIgnore([
            'id'               => $this->colonyId,
            'name'             => 'TriggerTestColony',
            'system_object_id' => $this->systemObjectId,
            'spot'             => 5,
            'user_id'          => $this->userId,
            'since_tick'       => 1,
            'is_primary'       => 1,
        ]);

        DB::table('user_resources')->insertOrIgnore([
            'user_id' => $this->userId,
            'credits' => 3000,
            'supply'  => 10,
        ]);

        // Trust resource (resource_id = 12) starts at 0.
        DB::table('colony_resources')->insertOrIgnore([
            'colony_id'   => $this->colonyId,
            'resource_id' => 12,
            'amount'      => 0,
        ]);

        // CommandCenter at level 1 (required for supply calculation and general sanity).
        // building_id 25 = commandCenter, max_status_points = 20, decay_rate = 0.33
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id'    => $this->colonyId,
            'building_id'  => 25,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 18, // 90 % — well above 80 %, will not trigger onboarding_decay
            'ap_spend'     => 0,
        ]);

        // Run for the test colony — current_tick=500 matches the pinned TickService value.
        // game:tick resolves runs by ID to avoid picking up Bart's seeded run.
        $this->runId = DB::table('runs')->insertGetId([
            'user_id'      => $this->userId,
            'colony_id'    => $this->colonyId,
            'current_tick' => 500,
            'status'       => 'active',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Trigger 1 — onboarding_decay
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * A building whose SP will drop below 80 % of max_status_points after one tick
     * of decay must generate an onboarding_decay INNN event and mark the trigger fired.
     *
     * max_status_points for building 28 (housingComplex) = 20.
     * 80 % threshold = 16.0. MasterDataSeeder sets decay_rate=0.13.
     * Starting at 16.1 (just above), one tick leaves 16.1 - 0.13 = 15.97 — below the threshold.
     */
    public function test_decay_trigger_fires_when_building_crosses_80_percent_threshold(): void
    {
        // Place a housingComplex (building_id 28) at the edge of the 80 % threshold.
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 28,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 16.1, // just above 80 % of 20 → will cross below after one tick
            'ap_spend'     => 0,
        ]);

        $this->assertFalse($this->triggerService->hasFired($this->userId, 'onboarding_decay'));

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $event = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_decay')
            ->first();

        $this->assertNotNull($event, 'onboarding_decay INNN event must be created when SP crosses 80 % threshold');
        $this->assertEquals('techtree', $event->area);

        $params = unserialize($event->parameters);
        $this->assertIsArray($params);
        $this->assertArrayHasKey('colony_id', $params);
        $this->assertEquals($this->colonyId, $params['colony_id']);

        $this->assertTrue(
            $this->triggerService->hasFired($this->userId, 'onboarding_decay'),
            'markFired() must have been called after the event is created'
        );
    }

    /**
     * If the trigger is already fired, a second tick that would cross the threshold
     * again must not create another INNN event.
     */
    public function test_decay_trigger_does_not_fire_twice(): void
    {
        // Pre-mark the trigger as already fired.
        $this->triggerService->markFired($this->userId, 'onboarding_decay');

        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 28,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 16.1,
            'ap_spend'     => 0,
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_decay')
            ->count();

        $this->assertEquals(0, $count, 'No onboarding_decay event must be created when trigger is already fired');
    }

    /**
     * When a building level-downs (SP <= 0), the tick emits a techtree.level_down event
     * but must NOT emit an onboarding_decay event — the two branches are mutually exclusive.
     *
     * MasterDataSeeder sets housingComplex (building_id=28) decay_rate=0.13.
     * SP=0.1 → 0.1 - 0.13 = -0.03 → level-down path (not the SP-update path).
     */
    public function test_decay_trigger_does_not_fire_on_level_down(): void
    {
        // SP so low it will reach 0 after decay — triggers the level-down branch.
        // MasterDataSeeder sets building 28 (housingComplex) decay_rate = 0.13.
        // Starting at 0.1 ensures newStatus = 0.1 - 0.13 = -0.03 ≤ 0 → level-down.
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 28,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 0.1,
            'ap_spend'     => 0,
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_decay')
            ->count();

        $this->assertEquals(0, $count, 'onboarding_decay must NOT fire when the building level-downs (SP <= 0 path)');
    }

    /**
     * A building well above the threshold (e.g. at full 20 SP)
     * must not trigger onboarding_decay even though SP decreases.
     *
     * MasterDataSeeder sets housingComplex (building_id=28) decay_rate=0.13.
     * SP = 20 → 20 - 0.13 = 19.87, still > 16 (80 % threshold).
     */
    public function test_decay_trigger_silent_when_still_above_threshold(): void
    {
        // SP = 20 (100 %). After one tick with decay 0.13 → 19.87 — still > 16.
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 28,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 20,
            'ap_spend'     => 0,
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_decay')
            ->count();

        $this->assertEquals(0, $count, 'onboarding_decay must not fire when SP remains >= 80 % after decay');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Trigger 2 — supply_cap_full
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * When used supply equals or exceeds the cap, the trigger must be marked fired.
     * This trigger emits NO INNN event — it is a UI-only flag.
     *
     * Cap formula: CC_flat (10) + housingComplex.level × 8 = 10 + 2×8 = 26.
     * Used supply: CC (supply_cost=0) + housing (supply_cost=0 per testdata buildings table).
     * To force used >= cap we set a building with a high supply_cost at a level
     * that saturates the cap.
     *
     * Strategy: set CC to level 1 (supply_cost=0, cap contribution=10).
     *           No housing → cap = 10.
     *           Add hangar (building_id=44, supply_cost=12) at level=1 → used=12 >= cap=10.
     */
    public function test_supply_trigger_marks_fired_when_used_supply_reaches_cap(): void
    {
        $this->assertFalse($this->triggerService->hasFired($this->userId, 'supply_cap_full'));

        // Hangar: supply_cost = 12 per level (from testdata buildings table).
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 44,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 20,
            'ap_spend'     => 0,
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $this->assertTrue(
            $this->triggerService->hasFired($this->userId, 'supply_cap_full'),
            'supply_cap_full must be marked fired when used supply >= cap'
        );

        // Confirm no INNN event was created for this trigger (it is UI-only).
        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'supply_cap_full')
            ->count();
        $this->assertEquals(0, $count, 'supply_cap_full must not produce an INNN event');
    }

    /**
     * If the trigger is already fired, a tick that would fire it again must not
     * write anything extra (idempotency at the markFired layer).
     */
    public function test_supply_trigger_does_not_fire_again_when_already_set(): void
    {
        $this->triggerService->markFired($this->userId, 'supply_cap_full');

        // Saturate supply again.
        DB::table('colony_buildings')->insert([
            'colony_id'    => $this->colonyId,
            'building_id'  => 44,
            'instance_id'  => 1,
            'level'        => 1,
            'status_points'=> 20,
            'ap_spend'     => 0,
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        // Verify the fired_triggers JSON contains supply_cap_full exactly once.
        $raw     = DB::table('user_preferences')->where('user_id', $this->userId)->value('fired_triggers');
        $decoded = json_decode($raw, true);
        $count   = array_count_values($decoded)['supply_cap_full'] ?? 0;

        $this->assertEquals(1, $count, 'supply_cap_full must appear exactly once in fired_triggers even after a second tick');
    }

    /**
     * When used supply is below the cap, the trigger must not fire.
     *
     * Setup: CC only (supply_cost=0) → cap=10, used=0 → 0 < 10 → no trigger.
     */
    public function test_supply_trigger_silent_when_below_cap(): void
    {
        // No additional buildings — only CC (supply_cost=0). Used supply = 0, cap = 10.
        Artisan::call('game:tick', ['--run' => $this->runId]);

        $this->assertFalse(
            $this->triggerService->hasFired($this->userId, 'supply_cap_full'),
            'supply_cap_full must not fire when used supply is below cap'
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Trigger 3 — onboarding_trust
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * When trust (resource_id=12) transitions from >= 0 (before tick) to < 0 (after
     * MoralService recalculates), the trigger must emit an onboarding_trust INNN event.
     *
     * We drive moral negative by inserting an 'encounter_lost' moral event (value = -5)
     * into the moral_events table at the pinned tick (500) for this colony.
     * After the tick, MoralService will store moral = -5 in colony_resources, which
     * satisfies the onboarding_trust trigger condition (trustBefore=0 >= 0, trustAfter=-5 < 0).
     */
    public function test_trust_trigger_fires_when_trust_crosses_zero_to_negative(): void
    {
        // Trust starts at 0 (set in setUp); trigger not yet fired.
        $this->assertFalse($this->triggerService->hasFired($this->userId, 'onboarding_trust'));

        // Insert a negative moral event at the pinned tick so MoralService produces -5.
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => 500, // matches the tick pinned in setUp
            'event_type' => 'encounter_lost',
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $moralAfter = (int) DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', 12)
            ->value('amount');

        $this->assertLessThan(0, $moralAfter, 'Trust must be negative after encounter_lost moral event');

        $event = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_trust')
            ->first();

        $this->assertNotNull($event, 'onboarding_trust INNN event must be created when trust crosses 0→negative');
        $this->assertEquals('colony', $event->area);

        $params = unserialize($event->parameters);
        $this->assertIsArray($params);
        $this->assertArrayHasKey('colony_id', $params);
        $this->assertEquals($this->colonyId, $params['colony_id']);

        $this->assertTrue(
            $this->triggerService->hasFired($this->userId, 'onboarding_trust'),
            'markFired() must have been called after the onboarding_trust event is created'
        );
    }

    /**
     * If the trigger was already fired in a previous tick, a subsequent tick that
     * would cross the same threshold must not create another event.
     */
    public function test_trust_trigger_does_not_fire_twice(): void
    {
        $this->triggerService->markFired($this->userId, 'onboarding_trust');

        // Force moral to go negative via encounter_lost event.
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => 500,
            'event_type' => 'encounter_lost',
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_trust')
            ->count();

        $this->assertEquals(0, $count, 'No onboarding_trust event must be emitted when trigger is already fired');
    }

    /**
     * When trust is already negative BEFORE the tick, the trigger must not fire —
     * the transition (>= 0 → < 0) already happened in an earlier tick.
     *
     * GameTick only reads trustBefore when hasFired() is false AND trustBefore >= 0.
     * When trustBefore < 0 the condition `trustBefore >= 0` is false, so the event
     * check is skipped entirely.
     */
    public function test_trust_trigger_silent_when_trust_was_already_negative(): void
    {
        // Overwrite trust to be already negative before the tick starts.
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', 12)
            ->update(['amount' => -5]);

        // Also insert a moral event so the result stays negative after recalculation.
        DB::table('moral_events')->insert([
            'colony_id'  => $this->colonyId,
            'tick'       => 500,
            'event_type' => 'encounter_lost',
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding_trust')
            ->count();

        $this->assertEquals(0, $count, 'onboarding_trust must NOT fire when trust was already negative before the tick');
    }

    /**
     * NPC colonies (user_id = null / 0) must never trigger onboarding_trust.
     *
     * Colony 2 (Shelbyville) has user_id = 0 in testdata — the GameTick guard requires
     * $userId !== null before even reading trustBefore, so no event must be emitted.
     */
    public function test_trust_trigger_does_not_fire_for_npc_colony(): void
    {
        // Shelbyville (id=2) has user_id=0 in testdata — the v_glx_colonies view
        // exposes user_id=0 which the GameTick treats as a non-null value (0).
        // To test the actual guard we need a colony where user_id IS NULL.
        // Shelbyville's user_id stored as integer 0 means the PHP null check
        // ($colony->user_id ?? null) returns 0 (truthy-ish), not null.
        // We create a truly NPC colony with user_id = null.
        DB::table('glx_colonies')->insert([
            'id'               => 9999,
            'name'             => 'NpcColony',
            'system_object_id' => 4,  // asteroid object — not used by any colony
            'spot'             => 1,
            'user_id'          => null,
            'since_tick'       => 1,
            'is_primary'       => 1,
        ]);

        DB::table('colony_resources')->insertOrIgnore([
            'colony_id'   => 9999,
            'resource_id' => 12,
            'amount'      => 0,
        ]);

        // Moral event for the NPC colony so trust would go negative.
        DB::table('moral_events')->insert([
            'colony_id'  => 9999,
            'tick'       => 500,
            'event_type' => 'encounter_lost',
        ]);

        Artisan::call('game:tick', ['--run' => $this->runId]);

        // No onboarding_trust event must exist for any user because user_id is null.
        $count = InnnEvent::where('event', 'onboarding_trust')->count();

        $this->assertEquals(0, $count, 'onboarding_trust must not be created for NPC colonies (user_id = null)');
    }
}
