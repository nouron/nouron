<?php

namespace Tests\Feature\Trade;

use App\Models\Advisor;
use App\Services\Techtree\PersonellService;
use App\Services\TradeGateway;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Economy-AP integration tests for TradeGateway.
 *
 * These tests run with config('game.dev_mode') = false so that the AP checks
 * and locks are enforced.  Each test sets up its own trader advisors as needed.
 *
 * Fixture recap (TestSeeder, same as other Trade tests):
 *   Colony 1 (Springfield) → user_id=3 (Bart)
 *   Colony 2 (Shelbyville) → user_id=0 (Homer)
 *
 *   Sell offer: colony_id=2, direction=1, resource_id=5, amount=123, price=32
 *   Buy offer:  colony_id=2, direction=0, resource_id=3, amount=11,  price=11
 *
 * AP config (default): ap_cost_threshold=1000.
 *
 * Trader (Händler) personell_id = 92, rank 1 = 4 AP/tick.
 */
class TradeApTest extends TestCase
{
    use RefreshDatabase;

    private TradeGateway    $gateway;
    private PersonellService $personellService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Enforce AP rules — this is what distinguishes this suite from the others.
        config(['game.bypass.ap_checks' => false]);

        $this->gateway          = $this->app->make(TradeGateway::class);
        $this->personellService = $this->app->make(PersonellService::class);

        // Remove any advisors that may have been seeded so AP counts are predictable.
        Advisor::where('colony_id', 1)->delete();
        Advisor::where('colony_id', 2)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Insert a Händler (trader) advisor for the given colony at rank 1 (4 AP/tick).
     */
    private function hireTrader(int $colonyId, int $rank = 1): Advisor
    {
        return Advisor::create([
            'user_id'      => 3,   // Bart owns both test colonies for seeding purposes
            'personell_id' => PersonellService::PERSONELL_ID_TRADER,
            'colony_id'    => $colonyId,
            'rank'         => $rank,
            'active_ticks' => 0,
        ]);
    }

    private function setColony2Resource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => 2, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function setHomerCredits(int $credits): void
    {
        DB::table('user_resources')->updateOrInsert(
            ['user_id' => 0],
            ['credits' => $credits, 'supply' => 0]
        );
    }

    // ── Creating an offer locks economy AP ────────────────────────────────────

    /**
     * After addResourceOffer() succeeds, the economy AP used must be recorded
     * in locked_actionpoints for the posting colony (colony 1).
     *
     * amount=100, price=5 → 100×5/1000 = 0.5 → floor = 0 → max(1, 0) = 1 AP.
     */
    public function test_add_offer_locks_economy_ap(): void
    {
        $this->hireTrader(1); // 4 AP available on colony 1

        $apBefore = $this->personellService->getEconomyPoints(1);
        $this->assertSame(4, $apBefore);

        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 5,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $apAfter = $this->personellService->getEconomyPoints(1);
        // min cost is 1 AP: max(1, floor(100×5/1000)) = max(1, 0) = 1
        $this->assertSame(3, $apAfter);
    }

    /**
     * addResourceOffer() throws when the colony has no economy AP.
     */
    public function test_add_offer_fails_when_insufficient_economy_ap(): void
    {
        // No trader hired → 0 economy AP
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wirtschafts-AP');

        $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 10,
            'price'       => 10,
            'user_id'     => 3,
        ]);
    }

    // ── Accepting an offer locks 1 economy AP on the buyer ────────────────────

    /**
     * After acceptResourceOffer() succeeds, 1 economy AP must be locked on the
     * buyer's colony.  Bart (colony 1) accepts Homer's sell offer (colony 2).
     */
    public function test_accept_offer_locks_economy_ap_on_buyer(): void
    {
        $this->hireTrader(1); // Bart's colony gets 4 economy AP
        $this->setColony2Resource(5, 200); // Homer stocks resource 5

        $apBefore = $this->personellService->getEconomyPoints(1);
        $this->assertSame(4, $apBefore);

        // Offer: col 2, dir=1, res=5, amount=123, price=32 → total = 3936 credits
        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);

        $apAfter = $this->personellService->getEconomyPoints(1);
        $this->assertSame(3, $apAfter); // 1 AP locked
    }

    /**
     * acceptResourceOffer() throws when the buyer's colony has no economy AP.
     */
    public function test_accept_offer_fails_when_buyer_has_insufficient_economy_ap(): void
    {
        // No trader on colony 1 → 0 economy AP
        $this->setColony2Resource(5, 200);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Wirtschafts-AP');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );
    }

    // ── AP cost scales with offer value ───────────────────────────────────────

    /**
     * amount=100, price=50 → 100×50 / 1000 = 5.0 → floor = 5 → max(1,5) = 5 AP.
     */
    public function test_ap_cost_scales_with_offer_value(): void
    {
        // We need at least 5 AP — hire one rank-2 trader (7 AP total).
        $this->hireTrader(1, rank: 2);

        $apBefore = $this->personellService->getEconomyPoints(1);
        $this->assertSame(7, $apBefore);

        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 50,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $apAfter = $this->personellService->getEconomyPoints(1);
        $this->assertSame(2, $apAfter); // 7 - 5 = 2
    }

    // ── dev_mode bypasses AP checks ───────────────────────────────────────────

    /**
     * With game.bypass.ap_checks=true, addResourceOffer() must succeed
     * even when the colony has zero economy AP.
     */
    public function test_dev_mode_bypasses_ap_check(): void
    {
        config(['game.bypass.ap_checks' => true]);

        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 50,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        // Also verify no AP were locked (bypass = no side effects)
        $apAfter = $this->personellService->getEconomyPoints(1);
        $this->assertSame(0, $apAfter);
    }
}
