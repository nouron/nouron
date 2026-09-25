<?php

namespace Tests\Feature\Bar;

/**
 * Cantina + Corvan integration of the Handelsvorteil (GDD §12, A13/P2a):
 *  - offers store BASE terms only (no Konsul rate baked in),
 *  - accept applies advantage x Get side (price stays),
 *  - what BarService::effectiveTerms() (display) reports is exactly what
 *    acceptOffer() executes,
 *  - Corvan's sell lots are fixed-price: no advantage, not negotiable,
 *  - migration removes legacy open offers that still carry a baked-in rate.
 */

use App\Models\User;
use App\Services\BarService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarTradeAdvantageTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const BAR_BUILDING_ID = 52;

    private const TRADING_POST_ID = 55;

    private const TRADER_ADVISOR_ID = 92;

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->mockTick(10);
        $this->barService = $this->app->make(BarService::class);
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
        config(['game.bypass.ap_checks' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
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
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::TRADING_POST_ID)->delete();
        if ($level > 0) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID, 'building_id' => self::TRADING_POST_ID, 'instance_id' => 1,
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

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function colonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)->where('resource_id', $resourceId)->value('amount');
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $amount]);
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    private function insertVisit(): int
    {
        return DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => 10, 'tick_end' => 11,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertOffer(array $overrides = []): int
    {
        return DB::table('bar_offers')->insertGetId(array_merge([
            'colony_id' => self::COLONY_ID,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 10,
            'expires_tick' => 20,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    // ── Offers store base terms only ──────────────────────────────────────────

    public function test_generated_guest_offers_store_base_terms_without_konsul_rate(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_BUILDING_ID)->update(['level' => 1]);
        $this->assignConsul(3); // would have baked +30 % into the Get amount before A13
        $basePrices = config('game.bar.base_prices');

        $checked = 0;
        for ($tick = 100; $tick < 140; $tick++) {
            DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
            $this->barService->generateOffersForColony(self::COLONY_ID, $tick);

            foreach (DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->get() as $offer) {
                $fairAmount = (int) max(1, round($offer->give_amount * $basePrices[$offer->give_resource_id] / $basePrices[$offer->get_resource_id]));
                $this->assertSame($fairAmount, (int) $offer->get_amount, 'stored Get amount must be the fair-market base amount, no Konsul rate baked in');
                $checked++;
            }
        }

        $this->assertGreaterThan(10, $checked, 'fixture: enough generated offers must have been inspected');
    }

    public function test_corvan_buy_offer_does_not_bake_in_the_konsul_discount(): void
    {
        // Ranks 0..2 share the seed path (the compounds bias only kicks in at rank 3),
        // so an identical result proves the rate is not baked in.
        $seed = 12345;
        $atRank0 = $this->barService->buildCorvanBuyOffer($seed, 0, 5000);
        $atRank1 = $this->barService->buildCorvanBuyOffer($seed, 1, 5000);
        $atRank2 = $this->barService->buildCorvanBuyOffer($seed, 2, 5000);

        $this->assertNotNull($atRank0);
        $this->assertSame($atRank0, $atRank1);
        $this->assertSame($atRank0, $atRank2);
    }

    // ── Accept: advantage x Get side ──────────────────────────────────────────

    public function test_accept_multiplies_get_side_by_konsul_rate_and_keeps_the_price(): void
    {
        $this->assignConsul(2); // +20 %
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 10]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(20, $result['give_amount'], 'the price (Give side) stays');
        $this->assertSame(12, $result['get_amount'], '10 x 1.20');
        $this->assertSame(80, $this->colonyResource(self::RES_REGOLITH));
        $this->assertSame(12, $this->colonyResource(self::RES_COMPOUNDS));
        $this->assertSame(10, (int) DB::table('bar_offers')->where('id', $offerId)->value('get_amount'), 'the stored offer keeps its base terms');
    }

    public function test_accept_on_a_corvan_buy_offer_raises_goods_not_lowers_price(): void
    {
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);
        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);
        $offerId = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_CREDITS, 'give_amount' => 500,
            'get_resource_id' => self::RES_REGOLITH, 'get_amount' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(500, $result['give_amount'], 'Credits price is unchanged');
        $this->assertSame(28, $result['get_amount'], '20 x (1 + 0.30 + 0.12) = 28.4 -> 28');
        $this->assertSame(500, 1000 - $this->credits());
        $this->assertSame(28, $this->colonyResource(self::RES_REGOLITH));
    }

    public function test_accept_adds_konsul_trading_post_and_trade_knowledge_additively(): void
    {
        $this->assignConsul(3); // 30
        $this->setTradingPostLevel(1); // 12
        $this->setTradeLevel(3); // 8  -> 50 %
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 20]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame(30, $result['get_amount'], '20 x 1.50 additive; a multiplicative stack would give 31');
    }

    public function test_accept_rounds_half_up_and_small_amounts_may_show_no_gain(): void
    {
        $this->assignConsul(1); // +10 %
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $five = $this->insertOffer(['give_amount' => 10, 'get_amount' => 5]);
        $three = $this->insertOffer(['give_amount' => 10, 'get_amount' => 3]);

        $this->assertSame(6, $this->barService->acceptOffer(self::COLONY_ID, $five, self::USER_ID, 10)['get_amount'], '5.5 rounds half up');
        $this->assertSame(3, $this->barService->acceptOffer(self::COLONY_ID, $three, self::USER_ID, 10)['get_amount'], '3.3 rounds down: the source line stays visible, the amount just does not move');
    }

    public function test_accept_without_any_source_executes_the_base_terms(): void
    {
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $offerId = $this->insertOffer(['give_amount' => 20, 'get_amount' => 10]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame(20, $result['give_amount']);
        $this->assertSame(10, $result['get_amount']);
    }

    // ── Display value == executed value ───────────────────────────────────────

    public function test_effective_terms_shown_equal_what_accept_executes(): void
    {
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(5);
        $this->setColonyResource(self::RES_REGOLITH, 200);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer(['give_amount' => 25, 'get_amount' => 17]);

        $shown = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);
        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame($shown['give_amount'], $result['give_amount']);
        $this->assertSame($shown['get_amount'], $result['get_amount']);
        $this->assertSame(200 - $shown['give_amount'], $this->colonyResource(self::RES_REGOLITH));
        $this->assertSame($shown['get_amount'], $this->colonyResource(self::RES_COMPOUNDS));
        $this->assertSame(54, $shown['advantage']['total_percent']);
        $this->assertSame(17, $shown['base_get_amount']);
        $this->assertFalse($shown['fixed_price']);
    }

    public function test_effective_terms_shown_equal_execution_for_a_corvan_buy_offer(): void
    {
        $this->assignConsul(1);
        $this->setTradeLevel(2);
        $this->setCredits(1000);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $offerId = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_CREDITS, 'give_amount' => 330,
            'get_resource_id' => self::RES_COMPOUNDS, 'get_amount' => 3,
        ]);

        $shown = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);
        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame($shown['give_amount'], $result['give_amount']);
        $this->assertSame($shown['get_amount'], $result['get_amount']);
        $this->assertSame(1000 - $shown['give_amount'], $this->credits());
        $this->assertSame($shown['get_amount'], $this->colonyResource(self::RES_COMPOUNDS));
    }

    // ── Verkaufslose: Festpreis ───────────────────────────────────────────────

    private function insertSellLot(): int
    {
        return $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
        ]);
    }

    public function test_sell_lot_pays_the_fixed_price_regardless_of_advantage(): void
    {
        $this->assignConsul(3);
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);
        $this->setColonyResource(self::RES_ORGANICS, 1000);
        $this->setCredits(0);
        $offerId = $this->insertSellLot();

        $shown = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);
        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($shown['fixed_price']);
        $this->assertSame(0, $shown['advantage']['total_percent']);
        $this->assertTrue($result['ok']);
        $this->assertSame(700, $result['get_amount']);
        $this->assertSame(20, $result['give_amount']);
        $this->assertSame(700, $this->credits());
    }

    public function test_sell_lot_is_not_negotiable(): void
    {
        $this->assignConsul(3);
        $this->setColonyResource(self::RES_ORGANICS, 1000);
        $offerId = $this->insertSellLot();

        $before = (int) DB::table('locked_actionpoints')->sum('spend_ap');
        $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
        $this->assertSame('bar_offer_not_negotiable', $result['error']);
        $this->assertTrue(DB::table('bar_offers')->where('id', $offerId)->exists(), 'the lot must survive the rejected attempt');
        $this->assertSame($before, (int) DB::table('locked_actionpoints')->sum('spend_ap'), 'no AP is spent on a rejected negotiation');
        $this->assertSame(700, (int) DB::table('bar_offers')->where('id', $offerId)->value('get_amount'));
    }

    public function test_fixed_price_rule_can_be_switched_off_by_config(): void
    {
        config(['game.bar.trade_terms.fixed_price_offers' => false]);
        $this->assignConsul(3); // +30 %
        $this->setColonyResource(self::RES_ORGANICS, 1000);
        $offerId = $this->insertSellLot();

        $shown = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $this->assertFalse($shown['fixed_price']);
        $this->assertSame(910, $shown['get_amount'], '700 x 1.30');
    }

    public function test_guest_offer_with_credits_on_get_side_but_no_visit_is_not_fixed_price(): void
    {
        $this->assignConsul(1);
        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_REGOLITH, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 100,
        ]);

        $shown = $this->barService->effectiveTerms(DB::table('bar_offers')->where('id', $offerId)->first(), self::COLONY_ID);

        $this->assertFalse($shown['fixed_price'], 'fixed price applies to Corvan lots (visit_id set) only');
        $this->assertSame(110, $shown['get_amount']);
    }

    // ── Verhandeln (additive, A13/P3) ─────────────────────────────────────────

    public function test_negotiate_success_stores_nothing_and_reports_the_additive_effective_terms(): void
    {
        $this->assignConsul(3);
        $this->setTradingPostLevel(1);
        $this->setColonyResource(self::RES_REGOLITH, 1000);

        $found = false;
        for ($tick = 1; $tick <= 100 && ! $found; $tick++) {
            $this->mockTick($tick);
            $this->barService = $this->app->make(BarService::class);
            DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
            $offerId = $this->insertOffer(['give_amount' => 30, 'get_amount' => 10, 'expires_tick' => $tick + 10]);

            $result = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);
            if (! ($result['ok'] && $result['success'])) {
                continue;
            }
            $found = true;

            // Nothing is baked into the row any more; the Handelsposten counts on negotiated offers.
            $this->assertSame(10, (int) DB::table('bar_offers')->where('id', $offerId)->value('get_amount'));
            $this->assertSame(42, $result['terms']['advantage']['total_percent'], 'Konsul 30 % + Handelsposten 12 %');
            $this->assertSame(0.2, $result['terms']['negotiation_bonus']);
            $this->assertSame(16, $result['terms']['get_amount'], '10 x (1 + 0.42 + 0.20) = 16.2 -> 16');
            $this->assertSame(16, $result['get_amount'], 'the top-level fields carry the same effective Get amount');

            $accepted = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, $tick);
            $this->assertSame($result['terms']['get_amount'], $accepted['get_amount'], 'what negotiate reports is what accept executes');
            $this->assertSame($result['terms']['give_amount'], $accepted['give_amount']);
        }

        $this->assertTrue($found, 'expected at least one successful negotiation roll');
    }

    // ── Legacy open offers ────────────────────────────────────────────────────

    public function test_migration_removes_open_legacy_offers_that_carry_a_baked_in_rate_but_keeps_fixed_lots(): void
    {
        $visitId = $this->insertVisit();
        $guest = $this->insertOffer();
        $corvanBuy = $this->insertOffer([
            'visit_id' => $visitId, 'give_resource_id' => self::RES_CREDITS, 'give_amount' => 300,
            'get_resource_id' => self::RES_REGOLITH, 'get_amount' => 10,
        ]);
        $sellLot = $this->insertOffer([
            'visit_id' => $visitId, 'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
        ]);
        $acceptedGuest = $this->insertOffer(['is_accepted' => true]);

        $migration = require database_path('migrations/2026_09_20_000001_remove_open_bar_offers_with_baked_in_konsul_rate.php');
        $migration->up();

        $this->assertFalse(DB::table('bar_offers')->where('id', $guest)->exists(), 'open guest offer is regenerated by the tick');
        $this->assertFalse(DB::table('bar_offers')->where('id', $corvanBuy)->exists(), 'open Corvan buy offer carried the baked-in rate');
        $this->assertTrue(DB::table('bar_offers')->where('id', $sellLot)->exists(), 'fixed-price sell lots never had a rate baked in');
        $this->assertTrue(DB::table('bar_offers')->where('id', $acceptedGuest)->exists(), 'accepted offers are history and stay');
    }

    // ── Cantina page shows the executed values ────────────────────────────────

    public function test_bar_page_shows_the_effective_get_amount_not_the_stored_base(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_BUILDING_ID)->update(['level' => 1]);
        $this->assignConsul(3);
        $this->setTradingPostLevel(1); // 30 + 12 = 42 %
        app()->setLocale('de');
        $offerId = $this->insertOffer(['give_amount' => 25, 'get_amount' => 20]);

        $html = $this->actingAs(User::find(self::USER_ID))->get(route('colony.bar'))->assertOk()->getContent();

        $section = $this->offerDialogHtml($html, $offerId);
        $text = preg_replace('/\s+/', ' ', strip_tags($section));
        // P2b: the dialog now lists the base offer as text and the result as a chip.
        $this->assertStringContainsString('25 Regolith → 20 Werkstoffe', $text, 'base offer shown as such, price (Give) stays 25');
        $this->assertMatchesRegularExpression('/Du erhältst\s+Co\s*28\b/', $text, '20 x 1.42 = 28.4 -> 28 is what accept will book');
        $this->assertDoesNotMatchRegularExpression('/Du erhältst\s+Co\s*20\b/', $text, 'the stored base amount is never the shown result');
    }

    public function test_bar_page_hides_negotiate_button_for_fixed_price_lots(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_BUILDING_ID)->update(['level' => 1]);
        $this->assignConsul(3);
        $lotId = $this->insertSellLot();

        $html = $this->actingAs(User::find(self::USER_ID))->get(route('colony.bar'))->assertOk()->getContent();

        $section = $this->offerDialogHtml($html, $lotId);
        $this->assertNotSame('', $section);
        $this->assertStringNotContainsString('negotiate('.$lotId, $section);
    }

    public function test_bar_page_negotiate_button_shows_the_ap_cost_chip_and_the_failure_facts_are_wired(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_BUILDING_ID)->update(['level' => 1]);
        $this->assignConsul(2);
        $offerId = $this->insertOffer(['give_amount' => 25, 'get_amount' => 20]);

        $html = $this->actingAs(User::find(self::USER_ID))->get(route('colony.bar'))->assertOk()->getContent();

        $section = $this->offerDialogHtml($html, $offerId);
        $this->assertStringContainsString('negotiate('.$offerId, $section);
        $this->assertStringContainsString('negotiate('.$offerId.', $el, "', $section, 'the Character name is passed as a JSON string argument (a broken quote would kill the Alpine handler)');
        $this->assertStringContainsString('Eco 2 AP', $section, 'the Verhandeln chip shows the configured AP cost (= Annehmen)');
        $this->assertSame(2, (int) config('game.bar.ap_cost_negotiate'));
        // The failure message names the facts: who withdrew, what stays with the player, how many AP are gone.
        $template = trans('colony.bar_offer_negotiate_failed', [], 'de'); // player-facing texts live in lang/de
        foreach ([':name', ':resource', ':ap'] as $placeholder) {
            $this->assertStringContainsString($placeholder, $template);
        }
        $this->assertStringContainsString('negotiateFailedText[', $section, 'the dialog renders the failure facts inline');
        $this->assertStringContainsString('resourceLabels', $html, 'resource names are handed to the page script');
    }

    /** The markup of one offer's dialog block (from its x-show marker to the next offer/encounter block). */
    private function offerDialogHtml(string $html, int $offerId): string
    {
        $start = strpos($html, "activeModal === 'offer_{$offerId}'");
        if ($start === false) {
            return '';
        }
        $end = strpos($html, "activeModal === 'offer_", $start + 10);
        $end = $end === false ? min(strlen($html), $start + 20000) : $end; // the dialog grew with the source breakdown (P2b)

        return substr($html, $start, $end - $start);
    }
}
