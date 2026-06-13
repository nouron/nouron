<?php

namespace Tests\Feature\Techtree;

use App\Models\Advisor;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersonellServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PersonellService $service;

    protected int $userId = 3;   // Bart in test data

    protected int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(PersonellService::class);

        // Clear existing seeded advisors for our test colony so counts are predictable
        Advisor::where('colony_id', $this->colonyId)->delete();

        // 1 engineer: rank 2 (7 AP) = 7 construction AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::idFor('engineer'),
            'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 5,
        ]);
        // 1 scientist: rank 1 = 4 research AP
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => PersonellService::idFor('scientist'),
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0,
        ]);
    }

    public function test_get_total_action_points(): void
    {
        // 6 base + engineer rank2 (7) = 13
        $this->assertEquals(13, $this->service->getTotalActionPoints('construction', $this->colonyId));
        // 6 base + scientist rank1 (4) = 10
        $this->assertEquals(10, $this->service->getTotalActionPoints('research', $this->colonyId));
        // unknown type: no base AP (early return)
        $this->assertEquals(0, $this->service->getTotalActionPoints('unknown', $this->colonyId));
    }

    public function test_get_available_action_points(): void
    {
        $this->assertEquals(13, $this->service->getAvailableActionPoints('construction', $this->colonyId));
        $this->assertEquals(0, $this->service->getAvailableActionPoints('unknown', $this->colonyId));
    }

    public function test_get_construction_points(): void
    {
        $this->assertGreaterThan(0, $this->service->getConstructionPoints($this->colonyId));
    }

    public function test_get_research_points(): void
    {
        $this->assertGreaterThan(0, $this->service->getResearchPoints($this->colonyId));
    }

    public function test_lock_action_points(): void
    {
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->assertTrue($this->service->lockActionPoints('construction', $this->colonyId, 3));
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints('construction', $this->colonyId));

        $this->assertFalse($this->service->lockActionPoints('unknown', $this->colonyId, 1));
    }

    public function test_hire(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId);
        $this->assertInstanceOf(Advisor::class, $advisor);
        $this->assertEquals($this->colonyId, $advisor->colony_id);
        $this->assertEquals(1, $advisor->rank);
    }

    public function test_fire(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId);
        $this->assertTrue($this->service->fire($advisor->id));
        $advisor->refresh();
        $this->assertNull($advisor->colony_id);
        $this->assertDatabaseHas('advisors', ['id' => $advisor->id]);  // still exists
    }

    public function test_get_colony_advisors(): void
    {
        $advisors = $this->service->getColonyAdvisors($this->colonyId);
        $this->assertGreaterThan(0, $advisors->count());
    }

    // ── Advisor model: getApPerTick ───────────────────────────────────────────

    public function test_get_ap_per_tick_rank_one(): void
    {
        $advisor = new Advisor(['rank' => 1]);
        $this->assertEquals(4, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_rank_two(): void
    {
        $advisor = new Advisor(['rank' => 2]);
        $this->assertEquals(7, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_rank_three(): void
    {
        $advisor = new Advisor(['rank' => 3]);
        $this->assertEquals(12, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_unknown_rank_falls_back_to_default(): void
    {
        // rank 99 is not in AP_BY_RANK — should fall back to 4
        $advisor = new Advisor(['rank' => 99]);
        $this->assertEquals(4, $advisor->getApPerTick());
    }

    // ── Advisor model: isUnemployed ───────────────────────────────────────────

    public function test_is_unemployed_when_both_null_returns_true(): void
    {
        $advisor = new Advisor(['colony_id' => null]);
        $this->assertTrue($advisor->isUnemployed());
    }

    public function test_is_unemployed_when_colony_set_returns_false(): void
    {
        $advisor = new Advisor(['colony_id' => 1]);
        $this->assertFalse($advisor->isUnemployed());
    }

    // ── Advisor model: isAvailable ────────────────────────────────────────────

    public function test_is_available_when_no_unavailable_tick_set_always_true(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => null]);
        $this->assertTrue($advisor->isAvailable(null));
        $this->assertTrue($advisor->isAvailable(99999));
    }

    public function test_is_available_returns_false_when_current_tick_is_null(): void
    {
        // unavailable_until_tick is set but we pass null — can't compare
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertFalse($advisor->isAvailable(null));
    }

    public function test_is_available_returns_false_when_current_tick_not_past_threshold(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertFalse($advisor->isAvailable(100)); // must be strictly greater
        $this->assertFalse($advisor->isAvailable(50));
    }

    public function test_is_available_returns_true_when_current_tick_past_threshold(): void
    {
        $advisor = new Advisor(['unavailable_until_tick' => 100]);
        $this->assertTrue($advisor->isAvailable(101));
    }

    // ── getTotalActionPoints respects unavailable_until_tick ─────────────────

    public function test_total_action_points_excludes_unavailable_advisors(): void
    {
        // Add a trader that is temporarily unavailable — economy AP should be 0
        Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 3,
            'active_ticks' => 10,
            'unavailable_until_tick' => 99999,
        ]);

        // Economy AP should be base 6 only — the unavailable trader is excluded
        $this->assertEquals(6, $this->service->getTotalActionPoints('economy', $this->colonyId));
    }

    // ── hire(): rank clamping and validation ──────────────────────────────────

    public function test_hire_with_rank_below_one_is_clamped_to_one(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId, 0);
        $this->assertEquals(1, $advisor->rank);
    }

    public function test_hire_with_rank_above_three_is_clamped_to_three(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId, 99);
        $this->assertEquals(3, $advisor->rank);
    }

    public function test_hire_with_negative_user_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire(-1, PersonellService::idFor('engineer'), $this->colonyId);
    }

    public function test_hire_with_negative_colony_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire($this->userId, PersonellService::idFor('engineer'), -5);
    }

    public function test_hired_advisor_starts_with_zero_active_ticks(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId);
        $this->assertEquals(0, $advisor->active_ticks);
        $this->assertNull($advisor->unavailable_until_tick);
    }

    // ── fire(): edge cases ────────────────────────────────────────────────────

    public function test_fire_non_existent_advisor_returns_false(): void
    {
        $this->assertFalse($this->service->fire(999999));
    }

    public function test_fireed_advisor_becomes_unemployed(): void
    {
        $advisor = $this->service->hire($this->userId, PersonellService::idFor('trader'), $this->colonyId);
        $this->service->fire($advisor->id);

        $advisor->refresh();
        $this->assertTrue($advisor->isUnemployed());
    }

    // ── lockActionPoints(): accumulation and negative value sanitisation ──────

    public function test_lock_action_points_accumulates_across_multiple_calls(): void
    {
        $this->service->lockActionPoints('construction', $this->colonyId, 3);
        $this->service->lockActionPoints('construction', $this->colonyId, 2);
        // 13 total − 5 locked = 8
        $this->assertEquals(8, $this->service->getAvailableActionPoints('construction', $this->colonyId));
    }

    public function test_lock_action_points_with_negative_amount_is_sanitised(): void
    {
        // Passing a negative amount: abs() is applied inside the service, so it
        // still reduces available AP (not increases it — no cheat possible).
        $before = $this->service->getAvailableActionPoints('construction', $this->colonyId);
        $this->service->lockActionPoints('construction', $this->colonyId, -3);
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints('construction', $this->colonyId));
    }

    public function test_get_available_action_points_floors_at_zero_when_over_locked(): void
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
    public function test_invest_adds_locks_delta_ap(): void
    {
        $buildingService = $this->app->make(BuildingService::class);

        // Testdata: oremine (27) on colony 1 has ap_spend=10 = ap_for_levelup → already maxed.
        // Reset so there is room to invest.
        DB::table('colony_buildings')
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
    public function test_ap_locks_expire_after_tick_advance(): void
    {
        $tickBefore = $this->service->getAvailableActionPoints('construction', $this->colonyId);

        $this->service->lockActionPoints('construction', $this->colonyId, 5);
        $this->assertEquals($tickBefore - 5, $this->service->getAvailableActionPoints('construction', $this->colonyId));

        // Advance the tick — GameTick runs with the next tick number so the old lock no longer applies
        $currentTick = $this->app->make(TickService::class)->getTickCount();
        $this->artisan('game:tick', ['--tick' => $currentTick + 1])->assertSuccessful();

        // After tick, available must equal total — no locked AP from the old tick applies.
        // (Moral may change during the tick, so we compare against the new total, not tickBefore.)
        $this->assertEquals(
            $this->service->getTotalActionPoints('construction', $this->colonyId),
            $this->service->getAvailableActionPoints('construction', $this->colonyId)
        );
    }

    // ── getEconomyPoints() convenience wrapper ────────────────────────────────

    public function test_get_economy_points_returns_base_with_no_traders(): void
    {
        // Base 6 AP always present even without advisor
        $this->assertEquals(6, $this->service->getEconomyPoints($this->colonyId));
    }

    public function test_get_economy_points_with_trader(): void
    {
        Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 2,
            'active_ticks' => 0,
        ]);
        // 6 base + trader rank2 (7) = 13
        $this->assertEquals(13, $this->service->getEconomyPoints($this->colonyId));
    }

    // ── incrementAdvisorTicks() via GameTick command ──────────────────────────

    public function test_increment_advisor_ticks_counts_active_colony_advisor(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 5,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(6, $advisor->active_ticks);
    }

    public function test_increment_advisor_ticks_does_not_count_unemployed_advisors(): void
    {
        $unemployed = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => null,
            'rank' => 1,
            'active_ticks' => 3,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $unemployed->refresh();
        $this->assertEquals(3, $unemployed->active_ticks); // unchanged
    }

    public function test_increment_advisor_ticks_does_not_count_unavailable_advisors(): void
    {
        $unavailable = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 7,
            'unavailable_until_tick' => 99999,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $unavailable->refresh();
        $this->assertEquals(7, $unavailable->active_ticks); // unchanged
    }

    public function test_rank_promotion_to_two_at_ten_ticks(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 9,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(10, $advisor->active_ticks);
        $this->assertEquals(2, $advisor->rank);
    }

    public function test_rank_promotion_to_three_at_twenty_ticks(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 2,
            'active_ticks' => 19,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(20, $advisor->active_ticks);
        $this->assertEquals(3, $advisor->rank);
    }

    public function test_rank_does_not_promote_at_rank_three(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 3,
            'active_ticks' => 99,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(3, $advisor->rank); // stays at 3
    }

    public function test_rank_promotion_ap_points_reflect_new_rank_after_tick(): void
    {
        // Start with 1 engineer at rank 1, 9 ticks — after one tick it hits 10 and promotes
        Advisor::where('colony_id', $this->colonyId)
            ->where('personell_id', PersonellService::idFor('engineer'))
            ->delete();

        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('engineer'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
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
