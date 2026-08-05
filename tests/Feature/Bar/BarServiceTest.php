<?php

namespace Tests\Feature\Bar;

/**
 * BarService feature tests.
 *
 * Covered scenarios:
 *  GENERATE OFFERS
 *    - test_generate_does_nothing_when_bar_not_built
 *    - test_generate_creates_offers_when_bar_built
 *    - test_generate_expires_old_offers
 *
 *  GET ACTIVE OFFERS
 *    - test_get_active_offers_filters_expired
 *    - test_get_active_offers_filters_accepted
 *
 *  ACCEPT OFFER
 *    - test_accept_offer_deducts_give_and_adds_get
 *    - test_accept_returns_error_for_expired_offer
 *    - test_accept_returns_error_when_insufficient_resources
 *    - test_accept_returns_error_for_foreign_offer
 */

use App\Services\BarService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarServiceTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const COLONY_ID = 1;   // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3;   // Bart

    private const BAR_BUILDING_ID = 52;

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private const TRADER_ADVISOR_ID = 92;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Override the bar level for Springfield. Uses the row that the TestSeeder
     * already inserts (colony_id=1, building_id=52).
     */
    private function setBarLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => $level]);
    }

    /**
     * Remove all bar_offers for Springfield so each test starts clean.
     * The TestSeeder inserts two seeded offers (expires_tick=99) that would
     * otherwise interfere with generate/accept tests.
     */
    private function clearBarOffers(): void
    {
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
    }

    /**
     * Insert a bar_offer directly and return its id.
     */
    private function insertOffer(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 10,
            'expires_tick' => 50,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_offers')->insertGetId(array_merge($defaults, $overrides));
    }

    /** Insert a merchant_visits row (bar_offers.visit_id has a FK to this table) and return its id. */
    private function insertMerchantVisit(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'tick_start' => 10,
            'tick_end' => 11,
            'was_visited' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('merchant_visits')->insertGetId(array_merge($defaults, $overrides));
    }

    /** Set the colony-level resource amount for Springfield. */
    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    /** Read the colony-level resource amount for Springfield. */
    private function getColonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    /** Set credits for Bart. */
    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    /** Read credits for Bart. */
    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    /**
     * Override the TickService singleton so that acceptOffer() sees a specific
     * current tick rather than the wall-clock-derived one.
     */
    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    // ── generateOffersForColony ───────────────────────────────────────────────

    public function test_generate_does_nothing_when_bar_not_built(): void
    {
        $this->clearBarOffers();
        $this->setBarLevel(0);

        $this->barService->generateOffersForColony(self::COLONY_ID, 100);

        $count = DB::table('bar_offers')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        $this->assertEquals(0, $count, 'No bar_offers should be created when bar level is 0');
    }

    public function test_generate_creates_offers_when_bar_built(): void
    {
        $this->clearBarOffers();
        $this->setBarLevel(1);

        // Use a seed+tick combination known to yield at least 1 guest at rank 0
        // guest_count for rank 0 = [0, 1]; try multiple ticks to find one that hits 1.
        // pseudoRand(colonyId*997 + tick*31, 0, 1) must return 1.
        // We brute-force a tick value: check small range until one works.
        $generated = false;
        for ($tick = 100; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->barService->generateOffersForColony(self::COLONY_ID, $tick);

            $count = DB::table('bar_offers')
                ->where('colony_id', self::COLONY_ID)
                ->count();

            if ($count > 0) {
                $generated = true;
                break;
            }
        }

        $this->assertTrue($generated, 'generateOffersForColony should create at least one bar_offer for bar level 1');
    }

    public function test_generate_expires_old_offers(): void
    {
        $this->clearBarOffers();
        $this->setBarLevel(1);

        // Insert a stale offer that should be deleted (expires_tick=1 < tick=5)
        $staleId = $this->insertOffer([
            'expires_tick' => 1,
            'is_accepted' => false,
        ]);

        $this->barService->generateOffersForColony(self::COLONY_ID, 5);

        $exists = DB::table('bar_offers')
            ->where('id', $staleId)
            ->exists();

        $this->assertFalse($exists, 'Expired unaccepted offers must be deleted on generate');
    }

    // ── buildCorvanBuyOffer ───────────────────────────────────────────────────
    //
    // The generic-guest credits offer type was removed (GDD §12 Kanal 1, Freigegeben
    // 2026-08-05) — Credits-Handel now exists only through Corvan's Alltagsgeschäft.
    // buildCorvanBuyOffer() is what generateOffersForColony()'s old type<6 branch
    // became; the affordability-cap regression it used to cover now targets this
    // method directly instead of the (now barter-only) guest offer generator.

    public function test_corvan_buy_offer_caps_price_to_affordability(): void
    {
        // Losgröße muss an die Zahlungsfähigkeit gebunden sein (höchstens ~35 %
        // des Credits-Bestands) — sonst kostet ein Angebot ein Vielfaches des
        // Netto-Einkommens und ist faktisch nie annehmbar.
        $credits = 100;
        $cap = (int) floor($credits * 0.35); // 35

        $sawOffer = false;
        for ($seed = 1; $seed <= 500; $seed++) {
            $offer = $this->barService->buildCorvanBuyOffer($seed, 2, $credits);

            if ($offer !== null) {
                $sawOffer = true;
                [, $giveAmount] = $offer;
                $this->assertLessThanOrEqual($cap, $giveAmount, 'Corvan buy offer must cost at most ~35% of credits on hand');
            }
        }

        $this->assertTrue($sawOffer, 'Test setup must produce at least one non-null Corvan buy offer to be meaningful');
    }

    public function test_corvan_buy_offer_returns_null_when_even_one_unit_is_unaffordable(): void
    {
        // No barter fallback for Corvan (unlike the old generic-guest type):
        // an unaffordable offer is simply omitted, never downgraded to a trade.
        $offer = $this->barService->buildCorvanBuyOffer(7, 0, 0);

        $this->assertNull($offer, 'buildCorvanBuyOffer must return null, not a barter offer, when unaffordable');
    }

    public function test_generate_never_creates_credits_offers_for_generic_guest_rotation(): void
    {
        // GDD §12 Kanal 1 "Corvan wird die zentrale Handelsfigur der Cantina"
        // (Freigegeben 2026-08-05): the anonymous guest rotation (Dax, Voss, ...)
        // loses Credits-Handel entirely — barter (resource↔resource) only. No row
        // may ever carry give/get resource_id = 1 (credits), regardless of rank.
        $this->setBarLevel(5); // widest concurrent slot budget, most guests/sol
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => 3, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );

        for ($tick = 100; $tick <= 500; $tick++) {
            $this->clearBarOffers();
            $this->barService->generateOffersForColony(self::COLONY_ID, $tick);

            $creditOffers = DB::table('bar_offers')
                ->where('colony_id', self::COLONY_ID)
                ->where(function ($q) {
                    $q->where('give_resource_id', self::RES_CREDITS)
                        ->orWhere('get_resource_id', self::RES_CREDITS);
                })
                ->count();

            $this->assertEquals(0, $creditOffers, "No credits-involving bar_offer may be generated for generic guests at tick {$tick}");
        }
    }

    public function test_generate_does_not_delete_accepted_expired_offers(): void
    {
        // Accepted offers are historical records — they should NOT be purged even if expired.
        $this->clearBarOffers();
        $this->setBarLevel(1);

        $acceptedId = $this->insertOffer([
            'expires_tick' => 1,
            'is_accepted' => true,
        ]);

        $this->barService->generateOffersForColony(self::COLONY_ID, 5);

        $exists = DB::table('bar_offers')
            ->where('id', $acceptedId)
            ->exists();

        $this->assertTrue($exists, 'Accepted offers must not be deleted even when expired');
    }

    // ── getActiveOffers ───────────────────────────────────────────────────────

    public function test_get_active_offers_filters_expired(): void
    {
        $this->clearBarOffers();

        // expires_tick = 5 means the offer expires AT tick 5 (exclusive: expires_tick > tick)
        $this->insertOffer(['expires_tick' => 5]);

        // At tick 5 the offer is expired (expires_tick > tick is false for tick=5)
        $atExpiry = $this->barService->getActiveOffers(self::COLONY_ID, 5);
        $this->assertCount(0, $atExpiry, 'Offer with expires_tick=5 must not appear at tick=5');

        // At tick 4 the offer is still active (5 > 4)
        $beforeExpiry = $this->barService->getActiveOffers(self::COLONY_ID, 4);
        $this->assertCount(1, $beforeExpiry, 'Offer with expires_tick=5 must appear at tick=4');
    }

    public function test_get_active_offers_filters_accepted(): void
    {
        $this->clearBarOffers();

        $this->insertOffer([
            'expires_tick' => 99,
            'is_accepted' => true,
        ]);

        $offers = $this->barService->getActiveOffers(self::COLONY_ID, 1);
        $this->assertCount(0, $offers, 'Accepted offers must not be returned as active');
    }

    public function test_get_active_offers_returns_non_expired_non_accepted_offers(): void
    {
        $this->clearBarOffers();

        $this->insertOffer(['expires_tick' => 99, 'is_accepted' => false]);
        $this->insertOffer(['expires_tick' => 99, 'is_accepted' => false]);

        $offers = $this->barService->getActiveOffers(self::COLONY_ID, 1);
        $this->assertCount(2, $offers);
    }

    // ── acceptOffer ───────────────────────────────────────────────────────────

    public function test_accept_offer_deducts_give_and_adds_get(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $giveAmount = 20;
        $getAmount = 30;

        // Give = regolith (colony resource), get = compounds (colony resource)
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => $giveAmount,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => $getAmount,
            'expires_tick' => 20, // valid at tick 10
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok'], 'acceptOffer should return ok=true on success');

        $regolithAfter = $this->getColonyResource(self::RES_REGOLITH);
        $compoundsAfter = $this->getColonyResource(self::RES_COMPOUNDS);

        $this->assertEquals(100 - $giveAmount, $regolithAfter, 'give_amount of regolith must be deducted');
        $this->assertEquals($getAmount, $compoundsAfter, 'get_amount of compounds must be added');
    }

    public function test_accept_offer_with_credits_as_give(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $giveAmount = 500;  // player pays credits
        $getAmount = 20;   // player receives regolith

        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_CREDITS,
            'give_amount' => $giveAmount,
            'get_resource_id' => self::RES_REGOLITH,
            'get_amount' => $getAmount,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        // Credits are handled by ResourcesService::decreaseAmount → increaseAmount
        // which goes through user_resources for res_id=1.
        $creditsAfter = $this->getCredits();
        $regolithAfter = $this->getColonyResource(self::RES_REGOLITH);

        $this->assertEquals(1000 - $giveAmount, $creditsAfter, 'Credits must be deducted for the give side');
        $this->assertEquals($getAmount, $regolithAfter, 'Regolith must be added for the get side');
    }

    public function test_accept_marks_offer_as_accepted(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'expires_tick' => 20,
        ]);

        $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $isAccepted = (bool) DB::table('bar_offers')
            ->where('id', $offerId)
            ->value('is_accepted');

        $this->assertTrue($isAccepted, 'Offer must be marked is_accepted=1 after successful accept');
    }

    public function test_accept_returns_error_for_expired_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10); // current tick = 10

        $offerId = $this->insertOffer([
            'expires_tick' => 10, // expires AT tick 10 → expired (service checks <= tick)
            'is_accepted' => false,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must fail for an expired offer');
        $this->assertArrayHasKey('error', $result, 'Error key must be present in failure response');
    }

    public function test_accept_returns_error_when_insufficient_resources(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        // Player has less regolith than required
        $this->setColonyResource(self::RES_REGOLITH, 5);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20, // needs 20 but only has 5
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must fail when player cannot afford give_amount');
        $this->assertArrayHasKey('error', $result);
    }

    // ── reserve floor (Corvan Organika sell offers, GDD §4b) ────────────────────

    public function test_accept_rejects_corvan_sell_offer_that_breaches_reserve_floor(): void
    {
        // GDD §4b: a sell offer (Organika→Credits) must not be acceptable if it
        // would drop the colony's Organika stock below sell_reserve_multiplier ×
        // ResourcesService::foodNeed() — even if it was generated when the stock
        // was still comfortably above that floor (drained by an earlier accept in
        // the same visit, or by ongoing food consumption).
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);

        config([
            'game.merchant.commodity.sell_resource_id' => self::RES_ORGANICS,
            'game.merchant.commodity.sell_reserve_multiplier' => 2,
        ]);

        // Zero every seeded building so ResourcesService::foodNeed() = 0... then
        // set one to a known level to get a deterministic non-zero food_need.
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->update(['level' => 0]);
        DB::table('buildings')->where('id', 25)->update(['supply_cost' => 8]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        config(['game.food.supply_per_eater' => 4]);
        // food_need = floor(3*8/4) = 6 → reserve floor = 2 × 6 = 12

        // Stock is only just above the give_amount + reserve floor.
        $this->setColonyResource(self::RES_ORGANICS, 30);

        $offerId = $this->insertOffer([
            'visit_id' => $this->insertMerchantVisit(),
            'give_resource_id' => self::RES_ORGANICS,
            'give_amount' => 20, // 30 - 20 = 10 < reserve floor 12
            'get_resource_id' => self::RES_CREDITS,
            'get_amount' => 700,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must reject a Corvan sell offer that would breach the reserve floor');
        $this->assertEquals(30, $this->getColonyResource(self::RES_ORGANICS), 'Organika stock must be untouched when the reserve floor blocks the trade');
    }

    public function test_accept_allows_corvan_sell_offer_above_reserve_floor(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);

        config([
            'game.merchant.commodity.sell_resource_id' => self::RES_ORGANICS,
            'game.merchant.commodity.sell_reserve_multiplier' => 2,
        ]);

        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->update(['level' => 0]);
        DB::table('buildings')->where('id', 25)->update(['supply_cost' => 8]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        config(['game.food.supply_per_eater' => 4]);
        // food_need = 6 → reserve floor = 12

        $this->setColonyResource(self::RES_ORGANICS, 100);

        $offerId = $this->insertOffer([
            'visit_id' => $this->insertMerchantVisit(),
            'give_resource_id' => self::RES_ORGANICS,
            'give_amount' => 20, // 100 - 20 = 80 >= reserve floor 12
            'get_resource_id' => self::RES_CREDITS,
            'get_amount' => 700,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok'], 'acceptOffer must allow a Corvan sell offer that stays above the reserve floor');
    }

    public function test_accept_reserve_floor_does_not_apply_to_generic_guest_barter_offers(): void
    {
        // A generic guest offer (visit_id=null) trading Organika away must not be
        // gated by the reserve floor — that check only applies to Corvan's sell
        // offers (visit_id set), matching the GDD's Bar-gated-not-run-wide scope.
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);

        config([
            'game.merchant.commodity.sell_resource_id' => self::RES_ORGANICS,
            'game.merchant.commodity.sell_reserve_multiplier' => 2,
        ]);

        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->update(['level' => 0]);
        DB::table('buildings')->where('id', 25)->update(['supply_cost' => 8]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        config(['game.food.supply_per_eater' => 4]);
        // food_need = 6 → reserve floor = 12 (would block if applied)

        $this->setColonyResource(self::RES_ORGANICS, 30);

        $offerId = $this->insertOffer([
            // no visit_id — generic guest offer
            'give_resource_id' => self::RES_ORGANICS,
            'give_amount' => 20, // 30 - 20 = 10 < reserve floor 12, but not a Corvan sell offer
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 5,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok'], 'Reserve floor must not apply to non-Corvan (visit_id=null) barter offers');
    }

    public function test_accept_returns_error_when_zero_credits(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $this->setCredits(0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_CREDITS,
            'give_amount' => 100,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must fail when player has 0 credits and offer costs credits');
    }

    public function test_accept_returns_error_for_foreign_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        // Insert offer for colony_id=2 (Shelbyville), not Springfield (1)
        $foreignOfferId = DB::table('bar_offers')->insertGetId([
            'colony_id' => 2,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 10,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 5,
            'expires_tick' => 20,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bart tries to accept an offer that belongs to colony 2
        $result = $this->barService->acceptOffer(self::COLONY_ID, $foreignOfferId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'Player must not be able to accept offers from a foreign colony');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_accept_returns_error_for_nonexistent_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $result = $this->barService->acceptOffer(self::COLONY_ID, 99999, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must fail for a non-existent offer id');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_accept_returns_error_for_already_accepted_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);

        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'expires_tick' => 20,
            'is_accepted' => true, // pre-accepted
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'acceptOffer must fail when offer is already accepted');
    }

    public function test_accept_returns_error_when_insufficient_economy_ap(): void
    {
        config(['game.bypass.ap_checks' => false]);

        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class); // refresh after mockTick
        $this->setColonyResource(self::RES_REGOLITH, 100);

        // Lock all economy AP so none is available
        $apCost = (int) config('game.bar.ap_cost_accept', 1);
        $totalAp = (int) config('game.ap.base', 6);
        DB::table('locked_actionpoints')->insert([
            'tick' => 10,
            'scope_type' => 'colony',
            'scope_id' => self::COLONY_ID,
            'personell_id' => PersonellService::idFor('trader'),
            'spend_ap' => $totalAp + $apCost, // exhaust entire pool
        ]);

        $offerId = $this->insertOffer(['expires_tick' => 20]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsStringIgnoringCase('ap', $result['error']);
    }

    public function test_accept_locks_economy_ap(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class); // refresh after mockTick
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer(['expires_tick' => 20]);

        $personellId = PersonellService::idFor('trader');
        $lockedBefore = (int) DB::table('locked_actionpoints')
            ->where(['tick' => 10, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
            ->value('spend_ap');

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);

        $lockedAfter = (int) DB::table('locked_actionpoints')
            ->where(['tick' => 10, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
            ->value('spend_ap');

        $this->assertEquals($lockedBefore + (int) config('game.bar.ap_cost_accept', 1), $lockedAfter);
    }

    public function test_accept_waives_ap_cost_for_an_already_negotiated_offer(): void
    {
        // The negotiate step already paid ap_cost_negotiate — accepting the improved
        // terms afterwards is a free confirmation, not a second priced action.
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer([
            'expires_tick' => 20,
            'is_negotiated' => true,
        ]);

        $personellId = PersonellService::idFor('trader');
        $lockedBefore = (int) DB::table('locked_actionpoints')
            ->where(['tick' => 10, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
            ->value('spend_ap');

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);

        $lockedAfter = (int) DB::table('locked_actionpoints')
            ->where(['tick' => 10, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
            ->value('spend_ap');

        $this->assertEquals($lockedBefore, $lockedAfter, 'Accepting an already-negotiated offer must not lock any additional AP');
    }

    // ── hasAvailableConsul ───────────────────────────────────────────────────────

    public function test_has_available_consul_false_without_trader(): void
    {
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();

        $this->assertFalse($this->barService->hasAvailableConsul(self::COLONY_ID));
    }

    public function test_has_available_consul_true_when_trader_assigned_and_available(): void
    {
        $this->assignTrader(1);

        $this->assertTrue($this->barService->hasAvailableConsul(self::COLONY_ID));
    }

    public function test_has_available_consul_false_when_trader_unavailable(): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => 1, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => 99]
        );

        $this->assertFalse($this->barService->hasAvailableConsul(self::COLONY_ID));
    }

    // ── negotiateOffer ────────────────────────────────────────────────────────

    /** Assign a trader advisor at the given rank, available (not on a mission). */
    private function assignTrader(int $rank): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => $rank, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );
    }

    public function test_negotiate_returns_error_when_no_consul_assigned(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer(['expires_tick' => 20]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'negotiateOffer must fail without an assigned Konsul');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_negotiate_returns_error_when_consul_unavailable(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => 2, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => 99]
        );
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer(['expires_tick' => 20]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'negotiateOffer must fail when the Konsul is on a mission (unavailable_until_tick set)');
    }

    public function test_negotiate_returns_error_for_expired_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->assignTrader(2);

        $offerId = $this->insertOffer(['expires_tick' => 10]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_negotiate_returns_error_when_insufficient_resources(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->assignTrader(2);
        $this->setColonyResource(self::RES_REGOLITH, 5);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_negotiate_returns_error_when_insufficient_economy_ap(): void
    {
        config(['game.bypass.ap_checks' => false]);

        $this->clearBarOffers();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);
        $this->assignTrader(2);
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $totalAp = $this->app->make(PersonellService::class)->getTotalActionPoints('economy', self::COLONY_ID);
        DB::table('locked_actionpoints')->insert([
            'tick' => 10,
            'scope_type' => 'colony',
            'scope_id' => self::COLONY_ID,
            'personell_id' => PersonellService::idFor('trader'),
            'spend_ap' => $totalAp + (int) config('game.bar.ap_cost_negotiate', 3),
        ]);

        $offerId = $this->insertOffer(['expires_tick' => 20]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsStringIgnoringCase('ap', $result['error']);
    }

    public function test_negotiate_costs_more_ap_than_accept(): void
    {
        $apAccept = (int) config('game.bar.ap_cost_accept', 1);
        $apNegotiate = (int) config('game.bar.ap_cost_negotiate', 3);

        $this->assertGreaterThan($apAccept, $apNegotiate, 'Negotiating must cost more AP than a plain accept');
    }

    public function test_negotiate_success_locks_negotiate_ap_but_does_not_execute_the_trade(): void
    {
        // Two-step flow (owner feedback 2026-07-31): a successful negotiation only
        // improves the offer's terms — the player still confirms with "Annehmen".
        // No resources move and the offer is NOT marked accepted at this point.
        $this->clearBarOffers();
        $this->assignTrader(3); // 85% success chance — find a winning tick quickly

        $found = false;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            $this->setColonyResource(self::RES_REGOLITH, 1000);

            $offerId = $this->insertOffer([
                'give_resource_id' => self::RES_REGOLITH,
                'give_amount' => 20,
                'expires_tick' => $tick + 10,
            ]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);

            if ($result['ok'] && $result['success']) {
                $found = true;

                $row = DB::table('bar_offers')->where('id', $offerId)->first();
                $this->assertFalse((bool) $row->is_accepted, 'A negotiated offer must not be auto-accepted');
                $this->assertTrue((bool) $row->is_negotiated, 'Offer must be flagged is_negotiated on a successful roll');
                $this->assertEquals(1000, $this->getColonyResource(self::RES_REGOLITH), 'No resources may move until the player clicks Annehmen');

                $personellId = PersonellService::idFor('trader');
                $locked = (int) DB::table('locked_actionpoints')
                    ->where(['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
                    ->value('spend_ap');
                $this->assertEquals((int) config('game.bar.ap_cost_negotiate', 3), $locked);
                break;
            }
        }

        $this->assertTrue($found, 'Expected at least one successful negotiation roll within 200 ticks at 85% chance');
    }

    public function test_negotiate_success_reduces_give_amount_for_credits_offer(): void
    {
        $this->clearBarOffers();
        $this->assignTrader(3);

        $found = false;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            $this->setCredits(10000);

            $offerId = $this->insertOffer([
                'give_resource_id' => self::RES_CREDITS,
                'give_amount' => 1000,
                'get_resource_id' => self::RES_REGOLITH,
                'get_amount' => 20,
                'expires_tick' => $tick + 10,
            ]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);

            if ($result['ok'] && $result['success']) {
                $found = true;
                $bonus = (float) config('game.bar.negotiate_bonus.3', 0.2);
                $expectedGive = (int) max(1, round(1000 * (1 - $bonus)));
                $this->assertEquals($expectedGive, $result['give_amount'], 'Negotiated price must apply the rank-3 bonus discount');
                // Credits are untouched — the trade only executes once acceptOffer() runs.
                $this->assertEquals(10000, $this->getCredits());

                $row = DB::table('bar_offers')->where('id', $offerId)->first();
                $this->assertEquals($expectedGive, $row->give_amount, 'The offer row itself must be updated to the negotiated give_amount');
                $this->assertEquals(20, $row->get_amount, 'Barter get_amount is unaffected for a credits-give offer');
                break;
            }
        }

        $this->assertTrue($found);
    }

    public function test_negotiate_success_increases_get_amount_for_barter_offer(): void
    {
        $this->clearBarOffers();
        $this->assignTrader(3);

        $found = false;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            $this->setColonyResource(self::RES_REGOLITH, 1000);

            $offerId = $this->insertOffer([
                'give_resource_id' => self::RES_REGOLITH,
                'give_amount' => 30,
                'get_resource_id' => self::RES_COMPOUNDS,
                'get_amount' => 10,
                'expires_tick' => $tick + 10,
            ]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);

            if ($result['ok'] && $result['success']) {
                $found = true;
                $bonus = (float) config('game.bar.negotiate_bonus.3', 0.2);
                $expectedGet = (int) max(1, round(10 * (1 + $bonus)));
                $this->assertEquals($expectedGet, $result['get_amount'], 'Barter offers get a get_amount bonus instead of a discount');
                $this->assertEquals(30, $result['give_amount'], 'Barter give_amount is unaffected');
                break;
            }
        }

        $this->assertTrue($found);
    }

    public function test_negotiate_success_increases_get_amount_for_corvan_sell_offer(): void
    {
        // A Corvan sell offer (visit_id set, give=Organika/get=Credits) takes the
        // barter branch in negotiateOffer() — $isCreditsOffer checks the GIVE side,
        // and Organika isn't Credits — so a successful negotiation scales get_amount
        // (more Credits), not give_amount. That's correct for this shape, but is an
        // artifact of which side the check reads; pin it explicitly so a future
        // "fix" that switches the check to get_resource_id can't silently turn
        // Corvan's sell channel into an Organika discount instead.
        $this->clearBarOffers();
        $this->assignTrader(3);
        $visitId = $this->insertMerchantVisit();

        $found = false;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            $this->setColonyResource(self::RES_ORGANICS, 1000);

            $offerId = $this->insertOffer([
                'visit_id' => $visitId,
                'give_resource_id' => self::RES_ORGANICS,
                'give_amount' => 20,
                'get_resource_id' => self::RES_CREDITS,
                'get_amount' => 700,
                'expires_tick' => $tick + 10,
            ]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);

            if ($result['ok'] && $result['success']) {
                $found = true;
                $bonus = (float) config('game.bar.negotiate_bonus.3', 0.2);
                $expectedGet = (int) max(1, round(700 * (1 + $bonus)));
                $this->assertEquals($expectedGet, $result['get_amount'], 'A negotiated Corvan sell offer must pay out more Credits, not fewer Organika');
                $this->assertEquals(20, $result['give_amount'], 'give_amount (Organika owed) is unaffected by a successful negotiation on a sell offer');

                // Reserve floor still governs at accept — negotiation only moved
                // get_amount for this shape, give_amount (and thus the stock check
                // in acceptOffer()) is unchanged.
                $accepted = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);
                $this->assertTrue($accepted['ok'], 'A negotiated sell offer must still be acceptable when comfortably above the reserve floor');
                break;
            }
        }

        $this->assertTrue($found);
    }

    public function test_negotiate_rejects_an_already_negotiated_offer(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->assignTrader(2);
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'expires_tick' => 20,
            'is_negotiated' => true,
        ]);

        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok'], 'Re-negotiating an already-negotiated offer must be rejected');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_negotiate_failure_deletes_offer_but_still_costs_ap(): void
    {
        $this->clearBarOffers();
        $this->assignTrader(1); // 55% success — 45% fail chance, findable quickly

        $found = false;
        for ($tick = 1; $tick <= 200; $tick++) {
            $this->clearBarOffers();
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            $this->setColonyResource(self::RES_REGOLITH, 1000);

            $offerId = $this->insertOffer([
                'give_resource_id' => self::RES_REGOLITH,
                'give_amount' => 20,
                'expires_tick' => $tick + 10,
            ]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);

            if ($result['ok'] && ! $result['success']) {
                $found = true;

                $exists = DB::table('bar_offers')->where('id', $offerId)->exists();
                $this->assertFalse($exists, 'Offer must be deleted entirely on negotiation failure — no fallback to plain accept');

                $personellId = PersonellService::idFor('trader');
                $locked = (int) DB::table('locked_actionpoints')
                    ->where(['tick' => $tick, 'scope_type' => 'colony', 'scope_id' => self::COLONY_ID, 'personell_id' => $personellId])
                    ->value('spend_ap');
                $this->assertEquals((int) config('game.bar.ap_cost_negotiate', 3), $locked, 'AP must still be spent even when the negotiation fails');
                break;
            }
        }

        $this->assertTrue($found, 'Expected at least one failing negotiation roll within 200 ticks at 45% fail chance');
    }

    public function test_generate_respects_max_concurrent(): void
    {
        $this->clearBarOffers();
        $this->setBarLevel(1); // max_concurrent = 2

        // Pre-fill with 2 active offers (at max_concurrent for Lv1)
        $this->insertOffer(['expires_tick' => 20]);
        $this->insertOffer(['expires_tick' => 20]);

        // Set trader rank 3 (1–2 guests guaranteed)
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => 3, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );

        $this->barService->generateOffersForColony(self::COLONY_ID, 5);

        $count = DB::table('bar_offers')
            ->where('colony_id', self::COLONY_ID)
            ->where('expires_tick', '>', 5)
            ->where('is_accepted', false)
            ->count();

        $this->assertLessThanOrEqual(2, $count, 'max_concurrent=2 at bar Lv1 must not be exceeded');
    }
}
