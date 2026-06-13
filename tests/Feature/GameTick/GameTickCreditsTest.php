<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick steps 8c + 8d — Passive Credits generation and Advisor upkeep.
 *
 * Step 8c — Passive Credits (generatePassiveCredits):
 *   Formula: nexus_subsidy (30) + housingComplex.level × tax_per_housing (20)
 *   Only colonies where CC level > 0 receive credits.
 *   NPC colonies (user_id = null) are skipped.
 *
 * Step 8d — Advisor upkeep (deductAdvisorUpkeep):
 *   Upkeep per rank: 1 → 10 Cr, 2 → 50 Cr, 3 → 160 Cr
 *   Deducted AFTER passive credits (so income is applied before costs).
 *   Credits clamped to ≥ 0 — never goes negative from upkeep alone.
 *   Advisors without a colony assignment incur no upkeep.
 *
 * Covered scenarios:
 *  Happy path:
 *  - Nexus subsidy (30 Cr) added each tick when CC > 0
 *  - Housing tax added per level
 *  - Advisor upkeep deducted (rank 1 = 10 Cr)
 *  - Net income = passive - upkeep for one rank-1 advisor
 *
 *  Edge cases:
 *  - No CC → no passive credits at all
 *  - Advisor upkeep clamped to 0 (credits cannot go negative)
 *  - Multiple advisors: each deducts independently
 *  - Advisor rank 2 (50 Cr) and rank 3 (160 Cr) upkeep correct
 *
 *  Adversarial:
 *  - Upkeep fires AFTER passive income (order of operations)
 *  - Unassigned advisor (colony_id=null) has no upkeep
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC (building_id=25): level=3 → passive subsidy fires
 *     housing (building_id=28): level=2 → +40 Cr housing tax
 *   user_resources: user 3, credits=2700
 *   Advisor id seeded: personell 35 (engineer), colony 1, rank=1
 *
 * Uses tick numbers 11400–11449.
 */
class GameTickCreditsTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;   // Bart

    private const COLONY_ID = 1;   // Springfield

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Remove all seeded advisors to give each test full control over upkeep
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    /** Auto-increments personell_id to avoid UNIQUE(colony_id, personell_id) violations. */
    private int $nextPersonellId = 35;

    private function insertAdvisor(int $rank, int $colonyId = self::COLONY_ID): int
    {
        $personellId = $this->nextPersonellId++;

        return DB::table('advisors')->insertGetId([
            'user_id' => self::USER_ID,
            'colony_id' => $colonyId,
            'personell_id' => $personellId,
            'rank' => $rank,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);
    }

    // ── Step 8c: Passive Credits ───────────────────────────────────────────────

    /**
     * With CC > 0 and no advisor, user receives:
     *   nexus_subsidy (30) + housing_level (2) × tax_per_housing (20) = 70 Cr per tick.
     */
    public function test_passive_credits_added_when_cc_is_active(): void
    {
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11400]);

        $after = $this->getCredits();
        $nexus = (int) config('game.credits.nexus_subsidy', 30);
        $taxPerHousing = (int) config('game.credits.tax_per_housing', 20);
        $housingLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->value('level');

        $expected = $before + $nexus + ($housingLevel * $taxPerHousing);
        $this->assertEquals($expected, $after,
            'User must receive nexus_subsidy + (housing_level × tax_per_housing) per tick');
    }

    /**
     * Nexus subsidy alone (no housing) = 30 Cr.
     */
    public function test_nexus_subsidy_only_when_no_housing(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11401]);

        $after = $this->getCredits();
        $expected = $before + (int) config('game.credits.nexus_subsidy', 30);
        $this->assertEquals($expected, $after,
            'User must receive exactly nexus_subsidy (30 Cr) when there is no housing');
    }

    /**
     * No passive credits when CC level is 0 (colony not operational).
     */
    public function test_no_passive_credits_without_command_center(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 25)
            ->update(['level' => 0]);

        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11402]);

        $after = $this->getCredits();
        $this->assertEquals($before, $after,
            'User must receive no passive credits when CC level is 0');
    }

    /**
     * Higher housing level increases the housing tax contribution.
     * Housing level 5 → 5 × 20 = 100 Cr housing tax + 30 Cr nexus = 130 Cr total.
     */
    public function test_housing_tax_scales_with_housing_level(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 5]);

        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11403]);

        $after = $this->getCredits();
        $expected = $before + 30 + (5 * 20); // nexus + housing
        $this->assertEquals($expected, $after, 'Housing tax must scale with housing level');
    }

    // ── Step 8d: Advisor upkeep ────────────────────────────────────────────────

    /**
     * A rank-1 advisor costs 10 Cr/tick (deducted after passive income).
     *
     * Net: +30 (nexus, no housing) - 10 (upkeep) = +20 Cr.
     * Housing is zeroed for simplicity.
     */
    public function test_rank_1_advisor_upkeep_deducted(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(1);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11410]);

        $after = $this->getCredits();
        // nexus=30 income - rank1 upkeep=10 = net +20
        $expected = 1000 + 30 - 10;
        $this->assertEquals($expected, $after,
            'Rank-1 advisor upkeep (10 Cr) must be deducted after passive income');
    }

    /**
     * A rank-2 advisor costs 50 Cr/tick.
     * Net: +30 (nexus) - 50 (upkeep) = -20 → but credits started at 1000, so 980.
     */
    public function test_rank_2_advisor_upkeep_deducted(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(2);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11411]);

        $after = $this->getCredits();
        $expected = 1000 + 30 - 50; // 980
        $this->assertEquals($expected, $after,
            'Rank-2 advisor upkeep (50 Cr) must be deducted after passive income');
    }

    /**
     * A rank-3 advisor costs 160 Cr/tick.
     */
    public function test_rank_3_advisor_upkeep_deducted(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(3);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11412]);

        $after = $this->getCredits();
        $expected = 1000 + 30 - 160; // 870
        $this->assertEquals($expected, $after,
            'Rank-3 advisor upkeep (160 Cr) must be deducted after passive income');
    }

    /**
     * Advisor upkeep clamps credits to 0 — never creates debt.
     *
     * Start with 0 credits. Passive income = 30. Rank-3 upkeep = 160.
     * 0 + 30 - 160 = -130 → clamped to 0.
     *
     * Actually: income is added first, then upkeep is MAX(0, credits - upkeep).
     * After income: 0 + 30 = 30. After upkeep: MAX(0, 30 - 160) = 0.
     */
    public function test_advisor_upkeep_clamps_credits_to_zero(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(3);
        $this->setCredits(0);

        Artisan::call('game:tick', ['--tick' => 11413]);

        $after = $this->getCredits();
        $this->assertGreaterThanOrEqual(0, $after, 'Credits must never go below 0 from advisor upkeep');
    }

    /**
     * Multiple advisors each deduct upkeep independently.
     * Rank 1 (10 Cr) + Rank 2 (50 Cr) = 60 Cr total upkeep.
     * Net: 1000 + 30 (nexus) - 60 = 970.
     */
    public function test_multiple_advisors_upkeep_deducted(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(1);
        $this->insertAdvisor(2);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11414]);

        $after = $this->getCredits();
        $expected = 1000 + 30 - 10 - 50; // 970
        $this->assertEquals($expected, $after,
            'Multiple advisor upkeep costs must all be deducted independently');
    }

    /**
     * An advisor with no colony assignment (colony_id=null) has no upkeep.
     * The colony-assigned advisor costs 10 Cr; unassigned costs nothing.
     */
    public function test_unassigned_advisor_has_no_upkeep(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        // One assigned (rank 1, 10 Cr)
        $this->insertAdvisor(1, self::COLONY_ID);

        // One unassigned (colony_id=null)
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'colony_id' => null,
            'personell_id' => 35,
            'rank' => 3, // would cost 160 Cr if assigned
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);

        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11415]);

        $after = $this->getCredits();
        // Only assigned advisor (rank 1 = 10 Cr) deducted; unassigned (rank 3) = 0
        $expected = 1000 + 30 - 10; // 1020
        $this->assertEquals($expected, $after,
            'Unassigned advisor must not incur upkeep cost');
    }

    // ── Order of operations ────────────────────────────────────────────────────

    /**
     * Passive income is applied BEFORE advisor upkeep in the same tick.
     *
     * Start at 0 credits. Rank-1 advisor upkeep = 10 Cr.
     * Passive income = 30 Cr.
     * If income first → 0+30=30, then 30-10=20. Result: 20.
     * If upkeep first → MAX(0, 0-10)=0, then 0+30=30. Result: 30 (different!).
     *
     * The correct result per GDD §3 / GameTick code is 20 (income before upkeep).
     */
    public function test_passive_income_applied_before_advisor_upkeep(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 28)
            ->update(['level' => 0]);

        $this->insertAdvisor(1); // upkeep = 10 Cr
        $this->setCredits(0);

        Artisan::call('game:tick', ['--tick' => 11420]);

        $after = $this->getCredits();
        // Income first (30), then upkeep (10): 0+30-10 = 20
        $this->assertEquals(20, $after,
            'Passive income must be applied before advisor upkeep in the same tick');
    }
}
