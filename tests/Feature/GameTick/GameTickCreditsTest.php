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
 *   Formula: nexus_subsidy (50) + uplinkStation.level × relay_bonus_per_uplink_level (35)
 *            + consul_contract_income_per_rank[konsulRank] (Konsul assigned + Cantina >= Lv1)
 *   "Relaisvergütung" is anchored on Uplink Station, not Housing — colonists' living
 *   quarters have no thematic connection to Nexus relay/sensor capacity.
 *   "Handelsvertrag" requires a Konsul (trader advisor, personell_id 92) assigned to
 *   the colony AND the Cantina (building_id 52) at level >= 1.
 *   Only colonies where CC level > 0 receive credits.
 *   NPC colonies (user_id = null) are skipped.
 *
 * Step 8d — Advisor upkeep (deductAdvisorUpkeep):
 *   Upkeep per rank: 1 → 10 Cr, 2 → 25 Cr, 3 → 50 Cr (flattened 2026-07-19, re-flattened 2026-08-14, GDD §18.4)
 *   Deducted AFTER passive credits (so income is applied before costs).
 *   Credits clamped to ≥ 0 — never goes negative from upkeep alone.
 *   Advisors without a colony assignment incur no upkeep.
 *
 * Covered scenarios:
 *  Happy path:
 *  - Nexus subsidy (50 Cr) added each tick when CC > 0
 *  - Relay bonus added per Uplink Station level
 *  - Handelsvertrag contract income added when Konsul + Cantina present
 *  - Advisor upkeep deducted (rank 1 = 10 Cr)
 *  - Net income = passive - upkeep for one rank-1 advisor
 *
 *  Edge cases:
 *  - No CC → no passive credits at all
 *  - No Uplink Station built → nexus subsidy only
 *  - Cantina built but no Konsul assigned → no contract income
 *  - Konsul assigned but no Cantina built → no contract income
 *  - Advisor upkeep clamped to 0 (credits cannot go negative)
 *  - Multiple advisors: each deducts independently
 *  - Advisor rank 2 (25 Cr) and rank 3 (50 Cr) upkeep correct
 *
 *  Adversarial:
 *  - Upkeep fires AFTER passive income (order of operations)
 *  - Unassigned advisor (colony_id=null) has no upkeep
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart)
 *     CC (building_id=25): level=3 → passive subsidy fires
 *     No Uplink Station (building_id=54) row seeded — tests that need one insert it.
 *     No Cantina (building_id=52) row seeded — tests that need one insert it.
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

    private const UPLINK_BUILDING_ID = 54;

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

    private function setUplinkLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::UPLINK_BUILDING_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'tile_x' => 0, 'tile_y' => 3]
        );
    }

    private const CANTINA_BUILDING_ID = 52;

    private const KONSUL_PERSONELL_ID = 92;

    private function setCantinaLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::CANTINA_BUILDING_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'tile_x' => 0, 'tile_y' => 4]
        );
    }

    private function insertKonsul(int $rank): void
    {
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'personell_id' => self::KONSUL_PERSONELL_ID,
            'rank' => $rank,
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);
    }

    private function upkeep(int $rank): int
    {
        return (int) config("game.advisor.upkeep.{$rank}");
    }

    private function nexusSubsidy(): int
    {
        return (int) config('game.credits.nexus_subsidy', 50);
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
     * With CC > 0 and an Uplink Station at level 2, user receives:
     *   nexus_subsidy (30) + uplink_level (2) × relay_bonus_per_uplink_level (20) = 70 Cr per tick.
     */
    public function test_passive_credits_added_when_cc_is_active(): void
    {
        $this->setUplinkLevel(2);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11400]);

        $after = $this->getCredits();
        $nexus = (int) config('game.credits.nexus_subsidy', 30);
        $relayBonusPerLevel = (int) config('game.credits.relay_bonus_per_uplink_level', 20);

        $expected = $before + $nexus + (2 * $relayBonusPerLevel);
        $this->assertEquals($expected, $after,
            'User must receive nexus_subsidy + (uplink_level × relay_bonus_per_uplink_level) per tick');
    }

    /**
     * Nexus subsidy alone (no Uplink Station built) = 30 Cr — matches the fixture default.
     */
    public function test_nexus_subsidy_only_when_no_uplink_station(): void
    {
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11401]);

        $after = $this->getCredits();
        $expected = $before + (int) config('game.credits.nexus_subsidy', 30);
        $this->assertEquals($expected, $after,
            'User must receive exactly nexus_subsidy (30 Cr) when there is no Uplink Station');
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
     * Higher Uplink Station level increases the relay bonus contribution.
     * Uplink level 3 (max_level) → 3 × 20 = 60 Cr relay bonus + 30 Cr nexus = 90 Cr total.
     */
    public function test_relay_bonus_scales_with_uplink_level(): void
    {
        $this->setUplinkLevel(3);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11403]);

        $after = $this->getCredits();
        $relayBonusPerLevel = (int) config('game.credits.relay_bonus_per_uplink_level', 35);
        $expected = $before + $this->nexusSubsidy() + (3 * $relayBonusPerLevel); // nexus + relay bonus
        $this->assertEquals($expected, $after, 'Relay bonus must scale with Uplink Station level');
    }

    // ── Step 8d: Advisor upkeep ────────────────────────────────────────────────

    /**
     * A rank-1 advisor costs 10 Cr/tick (deducted after passive income).
     *
     * Net: +30 (nexus, no Uplink Station) - 10 (upkeep) = +20 Cr.
     */
    public function test_rank_1_advisor_upkeep_deducted(): void
    {
        $this->insertAdvisor(1);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11410]);

        $after = $this->getCredits();
        $expected = 1000 + $this->nexusSubsidy() - $this->upkeep(1);
        $this->assertEquals($expected, $after,
            'Rank-1 advisor upkeep must be deducted after passive income');
    }

    /**
     * A rank-2 advisor costs 30 Cr/tick.
     * Net: +30 (nexus) - 30 (upkeep) = 0 → credits started at 1000, so 1000.
     */
    public function test_rank_2_advisor_upkeep_deducted(): void
    {
        $this->insertAdvisor(2);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11411]);

        $after = $this->getCredits();
        $expected = 1000 + $this->nexusSubsidy() - $this->upkeep(2);
        $this->assertEquals($expected, $after,
            'Rank-2 advisor upkeep must be deducted after passive income');
    }

    /**
     * A rank-3 advisor costs 80 Cr/tick.
     */
    public function test_rank_3_advisor_upkeep_deducted(): void
    {
        $this->insertAdvisor(3);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11412]);

        $after = $this->getCredits();
        $expected = 1000 + $this->nexusSubsidy() - $this->upkeep(3);
        $this->assertEquals($expected, $after,
            'Rank-3 advisor upkeep must be deducted after passive income');
    }

    /**
     * Advisor upkeep clamps credits to 0 — never creates debt.
     *
     * Start with 0 credits. Passive income = 30. Rank-3 upkeep = 80.
     * 0 + 30 - 80 = -50 → clamped to 0.
     *
     * Actually: income is added first, then upkeep is MAX(0, credits - upkeep).
     * After income: 0 + 30 = 30. After upkeep: MAX(0, 30 - 80) = 0.
     */
    public function test_advisor_upkeep_clamps_credits_to_zero(): void
    {
        $this->insertAdvisor(3);
        $this->setCredits(0);

        Artisan::call('game:tick', ['--tick' => 11413]);

        $after = $this->getCredits();
        $this->assertGreaterThanOrEqual(0, $after, 'Credits must never go below 0 from advisor upkeep');
    }

    /**
     * Multiple advisors each deduct upkeep independently.
     * Rank 1 (10 Cr) + Rank 2 (30 Cr) = 40 Cr total upkeep.
     * Net: 1000 + 30 (nexus) - 40 = 990.
     */
    public function test_multiple_advisors_upkeep_deducted(): void
    {
        $this->insertAdvisor(1);
        $this->insertAdvisor(2);
        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11414]);

        $after = $this->getCredits();
        $expected = 1000 + $this->nexusSubsidy() - $this->upkeep(1) - $this->upkeep(2);
        $this->assertEquals($expected, $after,
            'Multiple advisor upkeep costs must all be deducted independently');
    }

    // ── Handelsvertrag (Konsul + Cantina) ──────────────────────────────────────

    /**
     * With Cantina built and a rank-2 Konsul assigned, contract income (25 Cr)
     * is added on top of nexus subsidy, minus the Konsul's own rank-2 upkeep (30 Cr):
     * 30 (nexus) + 25 (contract) - 30 (Konsul upkeep) = +25.
     */
    public function test_contract_income_added_when_konsul_and_cantina_present(): void
    {
        $this->setCantinaLevel(1);
        $this->insertKonsul(2);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11416]);

        $after = $this->getCredits();
        $contractRank2 = (int) config('game.credits.consul_contract_income_per_rank.2', 25);
        $expected = $before + $this->nexusSubsidy() + $contractRank2 - $this->upkeep(2);
        $this->assertEquals($expected, $after,
            'Contract income must be added when a Konsul is assigned and the Cantina is built');
    }

    /**
     * Cantina built but no Konsul assigned → no contract income, nexus subsidy only.
     */
    public function test_no_contract_income_without_konsul(): void
    {
        $this->setCantinaLevel(1);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11417]);

        $after = $this->getCredits();
        $expected = $before + $this->nexusSubsidy();
        $this->assertEquals($expected, $after,
            'No contract income without a Konsul assigned, even with Cantina built');
    }

    /**
     * Konsul assigned but no Cantina built → no contract income; nexus subsidy (30)
     * minus the Konsul's own rank-2 upkeep (30) nets to 0.
     */
    public function test_no_contract_income_without_cantina(): void
    {
        $this->insertKonsul(2);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11418]);

        $after = $this->getCredits();
        $expected = $before + $this->nexusSubsidy() - $this->upkeep(2);
        $this->assertEquals($expected, $after,
            'No contract income without a Cantina built, even with a Konsul assigned');
    }

    /**
     * An advisor with no colony assignment (colony_id=null) has no upkeep.
     * The colony-assigned advisor costs 10 Cr; unassigned costs nothing.
     */
    public function test_unassigned_advisor_has_no_upkeep(): void
    {
        // One assigned (rank 1, 10 Cr)
        $this->insertAdvisor(1, self::COLONY_ID);

        // One unassigned (colony_id=null)
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID,
            'colony_id' => null,
            'personell_id' => 35,
            'rank' => 3, // would cost 80 Cr if assigned
            'active_ticks' => 0,
            'unavailable_until_tick' => null,
        ]);

        $this->setCredits(1000);

        Artisan::call('game:tick', ['--tick' => 11415]);

        $after = $this->getCredits();
        // Only assigned advisor (rank 1) deducted; unassigned (rank 3) = 0
        $expected = 1000 + $this->nexusSubsidy() - $this->upkeep(1);
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
        $this->insertAdvisor(1); // upkeep = 10 Cr
        $this->setCredits(0);

        Artisan::call('game:tick', ['--tick' => 11420]);

        $after = $this->getCredits();
        // Income first, then upkeep: if upkeep applied first, credits would clamp to
        // 0 before income lands, giving a different (wrong) result than income-first.
        $expected = $this->nexusSubsidy() - $this->upkeep(1);
        $this->assertEquals($expected, $after,
            'Passive income must be applied before advisor upkeep in the same tick');
    }
}
