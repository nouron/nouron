<?php

namespace Tests\Feature\Colony;

/**
 * CorporateContactService feature tests.
 *
 * Orin (corporate_rep) — Weg A for the Harvester second instance (GDD §4c
 * "Harvester-Zweitinstanz: Bezugsquelle", freigegeben 2026-08-05). Stateless by
 * design: no visits table, getActiveOffer() is a pure function of (colony, tick),
 * re-derived on both the display path and the buy path so they can't drift.
 *
 * Deterministic seeds for colony_id=1: tick=1/5 no appearance at all; tick=23
 * appearance but no harvester deal; tick=71 appearance WITH the harvester deal
 * (price rolls to 495, inside the 400-800 Cr range).
 *
 * Covered scenarios:
 *   - test_get_active_offer_returns_null_before_cc_gate
 *   - test_get_active_offer_returns_null_when_both_instances_already_placed
 *   - test_get_active_offer_returns_null_on_a_tick_without_appearance
 *   - test_get_active_offer_returns_null_on_appearance_without_offer_roll
 *   - test_get_active_offer_returns_offer_within_price_range_on_a_hit_tick
 *   - test_buy_harvester_offer_fails_when_no_offer_active
 *   - test_buy_harvester_offer_fails_with_insufficient_credits
 *   - test_buy_harvester_offer_succeeds_deducts_credits_and_grants_purchase_entitlement
 */
use App\Services\CorporateContactService;
use App\Services\HarvesterEntitlementService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorporateContactServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;   // Springfield

    private const USER_ID = 3;     // Bart

    private const CC_ID = 25;

    private const HARVESTER_ID = 27;

    private const OFFER_HIT_TICK = 71;

    private const APPEAR_ONLY_TICK = 23;

    private const NO_APPEAR_TICK = 1;

    private CorporateContactService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(CorporateContactService::class);

        $this->setCcLevel(3);
        // Keep exactly one Harvester instance placed (below max_instances=2).
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)->where('instance_id', 2)
            ->delete();
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0]
        );
    }

    private function setCcLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::CC_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $amount]);
    }

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    public function test_get_active_offer_returns_null_before_cc_gate(): void
    {
        $this->setCcLevel(2);

        $this->assertNull($this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK));
    }

    public function test_get_active_offer_returns_null_when_both_instances_already_placed(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 2],
            ['level' => 0, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]
        );

        $this->assertNull($this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK));
    }

    public function test_get_active_offer_returns_null_on_a_tick_without_appearance(): void
    {
        $this->assertNull($this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::NO_APPEAR_TICK));
    }

    public function test_get_active_offer_returns_null_on_appearance_without_offer_roll(): void
    {
        $this->assertNull($this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::APPEAR_ONLY_TICK));
    }

    public function test_get_active_offer_returns_offer_within_price_range_on_a_hit_tick(): void
    {
        $offer = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertNotNull($offer);
        $this->assertGreaterThanOrEqual(400, $offer['price']);
        $this->assertLessThanOrEqual(800, $offer['price']);
    }

    public function test_get_active_offer_returns_null_when_user_already_has_entitlement(): void
    {
        // Regression guard: an already-earned entitlement (e.g. Weg B salvage, still
        // unplaced) must not stack with a second, independently earned Weg A offer —
        // instance_count alone doesn't catch this because the earned instance hasn't
        // been placed yet.
        $this->app->make(HarvesterEntitlementService::class)->grantSalvage(self::USER_ID);

        $this->assertNull($this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK));
    }

    public function test_buy_harvester_offer_fails_when_no_offer_active(): void
    {
        $this->setCredits(1000);

        $result = $this->service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, self::NO_APPEAR_TICK);

        $this->assertFalse($result['ok']);
        $this->assertSame(1000, $this->getCredits(), 'no credits must be charged when no offer is active');
    }

    public function test_buy_harvester_offer_fails_with_insufficient_credits(): void
    {
        $this->setCredits(100); // below any price in the 400-800 range

        $result = $this->service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertFalse($result['ok']);
        $this->assertSame(100, $this->getCredits());
    }

    public function test_buy_harvester_offer_succeeds_deducts_credits_and_grants_purchase_entitlement(): void
    {
        $this->setCredits(1000);

        $result = $this->service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertTrue($result['ok']);
        $this->assertSame(1000 - $result['price'], $this->getCredits());

        $entitlementService = $this->app->make(HarvesterEntitlementService::class);
        $this->assertTrue($entitlementService->hasEntitlement(self::USER_ID));
        $this->assertFalse($entitlementService->isSalvageSourced(self::USER_ID), 'purchase path must not be marked as salvage-sourced');
    }

    // ── Handelsposten-Rabatt (Design-Spec 2026-08-23) ──────────────────────────

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 55)->delete();

        if ($level !== null) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => 55,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }
    }

    public function test_active_offer_price_is_discounted_with_trading_post_level_3(): void
    {
        $this->setTradingPostLevel(null);
        $offerWithoutDiscount = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);
        $this->assertNotNull($offerWithoutDiscount);
        $fullPrice = $offerWithoutDiscount['price'];

        $this->setTradingPostLevel(3); // Stufe 3 schaltet den Nexus/Corporate-Contact-Kanal frei
        $offerWithDiscount = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);
        $this->assertNotNull($offerWithDiscount);

        $discount = (float) config('buildings.tradingPost.merchant_price_bonus');
        $expectedPrice = (int) max(1, round($fullPrice * (1 - $discount)));
        $this->assertSame($expectedPrice, $offerWithDiscount['price'], 'active offer price must reflect the trading post discount at level 3');
        $this->assertLessThan($fullPrice, $offerWithDiscount['price'], 'discounted price must be strictly lower than the undiscounted price');
    }

    public function test_active_offer_price_has_no_discount_below_level_3(): void
    {
        $this->setTradingPostLevel(2); // Schwelle für diesen Kanal ist 3, Level 2 unlockt ihn noch nicht

        $offer = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertNotNull($offer);
        $this->assertSame(495, $offer['price'], 'below the level-3 threshold, price must equal the documented undiscounted roll (495 Cr at OFFER_HIT_TICK)');
    }

    public function test_active_offer_price_applies_trade_price_bonus_even_below_trading_post_threshold(): void
    {
        // GDD/Owner-Entscheidung 2026-08-27: trade-Kenntnis-Preisbonus ist additiv
        // zum Handelsposten-Kanal-Rabatt, nicht davon abhängig.
        $this->setTradingPostLevel(2); // unter dem 'corporate_contact'-Schwellenwert (3) → 0% Handelsposten-Rabatt

        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => (int) config('knowledge.trade.id')],
            ['level' => 3, 'ap_spend' => 0, 'status_points' => 20]
        );

        $offer = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertNotNull($offer);
        $expectedPrice = (int) max(1, round(495 * (1 - 0.08))); // trade Lv3 cumulative curve = 8%
        $this->assertSame($expectedPrice, $offer['price'], 'trade knowledge price bonus must apply even without a trading post channel discount');
    }
}
