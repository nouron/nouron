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
}
