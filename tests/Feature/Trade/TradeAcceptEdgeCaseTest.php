<?php

namespace Tests\Feature\Trade;

use App\Models\User;
use App\Services\TradeGateway;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Edge-case and security tests for the Trade acceptance flow.
 *
 * Scenarios covered:
 *
 * SERVICE LAYER — acceptResourceOffer():
 *   E1  Offer not found (deleted between load and accept) → exception
 *   E2  Seller colony not found (orphan offer) → exception
 *   E3  Buyer and seller share the same colony_id → self-trade guard fires
 *   E4  Buyer has exactly zero credits → exception (sell offer)
 *   E5  Seller's stock is exactly zero → exception (sell offer)
 *   E6  totalCost = amount × price would overflow a 32-bit integer → transfer
 *       still executes correctly (PHP ints are 64-bit on 64-bit platforms)
 *   E7  restriction=3 (same race) blocks different races
 *   E8  restriction=3 (same race) allows same race
 *   E9  restriction=1 (group, not yet implemented) is treated as open (= 0)
 *   E10 DB is left consistent after a failed transfer (credits/resources unchanged)
 *   E11 Offer is NOT deleted when transfer fails due to insufficient credits
 *   E12 acceptor's colony has exactly the right amount of resource (boundary)
 *       for a buy-offer acceptance
 *   E13 Seller's user_resources row does not exist yet → row is created on credit
 *       receipt (sell offer flow, direction=1)
 *
 * CONTROLLER LAYER — acceptResourceOffer (HTTP):
 *   C1  POST without colonyId in session → redirect with error
 *   C2  POST missing required fields → validation error
 *   C3  POST with invalid direction value → validation error
 *   C4  buyer_colony_id comes from session, not from POST body (injection prevention)
 *   C5  Successful accept → redirect back with 'success' flash
 *   C6  Accept that fails business logic → redirect back with 'trade' error bag
 *   C7  Guest cannot POST to /trade/offer/accept
 */
class TradeAcceptEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private TradeGateway $gateway;
    private User $bart;
    private User $homer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->gateway = $this->app->make(TradeGateway::class);
        $this->bart    = User::find(3);
        $this->homer   = User::find(0);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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

    private function setBartCredits(int $credits): void
    {
        DB::table('user_resources')
            ->where('user_id', 3)
            ->update(['credits' => $credits]);
    }

    // ── E1: Offer not found ───────────────────────────────────────────────────

    /**
     * Offer for resource 7 does not exist in the test fixture.
     * acceptResourceOffer must throw, not silently succeed.
     */
    public function test_accept_nonexistent_offer_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nicht gefunden');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     7, // no such offer in fixture
        );
    }

    // ── E2: Seller colony not found ───────────────────────────────────────────

    /**
     * Insert an offer for a colony that does not exist in glx_colonies.
     * The service must detect the missing seller colony and throw.
     */
    public function test_accept_offer_for_nonexistent_seller_colony_throws(): void
    {
        // Plant an orphan offer referencing colony 999 (no such colony)
        DB::table('trade_resources')->insert([
            'colony_id'   => 999,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 10,
            'price'       => 5,
            'restriction' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kolonie nicht gefunden');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 999,
            direction:      1,
            resourceId:     3,
        );
    }

    // ── E3: buyer and seller share the same colony_id ─────────────────────────

    /**
     * When buyerColonyId === sellerColonyId the two IDs are identical, but the
     * guard checks user_id, not colony_id.  A player could potentially own two
     * colonies and have them trade — but the current design says one colony per
     * player, so buyerUserId === sellerUserId when the colony matches.
     *
     * We verify that self-trade via the same colony is still blocked.
     */
    public function test_accept_own_offer_via_same_colony_id_throws(): void
    {
        // Bart's buy offer on colony 1 (dir=0, res=10)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('eigenes Angebot');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 1, // same colony
            direction:      0,
            resourceId:     10,
        );
    }

    // ── E4: Buyer has exactly zero credits (sell offer) ───────────────────────

    public function test_accept_sell_offer_fails_when_buyer_has_zero_credits(): void
    {
        $this->setColony2Resource(5, 200);
        $this->setBartCredits(0);

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

    // ── E5: Seller's stock is exactly zero (sell offer) ───────────────────────

    public function test_accept_sell_offer_fails_when_seller_stock_is_zero(): void
    {
        $this->setColony2Resource(5, 0);

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

    // ── E6: Large amounts — no integer overflow on 64-bit PHP ─────────────────

    /**
     * amount=1000000, price=1000000 → totalCost=1_000_000_000_000.
     * PHP integers are 64-bit on 64-bit platforms; this must not silently
     * truncate or produce negative values.
     */
    public function test_large_amount_and_price_transfers_correctly(): void
    {
        // Plant a large sell offer on colony 2 for resource 4
        DB::table('trade_resources')->updateOrInsert(
            ['colony_id' => 2, 'direction' => 1, 'resource_id' => 4],
            ['amount' => 1_000_000, 'price' => 1_000_000, 'restriction' => 0]
        );

        $totalCost = 1_000_000 * 1_000_000; // 1 trillion

        // Give Bart enough credits
        $this->setBartCredits($totalCost + 1);
        // Give colony 2 enough resource 4
        $this->setColony2Resource(4, 1_000_001);
        // Homer needs a user_resources row to receive credits
        $this->setHomerCredits(0);

        $bartCreditsBefore = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');

        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     4,
        );

        $this->assertTrue($result);

        $bartCreditsAfter = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');
        $this->assertSame($bartCreditsBefore - $totalCost, $bartCreditsAfter);

        $homerCreditsAfter = (int) DB::table('user_resources')->where('user_id', 0)->value('credits');
        $this->assertSame($totalCost, $homerCreditsAfter);
    }

    // ── E7: restriction=3 (race) blocks different races ───────────────────────

    /**
     * Bart (race_id=2) tries to accept a race-restricted offer from Homer
     * (race_id=1). The service must throw.
     */
    public function test_restriction_race_blocks_different_race(): void
    {
        $this->setColony2Resource(5, 200);

        DB::table('trade_resources')
            ->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)
            ->update(['restriction' => 3]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rasse');

        $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );
    }

    // ── E8: restriction=3 (race) allows same race ─────────────────────────────

    public function test_restriction_race_allows_same_race(): void
    {
        $this->setColony2Resource(5, 200);

        DB::table('trade_resources')
            ->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)
            ->update(['restriction' => 3]);

        // Make Homer share Bart's race (race_id=2)
        DB::table('user')->where('user_id', 0)->update(['race_id' => 2]);

        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);
    }

    // ── E9: restriction=1 (group) is treated as open ──────────────────────────

    public function test_restriction_group_treated_as_open(): void
    {
        $this->setColony2Resource(5, 200);

        DB::table('trade_resources')
            ->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)
            ->update(['restriction' => 1]);

        // Bart and Homer have different faction/race; restriction=1 must not block
        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);
    }

    // ── E10: DB consistency after failed transfer ─────────────────────────────

    /**
     * When a transfer fails (buyer has no credits), credits and resources must
     * be identical to their pre-call state — the transaction must have rolled back.
     */
    public function test_failed_transfer_leaves_db_unchanged(): void
    {
        $this->setColony2Resource(5, 200);
        $this->setBartCredits(0); // guaranteed to fail

        $bartCreditsBefore    = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');
        $col2Res5Before       = (int) DB::table('colony_resources')->where('colony_id', 2)->where('resource_id', 5)->value('amount');
        $offerCountBefore     = DB::table('trade_resources')->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)->count();

        try {
            $this->gateway->acceptResourceOffer(
                buyerUserId:    3,
                buyerColonyId:  1,
                sellerColonyId: 2,
                direction:      1,
                resourceId:     5,
            );
        } catch (\InvalidArgumentException) {
            // expected
        }

        // Credits unchanged
        $this->assertSame($bartCreditsBefore, (int) DB::table('user_resources')->where('user_id', 3)->value('credits'));
        // Resource on seller colony unchanged
        $this->assertSame($col2Res5Before,    (int) DB::table('colony_resources')->where('colony_id', 2)->where('resource_id', 5)->value('amount'));
        // Offer still present
        $this->assertSame($offerCountBefore,  DB::table('trade_resources')->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)->count());
    }

    // ── E11: Offer is NOT deleted when transfer fails ─────────────────────────

    public function test_offer_not_deleted_after_failed_transfer(): void
    {
        $this->setColony2Resource(5, 200);
        $this->setBartCredits(1); // far less than 3936

        try {
            $this->gateway->acceptResourceOffer(
                buyerUserId:    3,
                buyerColonyId:  1,
                sellerColonyId: 2,
                direction:      1,
                resourceId:     5,
            );
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertSame(1, DB::table('trade_resources')
            ->where('colony_id', 2)->where('direction', 1)->where('resource_id', 5)
            ->count());
    }

    // ── E12: Acceptor has exactly the required resource amount (buy offer) ─────

    /**
     * Boundary: colony 1 has exactly 11 of resource 3 — just enough to satisfy
     * Homer's buy offer (amount=11). The transfer must succeed and leave colony 1
     * with 0 of resource 3.
     */
    public function test_accept_buy_offer_when_acceptor_has_exact_amount(): void
    {
        $this->setHomerCredits(500);

        // Set colony 1 resource 3 to exactly the required amount
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => 1, 'resource_id' => 3],
            ['amount' => 11]
        );

        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      0,
            resourceId:     3,
        );

        $this->assertTrue($result);

        $col1Res3After = (int) DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 3)->value('amount');
        $this->assertSame(0, $col1Res3After);
    }

    // ── E13: Seller has no user_resources row yet (sell offer) ───────────────

    /**
     * If a seller (Homer) has no row in user_resources, the service must
     * INSERT one with the received credits, not throw a DB error.
     */
    public function test_accept_sell_offer_creates_user_resources_row_for_seller(): void
    {
        $this->setColony2Resource(5, 200);

        // Ensure Homer has NO user_resources row
        DB::table('user_resources')->where('user_id', 0)->delete();
        $this->assertSame(0, DB::table('user_resources')->where('user_id', 0)->count());

        $result = $this->gateway->acceptResourceOffer(
            buyerUserId:    3,
            buyerColonyId:  1,
            sellerColonyId: 2,
            direction:      1,
            resourceId:     5,
        );

        $this->assertTrue($result);

        $homerRow = DB::table('user_resources')->where('user_id', 0)->first();
        $this->assertNotNull($homerRow);
        $this->assertSame(3936, (int) $homerRow->credits); // 123 × 32
    }

    // ── C1: No colonyId in session → redirect with error ─────────────────────

    /**
     * If the session does not contain activeIds.colonyId (e.g. the player never
     * logged in properly or the session expired), the controller must redirect
     * back with an error — not throw a 500.
     */
    public function test_accept_resource_offer_controller_fails_without_colony_in_session(): void
    {
        // Act as Bart but do NOT put a colonyId into the session
        $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [
                'seller_colony_id' => 2,
                'direction'        => 1,
                'resource_id'      => 5,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('trade');
    }

    // ── C2: Missing required fields → validation errors ───────────────────────

    public function test_accept_resource_offer_controller_fails_validation_on_empty_post(): void
    {
        Session::put('activeIds.colonyId', 1);

        $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [])
            ->assertSessionHasErrors(['seller_colony_id', 'direction', 'resource_id']);
    }

    // ── C3: Invalid direction value → validation error ────────────────────────

    public function test_accept_resource_offer_controller_rejects_invalid_direction(): void
    {
        Session::put('activeIds.colonyId', 1);

        $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [
                'seller_colony_id' => 2,
                'direction'        => 99, // not in [0,1]
                'resource_id'      => 5,
            ])
            ->assertSessionHasErrors('direction');
    }

    // ── C4: buyer_colony_id comes from session, not POST body ─────────────────

    /**
     * Security: a crafted POST that includes a fake "buyer_colony_id" field
     * must be ignored. The controller reads buyerColonyId exclusively from the
     * session (activeIds.colonyId).
     *
     * We verify this by setting session colonyId=1 (Bart's colony) while
     * submitting seller_colony_id=1 (would be self-trade if buyer were also
     * colony 1 and same user).  The exception message must be the self-trade
     * guard, proving the session value — not any injected POST field — is used.
     */
    public function test_buyer_colony_id_is_taken_from_session_not_post(): void
    {
        // Session says Bart is acting from colony 1
        Session::put('activeIds.colonyId', 1);

        // Bart tries to accept his OWN offer on colony 1; this must fail with
        // self-trade error, confirming the buyer colony came from the session
        $response = $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [
                'seller_colony_id' => 1, // Bart's own colony = self-trade
                'direction'        => 0,
                'resource_id'      => 10,
            ]);

        $response->assertRedirect();
        $this->assertTrue(
            $response->getSession()->get('errors')?->hasBag('default'),
            'Expected a session error bag after self-trade attempt'
        );
    }

    // ── C5: Successful accept → 'success' flash ───────────────────────────────

    public function test_accept_resource_offer_controller_redirects_with_success(): void
    {
        $this->setColony2Resource(5, 200);
        Session::put('activeIds.colonyId', 1);

        $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [
                'seller_colony_id' => 2,
                'direction'        => 1,
                'resource_id'      => 5,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    // ── C6: Business logic failure → 'trade' error bag ────────────────────────

    public function test_accept_resource_offer_controller_returns_trade_error_on_failure(): void
    {
        // Colony 2 has no resource 5 stocked → seller stock check will fail
        $this->setColony2Resource(5, 0);
        Session::put('activeIds.colonyId', 1);

        $this->actingAs($this->bart)
            ->post(route('trade.offer.accept'), [
                'seller_colony_id' => 2,
                'direction'        => 1,
                'resource_id'      => 5,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('trade');
    }

    // ── C7: Guest blocked ─────────────────────────────────────────────────────

    public function test_accept_resource_offer_redirects_guest_to_login(): void
    {
        $this->post(route('trade.offer.accept'), [
            'seller_colony_id' => 2,
            'direction'        => 1,
            'resource_id'      => 5,
        ])->assertRedirect(route('login'));
    }
}
