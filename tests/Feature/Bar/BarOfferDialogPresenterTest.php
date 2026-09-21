<?php

namespace Tests\Feature\Bar;

/**
 * BarOfferDialogPresenter (GDD §12 "Was der Angebotsdialog zeigt", A13/P2b):
 * the numbers the Cantina offer dialog shows — safe outcome, negotiated outcome
 * with both branches, chance breakdown — all derived from BarService::effectiveTerms()
 * / negotiateChance(), i.e. the very same computations acceptOffer()/negotiateOffer() use.
 */

use App\Services\BarOfferDialogPresenter;
use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarOfferDialogPresenterTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADER_ADVISOR_ID = 92;

    private const TRADING_POST_ID = 55;

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private BarService $barService;

    private BarOfferDialogPresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        app()->setLocale('de');
        $this->barService = $this->app->make(BarService::class);
        $this->presenter = $this->app->make(BarOfferDialogPresenter::class);
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
        $this->setConsulRank(0);
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
    }

    private function setConsulRank(int $rank): void
    {
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        if ($rank > 0) {
            DB::table('advisors')->insert([
                'colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID, 'rank' => $rank,
                'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null,
            ]);
        }
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

    private function offer(array $overrides = []): object
    {
        $id = DB::table('bar_offers')->insertGetId(array_merge([
            'colony_id' => self::COLONY_ID, 'give_resource_id' => self::RES_REGOLITH, 'give_amount' => 20,
            'get_resource_id' => self::RES_ORGANICS, 'get_amount' => 10, 'expires_tick' => 20,
            'is_accepted' => false, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));

        return DB::table('bar_offers')->where('id', $id)->first();
    }

    private function dialog(object $offer): array
    {
        $terms = $this->barService->effectiveTerms($offer, self::COLONY_ID);

        return $this->presenter->present($terms, $this->barService->negotiateChance(self::COLONY_ID), self::COLONY_ID);
    }

    // ── Outcomes in numbers ───────────────────────────────────────────────────

    public function test_safe_and_negotiated_amounts_with_all_sources(): void
    {
        $this->setConsulRank(2);      // +20 %, chance 65 %
        $this->setTradingPostLevel(1); // +12 %
        $this->setTradeLevel(3);       // +8 %, chance bonus +5 (1+2+2)
        $dialog = $this->dialog($this->offer());

        $this->assertSame(10, $dialog['base_get']);
        $this->assertSame(14, $dialog['amount_safe']);      // 10 x 1.40
        $this->assertSame(4, $dialog['plus_safe']);
        $this->assertSame(16, $dialog['amount_negotiated']); // 10 x (1.40 + 0.20)
        $this->assertSame(6, $dialog['plus_negotiated']);
        $this->assertSame(2, $dialog['bonus_amount'], 'the negotiation share alone: 16 - 14');
        $this->assertSame(20, $dialog['negotiation_percent']);
        $this->assertTrue($dialog['can_negotiate']);
        $this->assertFalse($dialog['fixed_price']);
        $this->assertFalse($dialog['negotiated']);
    }

    public function test_the_negotiation_sum_parts_add_up_to_the_shown_total_even_when_rounding_bites(): void
    {
        $this->setConsulRank(1);       // +10 %
        $this->setTradingPostLevel(1); // +12 % => +22 %
        $dialog = $this->dialog($this->offer(['get_amount' => 7]));

        // Parts are differences of the service-rounded totals, so they always sum exactly.
        $this->assertSame(
            $dialog['amount_negotiated'],
            $dialog['base_get'] + $dialog['plus_safe'] + $dialog['bonus_amount']
        );
    }

    public function test_the_shown_totals_are_what_the_service_books(): void
    {
        $this->setConsulRank(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(2);
        $offer = $this->offer();
        $dialog = $this->dialog($offer);

        $terms = $this->barService->effectiveTerms($offer, self::COLONY_ID);
        $this->assertSame($terms['get_amount'], $dialog['amount_safe']);
        $this->assertSame($terms['get_amount_if_negotiated'], $dialog['amount_negotiated']);
    }

    public function test_an_already_negotiated_offer_shows_the_negotiated_total_and_no_further_negotiation(): void
    {
        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $offer = $this->offer(['is_negotiated' => true]);
        $dialog = $this->dialog($offer);

        $this->assertTrue($dialog['negotiated']);
        $this->assertFalse($dialog['can_negotiate']);
        $this->assertSame(13, $dialog['amount_safe'], 'advantage only: 10 x 1.32');
        $this->assertSame(15, $dialog['amount_negotiated'], '10 x (1.32 + 0.20) = 15.2');
        $this->assertSame(
            $this->barService->effectiveTerms($offer, self::COLONY_ID)['get_amount'],
            $dialog['amount_negotiated'],
            'a negotiated offer executes at the negotiated amount'
        );
    }

    public function test_small_amounts_can_show_no_visible_plus(): void
    {
        $this->setTradingPostLevel(1); // +12 % of 3 = 0.36
        $dialog = $this->dialog($this->offer(['get_amount' => 3]));

        $this->assertSame(3, $dialog['amount_safe']);
        $this->assertSame(0, $dialog['plus_safe']);
        $this->assertSame(12, $dialog['advantage']['total_percent'], 'the source line stays visible');
    }

    // ── Negotiation availability and chance ───────────────────────────────────

    public function test_without_a_consul_there_is_nothing_to_negotiate_and_the_hint_explains_why(): void
    {
        $this->setTradingPostLevel(1);
        $dialog = $this->dialog($this->offer());

        $this->assertFalse($dialog['can_negotiate']);
        $this->assertNull($dialog['chance']);
        $this->assertStringContainsString('Kein Konsul', implode(' ', $dialog['advantage']['hints']));
    }

    public function test_chance_breakdown_names_rank_base_and_knowledge_share(): void
    {
        $this->setConsulRank(2);
        $this->setTradeLevel(3);
        $dialog = $this->dialog($this->offer());

        $this->assertSame(70, $dialog['chance']['total_percent']);
        $this->assertSame(30, $dialog['chance']['lose_percent']);
        $this->assertSame('Chance 70 % = Konsul Rang 2: 65 % + Kenntnis Handel +5 %', $dialog['chance']['breakdown']);
    }

    public function test_chance_without_knowledge_bonus_names_only_the_rank(): void
    {
        $this->setConsulRank(3);
        $dialog = $this->dialog($this->offer());

        $this->assertSame(70, $dialog['chance']['total_percent']);
        $this->assertSame('Chance 70 % (Konsul Rang 3)', $dialog['chance']['breakdown']);
    }

    public function test_a_capped_chance_is_shown_as_the_total_only_never_explaining_the_cap(): void
    {
        $this->setConsulRank(3);
        $this->setTradeLevel(5);
        config(['game.bar.trade_terms.negotiate_chance_max' => 0.72]); // 70 + 8 = 78 > 72

        $dialog = $this->dialog($this->offer());

        $this->assertSame(72, $dialog['chance']['total_percent']);
        $this->assertSame('Chance 72 %', $dialog['chance']['breakdown']);
    }

    public function test_the_negotiation_sum_line_uses_the_service_totals(): void
    {
        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(3);
        $dialog = $this->dialog($this->offer());

        $this->assertSame(
            'Verhandlungs-Aufschlag: +20 % (10 Basis + 4 Vorteil + 2 Aufschlag = 16 Organika)',
            $dialog['negotiation_sum']
        );
    }

    // ── Fixed-price lots and Corvan buy offers ────────────────────────────────

    public function test_a_sell_lot_is_fixed_price_without_negotiation_advantage_or_hints(): void
    {
        $this->setConsulRank(3);
        $this->setTradingPostLevel(3);
        $visit = DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => 10, 'tick_end' => 11,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $dialog = $this->dialog($this->offer([
            'visit_id' => $visit, 'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
        ]));

        $this->assertTrue($dialog['fixed_price']);
        $this->assertFalse($dialog['can_negotiate']);
        $this->assertNull($dialog['chance']);
        $this->assertSame([], $dialog['advantage']['lines']);
        $this->assertSame([], $dialog['advantage']['hints']);
        $this->assertSame(700, $dialog['amount_safe']);
        $this->assertNull($dialog['amount_negotiated']);
        $this->assertStringContainsString(
            'Corvan kauft 20 Organika für 700 Cr. Festpreis',
            $dialog['fixed_price_note']
        );
    }

    public function test_a_corvan_buy_offer_keeps_the_price_and_raises_the_amount(): void
    {
        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(3); // +40 %
        $visit = DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => 10, 'tick_end' => 11,
            'was_visited' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $dialog = $this->dialog($this->offer([
            'visit_id' => $visit, 'give_resource_id' => self::RES_CREDITS, 'give_amount' => 1100,
            'get_resource_id' => self::RES_COMPOUNDS, 'get_amount' => 10,
        ]));

        $this->assertFalse($dialog['fixed_price']);
        $this->assertTrue($dialog['credits_give']);
        $this->assertSame(14, $dialog['amount_safe']);
        $this->assertSame(1100, $dialog['give_amount'], 'the price never changes');
        $this->assertSame('10 Werkstoffe für 1100 Cr', $dialog['base_credits_text']);
        $this->assertSame('Der Preis bleibt bei 1100 Cr, der Vorteil erhöht die Menge.', $dialog['price_stays_text']);
    }
}
