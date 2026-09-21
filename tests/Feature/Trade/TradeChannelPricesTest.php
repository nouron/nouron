<?php

namespace Tests\Feature\Trade;

/**
 * Handelsvorteil on the credit-priced channels (GDD §12, A13/P2a): Corvan's
 * special inventory ('merchant') and Orin ('nexus') take a price DISCOUNT of
 * exactly the advantage — and the shown price is the charged price. Neither
 * channel has a Konsul source.
 */

use App\Models\User;
use App\Services\CorporateContactService;
use App\Services\MerchantService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TradeChannelPricesTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADER_ADVISOR_ID = 92;

    private const TRUST_RESOURCE_ID = 12;

    private const OFFER_HIT_TICK = 71; // Orin offers the harvester at 495 Cr on this tick (see CorporateContactServiceTest)

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(self::OFFER_HIT_TICK));
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
    }

    private function assignConsul(int $rank): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => $rank, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );
    }

    private function setTradingPostLevel(int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 55)->delete();
        if ($level > 0) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID, 'building_id' => 55, 'instance_id' => 1,
                'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            ]);
        }
    }

    private function setTradeLevel(int $level): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => (int) config('knowledge.trade.id')],
            ['level' => $level, 'ap_spend' => 0, 'status_points' => 20]
        );
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    private function insertActiveMerchantItem(int $cost): array
    {
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => self::OFFER_HIT_TICK, 'tick_end' => self::OFFER_HIT_TICK + 1,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $itemId = DB::table('merchant_items')->insertGetId([
            'visit_id' => $visitId, 'item_type' => 'trust_boost', 'label' => 'Trust',
            'cost_credits' => $cost, 'payload' => json_encode(['trust_amount' => 15]), 'sold' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RESOURCE_ID],
            ['amount' => 50]
        );

        return [$visitId, $itemId];
    }

    // ── Corvan special inventory ('merchant') ─────────────────────────────────

    public function test_merchant_price_ignores_the_konsul_and_adds_trading_post_and_trade_knowledge(): void
    {
        $this->assignConsul(3); // no source on this channel
        $this->setTradingPostLevel(2); // 12
        $this->setTradeLevel(3); // 8  -> 20 %
        [, $itemId] = $this->insertActiveMerchantItem(200);
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 1000]);

        $service = $this->app->make(MerchantService::class);
        $shown = $service->itemPrice(self::COLONY_ID, 200);
        $result = $service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertTrue($result['ok']);
        $this->assertSame(160, $shown, '200 x (1 - 0.20); the Konsul must not add another 30 %');
        $this->assertSame(1000 - $shown, $this->credits(), 'shown price == charged price');
    }

    public function test_merchant_price_is_undiscounted_below_trading_post_tier_2_without_knowledge(): void
    {
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);

        $this->assertSame(200, $this->app->make(MerchantService::class)->itemPrice(self::COLONY_ID, 200));
    }

    public function test_bar_page_lists_corvans_special_inventory_at_the_discounted_price(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 52)->update(['level' => 1]);
        $this->setTradingPostLevel(2);
        $this->insertActiveMerchantItem(200);

        $html = $this->actingAs(User::find(self::USER_ID))->get(route('colony.bar'))->assertOk()->getContent();

        $this->assertStringContainsString('"price_credits":176', $html, 'the page data must carry the charged price (200 x 0.88)');
        $this->assertStringContainsString('item.price_credits', $html, 'the list must display the charged price, not the base cost');
        // The chip shows the charged price; the base cost only appears as the struck-through "was" price (P2b).
        $this->assertStringContainsString('<span class="res-amount" x-text="item.price_credits">', $html);
        $this->assertStringNotContainsString('<span class="res-amount" x-text="item.cost_credits">', $html);
    }

    // ── Orin ('nexus') ────────────────────────────────────────────────────────

    private function prepareOrin(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', 27)->where('instance_id', 2)->delete();
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 27, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0]
        );
    }

    public function test_orin_price_ignores_the_konsul_and_reports_base_price_and_advantage(): void
    {
        $this->prepareOrin();
        $this->assignConsul(3);
        $this->setTradingPostLevel(3); // 12
        $this->setTradeLevel(3); // 8 -> 20 %

        $offer = $this->app->make(CorporateContactService::class)->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertNotNull($offer);
        $this->assertSame(495, $offer['base_price']);
        $this->assertSame(396, $offer['price'], '495 x 0.80');
        $this->assertSame(20, $offer['advantage']['total_percent']);
        $this->assertNotContains('consul', array_column($offer['advantage']['sources'], 'key'));
    }

    public function test_orin_buy_charges_exactly_the_shown_price(): void
    {
        $this->prepareOrin();
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5); // 12 + 12 = 24 %
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 1000]);
        $service = $this->app->make(CorporateContactService::class);

        $shown = $service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK)['price'];
        $result = $service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertTrue($result['ok']);
        $this->assertSame(376, $shown, '495 x 0.76 = 376.2 -> 376');
        $this->assertSame($shown, $result['price']);
        $this->assertSame(1000 - $shown, $this->credits());
    }
}
