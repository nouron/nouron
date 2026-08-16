<?php

namespace Tests\Feature;

use App\Models\Advisor;
use App\Services\AdvisorService;
use App\Services\Techtree\BuildingService;
use App\Services\Techtree\ResearchService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvisorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdvisorService $service;

    protected int $userId = 3;   // Bart in test data

    protected int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(AdvisorService::class);

        // Clear existing seeded advisors for our test colony so counts are predictable
        Advisor::where('colony_id', $this->colonyId)->delete();

        // Ensure bar is placed so trader can be hired (path-building gate)
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'building_id' => 52],
            ['instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
                'tile_x' => 5, 'tile_y' => 5]
        );

        // 1 engineer: rank 2 = +3 AP into the shared colony pool
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => AdvisorService::idFor('engineer'),
            'colony_id' => $this->colonyId, 'rank' => 2, 'active_ticks' => 5,
        ]);
        // 1 scientist: rank 1 = +2 AP into the shared colony pool
        Advisor::create([
            'user_id' => $this->userId, 'personell_id' => AdvisorService::idFor('scientist'),
            'colony_id' => $this->colonyId, 'rank' => 1, 'active_ticks' => 0,
        ]);
    }

    public function test_get_total_action_points(): void
    {
        // 12 base + engineer rank2 (3) + scientist rank1 (2) = 17 — a shared pool,
        // both advisors contribute regardless of their domain.
        $this->assertEquals(17, $this->service->getTotalActionPoints($this->colonyId));
    }

    public function test_get_available_action_points(): void
    {
        $this->assertEquals(17, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_lock_action_points(): void
    {
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->assertTrue($this->service->lockActionPoints($this->colonyId, 3));
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_get_ap_breakdown(): void
    {
        $breakdown = $this->service->getApBreakdown($this->colonyId);

        $this->assertEquals(12, $breakdown['base']);
        $this->assertEquals(5, $breakdown['advisor']);
        $this->assertEquals($this->service->getTotalActionPoints($this->colonyId), $breakdown['total']);
        $this->assertEquals(1.0, $breakdown['plague_multiplier'], 'plague_multiplier must be 1.0 (inactive) when no plague debuff is active');
    }

    /**
     * getApBreakdown()'s `plague_multiplier` must reflect the active plague
     * reduction — without it the resource-bar popup's visible arithmetic
     * (base + advisor, × multiplier) silently didn't add up to `total` during
     * an active plague, with no row explaining the missing amount.
     */
    public function test_ap_breakdown_plague_multiplier_reflects_active_debuff(): void
    {
        DB::table('glx_colonies')->where('id', $this->colonyId)->update(['plague_until_tick' => 999999]);

        $breakdown = $this->app->make(AdvisorService::class)->getApBreakdown($this->colonyId);

        $this->assertEquals(0.80, $breakdown['plague_multiplier']);
        $this->assertEqualsWithDelta(
            (int) round(($breakdown['base'] + $breakdown['advisor']) * $breakdown['multiplier'] * $breakdown['plague_multiplier']),
            $breakdown['total'],
            0
        );
    }

    /**
     * Seuchenausbruch (GDD §9) debuff: while glx_colonies.plague_until_tick is
     * still in the future, getApBreakdown()/getTotalActionPoints() must shrink the
     * total by config('game.encounter.plague.ap_reduction_pct') (default 0.20).
     */
    public function test_active_plague_debuff_reduces_total_ap_by_configured_percent(): void
    {
        DB::table('glx_colonies')->where('id', $this->colonyId)->update(['plague_until_tick' => 999999]);
        $withPlague = $this->app->make(AdvisorService::class)->getTotalActionPoints($this->colonyId);
        DB::table('glx_colonies')->where('id', $this->colonyId)->update(['plague_until_tick' => null]);
        $baseline = $this->app->make(AdvisorService::class)->getTotalActionPoints($this->colonyId);

        // ap_reduction_pct default 0.20 → plague total should be ~80% of baseline.
        $this->assertLessThan($baseline, $withPlague);
        $this->assertEqualsWithDelta((int) round($baseline * 0.80), $withPlague, 1);
    }

    public function test_credit_ap_raises_available_action_points(): void
    {
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->service->creditAp($this->colonyId, 4);
        $this->assertEquals($before + 4, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_credit_ap_with_zero_or_negative_amount_is_noop(): void
    {
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->service->creditAp($this->colonyId, 0);
        $this->service->creditAp($this->colonyId, -5);
        $this->assertEquals($before, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_credit_ap_nets_against_a_prior_lock(): void
    {
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->service->lockActionPoints($this->colonyId, 5);
        $this->service->creditAp($this->colonyId, 3);
        $this->assertEquals($before - 2, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_hire(): void
    {
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId);
        $this->assertInstanceOf(Advisor::class, $advisor);
        $this->assertEquals($this->colonyId, $advisor->colony_id);
        $this->assertEquals(1, $advisor->rank);
    }

    public function test_fire(): void
    {
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId);
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
        $this->assertEquals(2, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_rank_two(): void
    {
        $advisor = new Advisor(['rank' => 2]);
        $this->assertEquals(3, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_rank_three(): void
    {
        $advisor = new Advisor(['rank' => 3]);
        $this->assertEquals(4, $advisor->getApPerTick());
    }

    public function test_get_ap_per_tick_unknown_rank_falls_back_to_default(): void
    {
        // rank 99 is not in ap_per_rank — should fall back to the Junior (rank-1) value
        $advisor = new Advisor(['rank' => 99]);
        $this->assertEquals(2, $advisor->getApPerTick());
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
        Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 3,
            'active_ticks' => 10,
            'unavailable_until_tick' => 99999,
        ]);

        // 12 base + engineer rank2 (3) + scientist rank1 (2) = 17 — the unavailable
        // trader (rank 3 = +4) does not count.
        $this->assertEquals(17, $this->service->getTotalActionPoints($this->colonyId));
    }

    // ── hire(): rank clamping and validation ──────────────────────────────────

    public function test_hire_with_rank_below_one_is_clamped_to_one(): void
    {
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId, 0);
        $this->assertEquals(1, $advisor->rank);
    }

    public function test_hire_with_rank_above_three_is_clamped_to_three(): void
    {
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId, 99);
        $this->assertEquals(3, $advisor->rank);
    }

    public function test_hire_with_negative_user_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire(-1, AdvisorService::idFor('engineer'), $this->colonyId);
    }

    public function test_hire_with_negative_colony_id_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->hire($this->userId, AdvisorService::idFor('engineer'), -5);
    }

    public function test_hired_advisor_starts_with_zero_active_ticks(): void
    {
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId);
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
        $advisor = $this->service->hire($this->userId, AdvisorService::idFor('trader'), $this->colonyId);
        $this->service->fire($advisor->id);

        $advisor->refresh();
        $this->assertTrue($advisor->isUnemployed());
    }

    // ── lockActionPoints(): accumulation and negative value sanitisation ──────

    public function test_lock_action_points_accumulates_across_multiple_calls(): void
    {
        $this->service->lockActionPoints($this->colonyId, 3);
        $this->service->lockActionPoints($this->colonyId, 2);
        // 17 total − 5 locked = 12
        $this->assertEquals(12, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_lock_action_points_with_negative_amount_is_sanitised(): void
    {
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->service->lockActionPoints($this->colonyId, -3);
        $this->assertEquals($before - 3, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_get_available_action_points_floors_at_zero_when_over_locked(): void
    {
        $this->service->lockActionPoints($this->colonyId, 9999);
        $this->assertEquals(0, $this->service->getAvailableActionPoints($this->colonyId));
    }

    public function test_locking_ap_via_one_advisor_action_reduces_pool_for_all_domains(): void
    {
        // Core behavior of the shared pool: one lock (e.g. from a construction
        // action) reduces the same number a trade action would see — there is
        // no second, independent "economy" remainder anymore.
        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $this->service->lockActionPoints($this->colonyId, 5);
        $this->assertEquals($before - 5, $this->service->getAvailableActionPoints($this->colonyId));
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
        // This test is about the AP lock itself, so the gate must be live: phpunit.xml
        // sets GAME_BYPASS_AP=true globally, and a bypassed invest() locks nothing.
        config(['game.bypass.ap_checks' => false]);

        $buildingService = $this->app->make(BuildingService::class);

        // Testdata: oremine (27) on colony 1 has ap_spend=10 = ap_for_levelup → already maxed.
        // Reset so there is room to invest.
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => 27])
            ->update(['ap_spend' => 0]);

        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $buildingService->invest($this->colonyId, 27, 'add', 3);
        $after = $this->service->getAvailableActionPoints($this->colonyId);

        $this->assertEquals($before - 3, $after);
    }

    public function test_building_and_research_investment_share_one_pool(): void
    {
        config(['game.bypass.ap_checks' => false]);

        $buildingService = $this->app->make(BuildingService::class);
        $researchService = $this->app->make(ResearchService::class);

        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyId, 'building_id' => 27])
            ->update(['ap_spend' => 0]);

        $before = $this->service->getAvailableActionPoints($this->colonyId);
        $buildingService->invest($this->colonyId, 27, 'add', 3);
        $afterBuilding = $this->service->getAvailableActionPoints($this->colonyId);
        $this->assertEquals($before - 3, $afterBuilding);

        // A research investment right after must draw from the SAME remaining
        // pool, not from an untouched "research" pool — this is the behavior
        // that only exists once construction/research share one pool.
        $researchEntity = DB::table('researches')->first();
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $this->colonyId, 'research_id' => $researchEntity->id],
            ['level' => 0, 'ap_spend' => 0, 'status_points' => 0]
        );
        $researchService->invest($this->colonyId, $researchEntity->id, 'add', 2);
        $afterResearch = $this->service->getAvailableActionPoints($this->colonyId);
        $this->assertEquals($afterBuilding - 2, $afterResearch);
    }

    /**
     * E2: AP locks are tick-scoped — after the tick advances the full pool is available again.
     *
     * Lock 5 AP in the current tick, then run game:tick to move to the next tick.
     * The locked_actionpoints row belongs to the old tick and is no longer applied.
     */
    public function test_ap_locks_expire_after_tick_advance(): void
    {
        $tickBefore = $this->service->getAvailableActionPoints($this->colonyId);

        $this->service->lockActionPoints($this->colonyId, 5);
        $this->assertEquals($tickBefore - 5, $this->service->getAvailableActionPoints($this->colonyId));

        // Advance the tick — GameTick runs with the next tick number so the old lock no longer applies
        $currentTick = $this->app->make(TickService::class)->getTickCount();
        $this->artisan('game:tick', ['--tick' => $currentTick + 1])->assertSuccessful();

        // After tick, available must equal total — no locked AP from the old tick applies.
        // (Trust may change during the tick, so we compare against the new total, not tickBefore.)
        $this->assertEquals(
            $this->service->getTotalActionPoints($this->colonyId),
            $this->service->getAvailableActionPoints($this->colonyId)
        );
    }

    // ── incrementAdvisorTicks() via GameTick command ──────────────────────────

    public function test_increment_advisor_ticks_counts_active_colony_advisor(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('trader'),
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
            'personell_id' => AdvisorService::idFor('trader'),
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
            'personell_id' => AdvisorService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 7,
            'unavailable_until_tick' => 99999,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $unavailable->refresh();
        $this->assertEquals(7, $unavailable->active_ticks); // unchanged
    }

    public function test_rank_promotion_to_two_at_fifteen_ticks(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 14,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(15, $advisor->active_ticks);
        $this->assertEquals(2, $advisor->rank);
    }

    public function test_rank_promotion_to_three_at_fortyfive_ticks(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 2,
            'active_ticks' => 44,
        ]);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();
        $this->assertEquals(45, $advisor->active_ticks);
        $this->assertEquals(3, $advisor->rank);
    }

    public function test_rank_does_not_promote_at_rank_three(): void
    {
        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('trader'),
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
        // Start with 1 engineer at rank 1, 14 ticks — after one tick it hits 15 and promotes
        Advisor::where('colony_id', $this->colonyId)
            ->where('personell_id', AdvisorService::idFor('engineer'))
            ->delete();

        $advisor = Advisor::create([
            'user_id' => $this->userId,
            'personell_id' => AdvisorService::idFor('engineer'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 14,
        ]);

        $totalBefore = $this->service->getTotalActionPoints($this->colonyId);

        $this->artisan('game:tick')->assertSuccessful();
        $advisor->refresh();

        // After promotion to rank 2, AP must exceed the pre-promotion total.
        // The exact value depends on the trust multiplier and other advisors already
        // present in the pool, so we compare against a captured baseline instead of
        // a fixed constant (a fixed threshold is unfalsifiable against ap.base=12).
        $this->assertEquals(2, $advisor->rank);
        $this->assertGreaterThan($totalBefore, $this->service->getTotalActionPoints($this->colonyId));
    }
}
