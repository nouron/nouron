<?php

namespace Tests\Feature\Trade;

use App\Services\TradeGateway;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for TradeGateway::acceptResourceOffer().
 *
 * Fixture recap (TestSeeder):
 *   Colony 1 (Springfield) → user_id=3 (Bart),  race_id=2, faction_id=4
 *   Colony 2 (Shelbyville) → user_id=0 (Homer),  race_id=1, faction_id=7
 *
 *   user_resources: user_id=3 → credits=49615
 *   colony_resources (colony 1): res4=18598, res5=6335
 *
 *   trade_resources:
 *     (colony_id=2, dir=0, res_id=4,  amount=11,  price=11)  ← Homer's buy offer (Werkstoffe)
 *     (colony_id=2, dir=1, res_id=5,  amount=123, price=32)  ← Homer's sell offer (Organika)
 *     (colony_id=1, dir=0, res_id=4,  amount=100, price=50)  ← Bart's buy offer  (Werkstoffe)
 *
 * Test scenarios use:
 *   - Sell offer: Bart (buyer, col 1, user 3) buys Homer's sell offer (col 2, dir=1, res=5)
 *     cost = 123 × 32 = 3936 credits. Homer needs res5=123 on col 2 (seeded manually per test).
 *   - Buy offer: Bart accepts Homer's buy offer (col 2, dir=0, res=4, amount=11, price=11)
 *     Bart delivers 11 of res4 from col 1, Homer pays 11×11=121 credits.
 *     Homer's user_resources row is seeded manually per test.
 */
class TradeAcceptTest extends TestCase
{
    use RefreshDatabase;

    private TradeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Bypass AP checks — test colonies have no traders (no economy AP).
        config(['game.dev_mode' => true]);
        $this->gateway = $this->app->make(TradeGateway::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Ensure colony 2 has a specific resource amount (upsert).
     */
    private function setColony2Resource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')
            ->updateOrInsert(
                ['colony_id' => 2, 'resource_id' => $resourceId],
                ['amount' => $amount]
            );
    }

    /**
     * Ensure Homer (user_id=0) has a user_resources row with the given credits.
     */
    private function setHomerCredits(int $credits): void
    {
        DB::table('user_resources')
            ->updateOrInsert(
                ['user_id' => 0],
                ['credits' => $credits, 'supply' => 0]
            );
    }

    // ── Sell offer (direction=1) ───────────────────────────────────────────────

    /**
     * Bart accepts Homer's sell offer for resource 5.
     * Credits flow Bart → Homer; resource flows col 2 → col 1.
     */
    public function test_accept_sell_offer_transfers_resources_and_credits(): void
    {
        // Homer's colony needs to have resource 5 stocked
        $this->setColony2Resource(5, 200);

        $bartCreditsBefore  = DB::table('user_resources')->where('user_id', 3)->value('credits');
        $col1Res5Before     = DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 5)->value('amount');

