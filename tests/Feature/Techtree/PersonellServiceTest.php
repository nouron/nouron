<?php

namespace Tests\Feature\Techtree;

use App\Models\Advisor;
use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonellServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PersonellService $service;
    protected int $userId   = 3;   // Bart in test data
    protected int $colonyId = 1;
    protected int $fleetId  = 10;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(PersonellService::class);

        // Clear existing seeded advisors for our test colony/fleet so counts are predictable
        Advisor::where('colony_id', $this->colonyId)->delete();
        Advisor::where('fleet_id', $this->fleetId)->delete();

        // 1 engineer: rank 2 = 7 construction AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 5,
        ]);
        // 1 scientist: rank 1 = 4 research AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_SCIENTIST,
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0,
        ]);
        // 1 pilot at colony: rank 1 = 4 navigation AP (advisors are colony-scoped, Option B)
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::PERSONELL_ID_PILOT,
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0,
        ]);
    }

    public function testGetTotalActionPoints(): void
    {
        // 1 engineer rank2 = 7
        $this->assertEquals(7, $this->service->getTotalActionPoints('construction', $this->colonyId));
        // 1 scientist rank1 = 4
        $this->assertEquals(4, $this->service->getTotalActionPoints('knowledge', $this->colonyId));
        // 1 pilot rank1 at colony = 4
        $this->assertEquals(4, $this->service->getTotalActionPoints('navigation', $this->colonyId));
        // unknown = 0
        $this->assertEquals(0, $this->service->getTotalActionPoints('unknown', $this->colonyId));
    }

    public function testGetAvailableActionPoints(): void
    {
        $this->assertEquals(7, $this->service->getAvailableActionPoints('construction', $this->colonyId));
        $this->assertEquals(4,  $this->service->getAvailableActionPoints('navigation', $this->colonyId));
        $this->assertEquals(0,  $this->service->getAvailableActionPoints('unknown', $this->colonyId));
    }

    public function testGetConstructionPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getConstructionPoints($this->colonyId));
    }

    public function testGetResearchPoints(): void
    {
        $this->assertGreaterThan(0, $this->service->getKnowledgePoints($this->colonyId));
    }

    public function testGetNavigationPoints(): void
    {
        $this->assertEquals(4, $this->service->getNavigationPoints($this->colonyId));
    }

    public function testLockActionPoints(): void
    {
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->assertTrue($this->service->lockActionPoints('construction', $this->colonyId, 3));
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints('construction', $this->colonyId));

        $beforeNav = $this->service->getAvailableActionPoints('navigation', $this->colonyId);
        $this->assertTrue($this->service->lockActionPoints('navigation', $this->colonyId, 2));
        $this->assertEquals($beforeNav - 2, $this->service->getAvailableActionPoints('navigation', $this->colonyId));

        $this->assertFalse($this->service->lockActionPoints('unknown', $this->colonyId, 1));
    }

    public function testHire(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->assertInstanceOf(Advisor::class, $advisor);
        $this->assertEquals($this->colonyId, $advisor->colony_id);
        $this->assertEquals(1, $advisor->rank);
        $this->assertNull($advisor->fleet_id);
    }

    public function testFire(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->assertTrue($this->service->fire($advisor->id));
        $advisor->refresh();
        $this->assertNull($advisor->colony_id);
        $this->assertNull($advisor->fleet_id);
        $this->assertDatabaseHas('advisors', ['id' => $advisor->id]);  // still exists
    }

    public function testGetColonyAdvisors(): void
    {
        $advisors = $this->service->getColonyAdvisors($this->colonyId);
        $this->assertGreaterThan(0, $advisors->count());
    }

    // ── Advisor model: getApPerTick ───────────────────────────────────────────

    public function testGetApPerTickRankOne(): void
    {
        $advisor = new Advisor(['rank' => 1]);
        $this->assertEquals(4, $advisor->getApPerTick());
    }

    public function testGetApPerTickRankTwo(): void
    {
        $advisor = new Advisor(['rank' => 2]);
        $this->assertEquals(7, $advisor->getApPerTick());
    }

    public function testGetApPerTickRankThree(): void
    {
        $advisor = new Advisor(['rank' => 3]);
        $this->assertEquals(12, $advisor->getApPerTick());
    }

    public function testGetApPerTickUnknownRankFallsBackToDefault(): void
    {
        // rank 99 is not in AP_BY_RANK — should fall back to 4
        $advisor = new Advisor(['rank' => 99]);
        $this->assertEquals(4, $advisor->getApPerTick());
    }

    // ── Advisor model: isUnemployed ───────────────────────────────────────────

    public function testIsUnemployedWhenBothNullReturnsTrue(): void
    {
        $advisor = new Advisor(['colony_id' => null, 'fleet_id' => null]);
        $this->assertTrue($advisor->isUnemployed());
    }

    public function testIsUnemployedWhenColonySetReturnsFalse(): void
    {
        $advisor = new Advisor(['colony_id' => 1, 'fleet_id' => null]);
        $this->assertFalse($advisor->isUnemployed());
    }

    public function testIsUnemployedWhenFleetSetReturnsFalse(): void
    {
        $advisor = new Advisor(['colony_id' => null, 'fleet_id' => 10]);
        $this->assertFalse($advisor->isUnemployed());
    }

    // ── Advisor model: isAvailable ────────────────────────────────────────────

    public function testIsAvailableWhenNoUnavailableTickSetAlwaysTrue(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => null]);
        $this->assertTrue($advisor->isAvailable(null));
        $this->assertTrue($advisor->isAvailable(99999));
    }

    public function testIsAvailableReturnsFalseWhenCurrentTickIsNull(): void
    {
        // unavailable_until_tick is set but we pass null — can't compare
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertFalse($advisor->isAvailable(null));
    }

    public function testIsAvailableReturnsFalseWhenCurrentTickNotPastThreshold(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertFalse($advisor->isAvailable(100)); // must be strictly greater
        $this->assertFalse($advisor->isAvailable(50));
    }

    public function testIsAvailableReturnsTrueWhenCurrentTickPastThreshold(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertTrue($advisor->isAvailable(101));
    }

    // ── getTotalActionPoints respects unavailable_until_tick ─────────────────

    public function testTotalActionPointsExcludesUnavailableAdvisors(): void
    {
        // Mark the existing scientist as temporarily unavailable
        Advisor::where('colony_id', $this->colonyId)
               ->where('personell_id', PersonellService::PERSONELL_ID_SCIENTIST)
               ->update(['unavailable_until_tick' => 99999]);

        // The unavailable scientist must not count: research AP = 0
        $this->assertEquals(0, $this->service->getTotalActionPoints('knowledge', $this->colonyId));
        // The available engineer must still count: construction AP = 7 (rank 2)
        $this->assertEquals(7, $this->service->getTotalActionPoints('construction', $this->colonyId));
    }

    // ── hire(): rank clamping and validation ──────────────────────────────────

    public function testHireWithRankBelowOneIsClampedToOne(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId, 0);
        $this->assertEquals(1, $advisor->rank);
    }

    public function testHireWithRankAboveThreeIsClampedToThree(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId, 99);
        $this->assertEquals(3, $advisor->rank);
    }

    public function testHireWithNegativeUserIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire(-1, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
    }

    public function testHireWithNegativeColonyIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, -5);
    }

    public function testHiredAdvisorStartsWithZeroActiveTicks(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_SCIENTIST)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_SCIENTIST, $this->colonyId);
        $this->assertEquals(0, $advisor->active_ticks);
        $this->assertNull($advisor->unavailable_until_tick);
        $this->assertFalse($advisor->is_commander);
    }

    // ── fire(): edge cases ────────────────────────────────────────────────────

    public function testFireNonExistentAdvisorReturnsFalse(): void
    {
        $this->assertFalse($this->service->fire(999999));
    }

    public function testFireedAdvisorBecomesUnemployed(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = $this->service->hire($this->userId, PersonellService::PERSONELL_ID_ENGINEER, $this->colonyId);
        $this->service->fire($advisor->id);

        $advisor->refresh();
        $this->assertTrue($advisor->isUnemployed());
    }

    // ── lockActionPoints(): accumulation and negative value sanitisation ──────

    public function testLockActionPointsAccumulatesAcrossMultipleCalls(): void
    {
        $this->service->lockActionPoints('construction', $this->colonyId, 3);
        $this->service->lockActionPoints('construction', $this->colonyId, 2);
        // 7 total − 5 locked = 2
        $this->assertEquals(2, $this->service->getAvailableActionPoints('construction', $this->colonyId));
    }

    public function testLockActionPointsWithNegativeAmountIsSanitised(): void
    {
        // Passing a negative amount: abs() is applied inside the service, so it
        // still reduces available AP (not increases it — no cheat possible).
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->service->lockActionPoints('construction', $this->colonyId, -3);
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints('construction', $this->colonyId));
    }

    public function testGetAvailableActionPointsFloorsAtZeroWhenOverLocked(): void
    {
        // Lock more AP than exist — result must never go negative
        $this->service->lockActionPoints('construction', $this->colonyId, 9999);
        $this->assertEquals(0, $this->service->getAvailableActionPoints('construction', $this->colonyId));
    }

    // ── E1: invest() locks AP (delta-locking) ────────────────────────────────

    /**
     * E1: Calling invest('add') reduces available AP by the amount actually spent.
     *
     * After investing 3 AP into oremine, the AP pool must decrease by exactly 3.
     * This verifies that _invest() calls lockActionPoints() with the delta.
     */
    public function testInvestAddsLocksDeltaAp(): void
    {
        $buildingService = $this->app->make(\App\Services\Techtree\BuildingService::class);

        // Testdata: oremine (27) on colony 1 has ap_spend=10 = ap_for_levelup → already maxed.
        // Reset so there is room to invest.
        \Illuminate\Support\Facades\DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => 27])
            ->update(['ap_spend' => 0]);

        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $buildingService->invest($this->colonyId, 27, 'add', 3);
        $after = $this->service->getAvailableActionPoints('construction', $this->colonyId);

        $this->assertEquals($before - 3, $after);
    }

    /**
     * E2: AP locks are tick-scoped — after the tick advances the full pool is available again.
     *
     * Lock 5 AP in the current tick, then run game:tick to move to the next tick.
     * The locked_actionpoints row belongs to the old tick and is no longer applied.
     */
    public function testApLocksExpireAfterTickAdvance(): void
    {
        $tickBefore = $this->service->getAvailableActionPoints('construction', $this->colonyId);

        $this->service->lockActionPoints('construction', $this->colonyId, 5);
        $this->assertEquals($tickBefore - 5, $this->service->getAvailableActionPoints('construction', $this->colonyId));

        // Advance the tick — GameTick runs with the next tick number so the old lock no longer applies
        $currentTick = $this->app->make(\App\Services\TickService::class)->getTickCount();
        $this->artisan('game:tick', ['--tick' => $currentTick + 1])->assertSuccessful();

        // After tick, available must equal total — no locked AP from the old tick applies.
        // (Moral may change during the tick, so we compare against the new total, not tickBefore.)
        $this->assertEquals(
            $this->service->getTotalActionPoints('construction', $this->colonyId),
            $this->service->getAvailableActionPoints('construction', $this->colonyId)
        );
    }

    // ── getEconomyPoints() convenience wrapper ────────────────────────────────

    public function testGetEconomyPointsReturnsZeroWithNoTraders(): void
    {
        $this->assertEquals(0, $this->service->getEconomyPoints($this->colonyId));
    }

    public function testGetEconomyPointsWithTrader(): void
    {
        Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_TRADER,
            'colony_id'    => $this->colonyId,
            'rank'         => 2,
            'active_ticks' => 0,
        ]);
        $this->assertEquals(7, $this->service->getEconomyPoints($this->colonyId));
    }

    // ── incrementAdvisorTicks() via GameTick command ──────────────────────────

    public function testIncrementAdvisorTicksCountsActiveColonyAdvisor(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => $this->colonyId,
            'rank'         => 1,
            'active_ticks' => 5,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(6, $advisor->active_ticks);
    }

    public function testIncrementAdvisorTicksCountsActiveCommander(): void
    {
        $commander = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_PILOT,
            'fleet_id'     => $this->fleetId,
            'is_commander' => true,
            'rank'         => 1,
            'active_ticks' => 0,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $commander->refresh();
        $this->assertEquals(1, $commander->active_ticks);
    }

    public function testIncrementAdvisorTicksDoesNotCountUnemployedAdvisors(): void
    {
        $unemployed = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => null,
            'fleet_id'     => null,
            'rank'         => 1,
            'active_ticks' => 3,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $unemployed->refresh();
        $this->assertEquals(3, $unemployed->active_ticks); // unchanged
    }

    public function testIncrementAdvisorTicksDoesNotCountUnavailableAdvisors(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $unavailable = Advisor::create([
            'user_id'                => $this->userId,
            'personell_id'           => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'              => $this->colonyId,
            'rank'                   => 1,
            'active_ticks'           => 7,
            'unavailable_until_tick' => 99999,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $unavailable->refresh();
        $this->assertEquals(7, $unavailable->active_ticks); // unchanged
    }

    public function testIncrementAdvisorTicksDoesNotCountFleetPassenger(): void
    {
        // A pilot on a fleet but NOT is_commander — passenger, does not accumulate ticks
        $passenger = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_PILOT,
            'fleet_id'     => $this->fleetId,
            'is_commander' => false,
            'rank'         => 1,
            'active_ticks' => 2,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $passenger->refresh();
        $this->assertEquals(2, $passenger->active_ticks); // unchanged
    }

    public function testRankPromotionToTwoAtTenTicks(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => $this->colonyId,
            'rank'         => 1,
            'active_ticks' => 9,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(10, $advisor->active_ticks);
        $this->assertEquals(2, $advisor->rank);
    }

    public function testRankPromotionToThreeAtTwentyTicks(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => $this->colonyId,
            'rank'         => 2,
            'active_ticks' => 19,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(20, $advisor->active_ticks);
        $this->assertEquals(3, $advisor->rank);
    }

    public function testRankDoesNotPromoteAtRankThree(): void
    {
        Advisor::where('colony_id', $this->colonyId)->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)->delete();
        $advisor = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => $this->colonyId,
            'rank'         => 3,
            'active_ticks' => 99,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(3, $advisor->rank); // stays at 3
    }

    public function testRankPromotion_ApPointsReflectNewRankAfterTick(): void
    {
        // Start with 1 engineer at rank 1, 9 ticks — after one tick it hits 10 and promotes
        Advisor::where('colony_id', $this->colonyId)
               ->where('personell_id', PersonellService::PERSONELL_ID_ENGINEER)
               ->delete();

        $advisor = Advisor::create([
            'user_id'      => $this->userId,
            'personell_id' => PersonellService::PERSONELL_ID_ENGINEER,
            'colony_id'    => $this->colonyId,
            'rank'         => 1,
            'active_ticks' => 9,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();

        // After promotion to rank 2, AP must exceed rank-1 value (4).
        // The exact value depends on the moral multiplier, so we just assert the promotion raised AP.
        $this->assertEquals(2, $advisor->rank);
        $this->assertGreaterThan(4, $this->service->getTotalActionPoints('construction', $this->colonyId));
    }
}
