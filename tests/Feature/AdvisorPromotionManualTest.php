<?php

namespace Tests\Feature;

/**
 * Manual advisor rank promotion (Owner-Entscheidung F3/A23, 2026-09-09).
 *
 * Rank promotion is player-triggered (AdvisorService::promote()), not automatic
 * via GameTick anymore — see GameTickAdvisorTest for the regression coverage
 * confirming GameTick no longer promotes advisors on its own.
 *
 * Config:
 *   game.advisor.rank_thresholds = [1 => 15, 2 => 45]  (active_ticks needed to promote)
 *   game.advisor.promotion_costs = [2 => 150, 3 => 250]  (Credits charged at target rank)
 *
 * Covered scenarios:
 *  Happy path:
 *  - Eligible rank-1 advisor promotes to rank 2 and charges the configured cost
 *  - Eligible rank-2 advisor promotes to rank 3
 *  - Player can defer promotion indefinitely (active_ticks keeps sitting at/above
 *    threshold without auto-promoting — covered by GameTickAdvisorTest)
 *
 *  Edge cases:
 *  - Not enough active_ticks yet → rejected, no charge
 *  - Insufficient credits → rejected, no charge, rank unchanged
 *  - Already at max rank (3) → rejected
 *  - Advisor belonging to a different user → rejected (ownership)
 *
 * Fixture: TestSeeder colony 1 (Springfield), user_id=3 (Bart). Advisors cleared
 * in setUp so each test controls its own fixture precisely.
 */
use App\Models\Advisor;
use App\Services\AdvisorService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvisorPromotionManualTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const OTHER_USER_ID = 4;

    private const COLONY_ID = 1;

    private const RANK1_THRESHOLD = 15;

    private const RANK2_THRESHOLD = 45;

    private const RANK2_COST = 150;

    private const RANK3_COST = 250;

    private AdvisorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(AdvisorService::class);

        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
    }

    private function setCredits(int $amount, int $userId = self::USER_ID): void
    {
        DB::table('user_resources')->updateOrInsert(
            ['user_id' => $userId],
            ['credits' => $amount, 'supply' => 0]
        );
    }

    private function getCredits(int $userId = self::USER_ID): int
    {
        return (int) DB::table('user_resources')->where('user_id', $userId)->value('credits');
    }

    private function insertAdvisor(int $rank, int $activeTicks, int $userId = self::USER_ID): int
    {
        return DB::table('advisors')->insertGetId([
            'user_id' => $userId,
            'colony_id' => self::COLONY_ID,
            'personell_id' => 35,
            'rank' => $rank,
            'active_ticks' => $activeTicks,
            'unavailable_until_tick' => null,
        ]);
    }

    public function test_eligible_advisor_promotes_and_charges_cost(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD);
        $this->setCredits(1000);

        $result = $this->service->promote($id, self::USER_ID);

        $this->assertTrue($result === true || $result instanceof Advisor, 'Promotion must succeed');
        $advisor = DB::table('advisors')->where('id', $id)->first();
        $this->assertEquals(2, (int) $advisor->rank, 'Advisor must be promoted to rank 2');
        $this->assertEquals(1000 - self::RANK2_COST, $this->getCredits(), 'Promotion cost must be charged exactly once');
    }

    public function test_rank2_to_rank3_promotion(): void
    {
        $id = $this->insertAdvisor(rank: 2, activeTicks: self::RANK2_THRESHOLD);
        $this->setCredits(1000);

        $result = $this->service->promote($id, self::USER_ID);

        $this->assertTrue($result === true || $result instanceof Advisor);
        $this->assertEquals(3, (int) DB::table('advisors')->where('id', $id)->value('rank'));
        $this->assertEquals(1000 - self::RANK3_COST, $this->getCredits());
    }

    public function test_promotion_rejected_when_active_ticks_below_threshold(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD - 1);
        $this->setCredits(1000);

        $result = $this->service->promote($id, self::USER_ID);

        $this->assertIsString($result, 'Promotion must be rejected as an error code');
        $this->assertEquals(1, (int) DB::table('advisors')->where('id', $id)->value('rank'), 'Rank must stay unchanged');
        $this->assertEquals(1000, $this->getCredits(), 'Credits must not be charged on a rejected promotion');
    }

    public function test_promotion_rejected_when_insufficient_credits(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD);
        $this->setCredits(self::RANK2_COST - 1);

        $result = $this->service->promote($id, self::USER_ID);

        $this->assertIsString($result);
        $this->assertEquals(1, (int) DB::table('advisors')->where('id', $id)->value('rank'));
        $this->assertEquals(self::RANK2_COST - 1, $this->getCredits(), 'Credits must not be touched on a rejected promotion');
    }

    public function test_promotion_rejected_at_max_rank(): void
    {
        $id = $this->insertAdvisor(rank: 3, activeTicks: 1000);
        $this->setCredits(10_000);

        $result = $this->service->promote($id, self::USER_ID);

        $this->assertIsString($result);
        $this->assertEquals(3, (int) DB::table('advisors')->where('id', $id)->value('rank'));
    }

    public function test_promotion_rejected_for_advisor_owned_by_another_user(): void
    {
        $id = $this->insertAdvisor(rank: 1, activeTicks: self::RANK1_THRESHOLD, userId: self::USER_ID);
        $this->setCredits(1000, self::OTHER_USER_ID);

        $result = $this->service->promote($id, self::OTHER_USER_ID);

        $this->assertIsString($result);
        $this->assertEquals(1, (int) DB::table('advisors')->where('id', $id)->value('rank'));
    }
}