        // Offer: col 2, dir=1, res=5, amount=123, price=32 → total = 3936
        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);

        // Bart's credits decreased
        $bartCreditsAfter = DB::table('user_resources')->where('user_id', 3)->value('credits');
        $this->assertSame((int) $bartCreditsBefore - 3936, (int) $bartCreditsAfter);

        // Homer received credits
        $homerCreditsAfter = DB::table('user_resources')->where('user_id', 0)->value('credits');
        $this->assertSame(3936, (int) $homerCreditsAfter);

        // Colony 2 lost resource 5
        $col2Res5After = DB::table('colony_resources')
            ->where('colony_id', 2)->where('resource_id', 5)->value('amount');
        $this->assertSame(200 - 123, (int) $col2Res5After);

        // Colony 1 gained resource 5
        $col1Res5After = DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 5)->value('amount');
        $this->assertSame((int) $col1Res5Before + 123, (int) $col1Res5After);
    }

    // ── Buy offer (direction=0) ────────────────────────────────────────────────

    /**
     * Bart accepts Homer's buy offer for resource 4 (Werkstoffe).
     * Bart delivers resource; Homer pays credits.
     */
    public function test_accept_buy_offer_transfers_resources_and_credits(): void
    {
        // Homer needs credits to pay: 11 × 11 = 121
        $this->setHomerCredits(500);

        $bartCreditsBefore = DB::table('user_resources')->where('user_id', 3)->value('credits');
        $col1Res4Before    = DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 4)->value('amount');

        // Offer: col 2, dir=0, res=4, amount=11, price=11 → total = 121
        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      0,
            resourceId:     4,
        );

        $this->assertTrue($result);

        // Bart (acceptor) received 121 credits
        $bartCreditsAfter = DB::table('user_resources')->where('user_id', 3)->value('credits');
        $this->assertSame((int) $bartCreditsBefore + 121, (int) $bartCreditsAfter);

        // Homer paid 121 credits
        $homerCreditsAfter = DB::table('user_resources')->where('user_id', 0)->value('credits');
        $this->assertSame(500 - 121, (int) $homerCreditsAfter);

        // Colony 1 lost resource 4
        $col1Res4After = DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 4)->value('amount');
        $this->assertSame((int) $col1Res4Before - 11, (int) $col1Res4After);

        // Colony 2 gained resource 4
        $col2Res4After = DB::table('colony_resources')
            ->where('colony_id', 2)->where('resource_id', 4)->value('amount');
        $this->assertSame(11, (int) $col2Res4After);
    }

    // ── Offer deleted after acceptance ────────────────────────────────────────

    public function test_accept_offer_deleted_after_acceptance(): void
    {
        $this->setColony2Resource(5, 200);

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $remaining = DB::table('trade_resources')
            ->where('colony_id', 2)
            ->where('direction', 1)
            ->where('resource_id', 5)
            ->count();

        $this->assertSame(0, $remaining);
    }

    // ── Self-trade prevention ─────────────────────────────────────────────────

    public function test_accept_own_offer_fails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('eigenes Angebot');

        // Bart tries to accept his own buy offer (col 1, dir=0, res=4)
        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 1,
            direction:      0,
            resourceId:     4,
        );
    }

    // ── Insufficient credits ──────────────────────────────────────────────────

    public function test_accept_offer_fails_when_insufficient_credits(): void
    {
        $this->setColony2Resource(5, 200);

        // Drain Bart's credits so he can't afford 3936
        DB::table('user_resources')->where('user_id', 3)->update(['credits' => 100]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Credits');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );
    }

    // ── Insufficient resources on seller's colony ─────────────────────────────

    public function test_accept_offer_fails_when_insufficient_resources(): void
    {
        // Homer's colony only has 10 of resource 5, but the offer is for 123
        $this->setColony2Resource(5, 10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ressourcen');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );
    }

    // ── Restriction: faction ──────────────────────────────────────────────────

    /**
     * Bart (faction_id=4) cannot accept a faction-restricted offer
     * from Homer (faction_id=7).
     */
    public function test_restriction_faction_blocks_different_faction(): void
    {
        $this->setColony2Resource(5, 200);

        // Set the sell offer to faction-restricted (restriction=2)
        DB::table('trade_resources')
            ->where('colony_id', 2)
            ->where('direction', 1)
            ->where('resource_id', 5)
            ->update(['restriction' => 2]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Fraktion');

        // Bart (faction=4) tries to buy from Homer (faction=7)
        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );
    }

    /**
     * Bart can accept a faction-restricted offer when both share the same faction.
     * We use a second offer that Bart created, accepted by Marge (faction_id=6)...
     * but it is simpler to just make Homer share Bart's faction for this test.
     */
    public function test_restriction_faction_allows_same_faction(): void
    {
        $this->setColony2Resource(5, 200);

        // Set the sell offer to faction-restricted (restriction=2)
        DB::table('trade_resources')
            ->where('colony_id', 2)
            ->where('direction', 1)
            ->where('resource_id', 5)
            ->update(['restriction' => 2]);

        // Put Homer in the same faction as Bart (faction_id=4)
        DB::table('user')->where('user_id', 0)->update(['faction_id' => 4]);

        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);
    }
}
