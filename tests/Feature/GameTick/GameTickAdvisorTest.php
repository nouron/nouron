<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 9 — Advisor tick increment and rank promotion.
 *
 * Each tick, assigned advisors (colony_id IS NOT NULL and unavailable_until_tick IS NULL)
 * have their active_ticks incremented.
 *
 * When active_ticks reaches a rank threshold, the advisor is promoted:
 *   rank 1 → rank 2: requires 15 active_ticks, costs 150 Cr (one-time)
 *   rank 2 → rank 3: requires 45 active_ticks, costs 250 Cr (one-time)
 *
 * If the player cannot afford the promotion cost it is deferred until next tick.
 *
 * Covered scenarios:
 *  Happy path:
 *  - active_ticks incremented each tick for assigned advisor
 *  - Advisor promotes from rank 1 to 2 on crossing threshold (with sufficient credits)
 *  - Advisor promotes from rank 2 to 3 on crossing threshold
 *
 *  Edge cases:
 *  - Promotion deferred when insufficient credits
 *  - Promotion fires exactly once (not re-charged on subsequent ticks)
 *  - unavailable_until_tick set → advisor tick NOT incremented
 *  - Unassigned advisor (colony_id=null) NOT incremented
 *
 *  Adversarial:
 *  - active_ticks must never decrease
 *  - Two advisors on same colony both increment independently
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *   Seeded advisor: personell 35 (engineer), rank=1, active_ticks=0
 *   Config rank_thresholds: [1 => 15, 2 => 45]
 *   Config promotion_costs:  [2 => 150, 3 => 250]
 *
 * Uses tick numbers 11500–11549.
 */
class GameTickAdvisorTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    // From config/game.php
    private const RANK1_THRESHOLD = 15;

    private const RANK2_THRESHOLD = 45;

    private const RANK2_COST = 150;

    private const RANK3_COST = 250;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Remove seeded advisors for a clean slate; each test inserts exactly what it needs
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Auto-increments personell_id to avoid UNIQUE(colony_id, personell_id) violations. */
    private int $nextPersonellId = 35;

    private function insertAdvisor(
        int $rank,
        int $activeTicks,
        ?int $unavailableUntilTick = null,
        ?int $colonyId = self::COLONY_ID
    ): int {
        $personellId = $this->nextPersonellId++;

        return DB::table('advisors')->insertGetId([
            'user_id' => self::USER_ID,
            'colony_id' => $colonyId,
            'personell_id' => $personellId,
            'rank' => $rank,
            'active_ticks' => $activeTicks,
            'unavailable_until_tick' => $unavailableUntilTick,
        ]);
    }

    private function getAdvisor(int $id): object
    {
        return DB::table('advisors')->where('id', $id)->first();
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    // ── active_ticks increment ─────────────────────────────────────────────────

    /**
     * active_ticks must increase by 1 each tick for an assigned, available advisor.
     */
    public function test_active_ticks_incremented_for_assigned_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5);

        Artisan::call('game:tick', ['--tick' => 11500]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(6, (int) $advisor->active_ticks,
            'active_ticks must be incremented by 1 each tick');
    }

    /**
     * active_ticks must NOT be incremented when unavailable_until_tick is set.
     */
    public function test_active_ticks_not_incremented_when_advisor_unavailable(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5, unavailableUntilTick: 99999);

        Artisan::call('game:tick', ['--tick' => 11501]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(5, (int) $advisor->active_ticks,
            'active_ticks must not change when unavailable_until_tick is set');
    }

    /**
     * active_ticks must NOT be incremented for an unassigned advisor (colony_id=null).
     */
    public function test_active_ticks_not_incremented_for_unassigned_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 5, colonyId: null);

        Artisan::call('game:tick', ['--tick' => 11502]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(5, (int) $advisor->active_ticks,
            'active_ticks must not change for an unassigned advisor');
    }

    /**
     * Two advisors on the same colony both have their active_ticks incremented independently.
     */
    public function test_two_advisors_both_increment(): void
    {
        $id1 = $this->insertAdvisor(rank: 1, activeTicks: 3);
        $id2 = $this->insertAdvisor(rank: 1, activeTicks: 7);

        Artisan::call('game:tick', ['--tick' => 11503]);

        $this->assertEquals(4, (int) $this->getAdvisor($id1)->active_ticks,
            'First advisor active_ticks must increment');
        $this->assertEquals(8, (int) $this->getAdvisor($id2)->active_ticks,
            'Second advisor active_ticks must increment');
    }

    // ── Rank promotion is manual, not automatic (Owner-Entscheidung F3/A23, 2026-09-09) ──

    /**
     * GameTick must NOT auto-promote an advisor anymore, even when active_ticks
     * crosses the threshold and the player could afford the cost — promotion is
     * now player-triggered via AdvisorService::promote() (see
     * AdvisorPromotionManualTest). active_ticks itself keeps accumulating past
     * the threshold with no cap, so the player can defer indefinitely.
     */
    public function test_gametick_never_auto_promotes_rank1_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        $this->setCredits(10_000);

        Artisan::call('game:tick', ['--tick' => 11510]);

        $advisor = $this->getAdvisor($id);
        $this->assertEquals(1, (int) $advisor->rank, 'GameTick must never auto-promote an advisor');
        $this->assertEquals(self::RANK1_THRESHOLD, (int) $advisor->active_ticks,
            'active_ticks must still increment normally, just without triggering a promotion');
    }

    /**
     * Same regression at the rank 2 → 3 boundary.
     */
    public function test_gametick_never_auto_promotes_rank2_advisor(): void
    {
        $id = $this->insertAdvisor(rank: 2, activeTicks: self::RANK2_THRESHOLD - 1);
        $this->setCredits(10_000);

        Artisan::call('game:tick', ['--tick' => 11530]);

        $this->assertEquals(2, (int) $this->getAdvisor($id)->rank, 'GameTick must never auto-promote an advisor');
    }

    /**
     * active_ticks must keep accumulating past the threshold without a cap —
     * the player can defer the manual promotion for as long as they want.
     */
    public function test_active_ticks_accumulate_past_threshold_without_cap(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD + 10);

        Artisan::call('game:tick', ['--tick' => 11531]);

        $this->assertEquals(self::RANK1_THRESHOLD + 11, (int) $this->getAdvisor($id)->active_ticks);
    }

    /**
     * active_ticks must not decrease under any circumstances.
     */
    public function test_active_ticks_never_decrease(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: 15);

        Artisan::call('game:tick', ['--tick' => 11540]);

        $advisor = $this->getAdvisor($id);
        $this->assertGreaterThanOrEqual(15, (int) $advisor->active_ticks,
            'active_ticks must never decrease after a tick');
    }
}
