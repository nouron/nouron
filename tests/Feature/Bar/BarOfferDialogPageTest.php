<?php

namespace Tests\Feature\Bar;

/**
 * Cantina page — offer dialog with source breakdown (GDD §12 "Was der Angebotsdialog
 * zeigt", A13/P2b). Renders the real page and checks what the player reads:
 *   - basis offer, every ACTIVE Handelsvorteil source on its own line, result with "+X vs. Basis",
 *   - both negotiation outcomes in numbers, the chance with its sources, the Verhandlungs-Aufschlag,
 *   - quiet hints for a missing Konsul / Handelsposten (values from config),
 *   - fixed-price sell lots, Corvan buy offers (price stays, amount rises),
 *   - the Cantina header line, Corvan's price line, the toast state fix,
 *   - and that every shown end amount equals what accept/negotiate actually book.
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BarOfferDialogPageTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        app()->setLocale('de');
        $this->app->instance(TickService::class, new TickService(10));
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_BUILDING_ID)->update(['level' => 1]);
        $this->assignConsul(0);
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
        config(['game.bypass.ap_checks' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assignConsul(int $rank): void
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
            'colony_id' => self::COLONY_ID, 'give_resource_id' => self::RES_REGOLITH, 'give_amount' => 20,
            'get_resource_id' => self::RES_ORGANICS, 'get_amount' => 10, 'expires_tick' => 20,
            'is_accepted' => false, 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }

    private function fullAdvantage(): void
    {
        $this->assignConsul(2);        // +20 %, chance 65 %
        $this->setTradingPostLevel(1); // +12 %
        $this->setTradeLevel(3);       // +8 %, chance +5
    }

    /** Whitespace-normalised text of one offer's dialog (tags stripped). */
    private function dialogText(int $offerId): string
    {
        $html = $this->page();
        $start = strpos($html, "activeModal === 'offer_{$offerId}'");
        $this->assertNotFalse($start, 'offer dialog present');
        $end = strpos($html, "activeModal === 'offer_", $start + 10);
        $end = $end === false ? $this->dialogEnd($html, $start) : $end;
        $block = substr($html, $start, $end - $start);

        return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($block)));
    }

    private function dialogHtml(int $offerId): string
    {
        $html = $this->page();
        $start = strpos($html, "activeModal === 'offer_{$offerId}'");
        $end = strpos($html, "activeModal === 'offer_", $start + 10);
        $end = $end === false ? $this->dialogEnd($html, $start) : $end;

        return substr($html, $start, $end - $start);
    }

    /** End of the last offer dialog: the first of the blocks that always follow the offers. */
    private function dialogEnd(string $html, int $start): int
    {
        $ends = array_filter([
            strpos($html, "activeModal === 'encounter'", $start),
            strpos($html, "activeModal === 'story'", $start),
            strpos($html, "activeModal === 'bartender'", $start),
        ], fn ($p) => $p !== false);

        return min($ends);
    }

    private function page(): string
    {
        return $this->actingAs(User::find(self::USER_ID))->get(route('colony.bar'))->assertOk()->getContent();
    }

    // ── Guest barter: sources, result, both negotiation outcomes ──────────────

    public function test_dialog_shows_basis_every_active_source_and_the_result_with_plus_over_basis(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer();

        $text = $this->dialogText($id);

        $this->assertStringContainsString('Basisangebot (Marktwert) 20 Regolith → 10 Organika', $text);
        $this->assertStringContainsString('Dein Handelsvorteil +40 %', $text);
        $this->assertStringContainsString('Konsul (Rang 2) +20 %', $text);
        $this->assertStringContainsString('Handelsposten (Stufe I) +12 %', $text);
        $this->assertStringContainsString('Kenntnis Handel (Lv 3) +8 %', $text);
        $this->assertMatchesRegularExpression('/Du erhältst\s+Or\s*14\s+\+4 gegenüber Basis/', $text);
    }

    public function test_dialog_shows_both_negotiation_outcomes_the_chance_sources_and_the_surcharge(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer();

        $text = $this->dialogText($id);

        $this->assertStringContainsString('sicher: 14 Organika (+4)', $text);
        $this->assertStringContainsString('70 %: 16 Organika (+6)', $text);
        $this->assertStringContainsString('30 %: kein Handel, du behältst Regolith, das Angebot verfällt, 2 AP verbraucht.', $text);
        $this->assertStringContainsString('Chance 70 % = Konsul Rang 2: 65 % + Kenntnis Handel +5 %', $text);
        $this->assertStringContainsString('Verhandlungs-Aufschlag: +20 % (10 Basis + 4 Vorteil + 2 Aufschlag = 16 Organika)', $text);
    }

    public function test_both_buttons_carry_the_ap_cost_chip(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer();

        $html = $this->dialogHtml($id);

        $this->assertStringContainsString('Eco 2 AP', $html);
        $this->assertSame(2, (int) config('game.bar.ap_cost_accept'));
        $this->assertSame(2, substr_count($html, 'ap-cost-chip'), 'Annehmen and Verhandeln each show their cost');
    }

    /** @return array<string, array{int, string, string}> */
    public static function consulRanks(): array
    {
        return [
            'rank 1' => [1, 'Konsul (Rang 1) +10 %', 'Chance 60 % (Konsul Rang 1)'],
            'rank 2' => [2, 'Konsul (Rang 2) +20 %', 'Chance 65 % (Konsul Rang 2)'],
            'rank 3' => [3, 'Konsul (Rang 3) +30 %', 'Chance 70 % (Konsul Rang 3)'],
        ];
    }

    #[DataProvider('consulRanks')]
    public function test_konsul_line_and_chance_follow_the_rank(int $rank, string $line, string $chance): void
    {
        $this->assignConsul($rank);
        $id = $this->insertOffer();

        $text = $this->dialogText($id);

        $this->assertStringContainsString($line, $text);
        $this->assertStringContainsString($chance, $text, 'no trade knowledge => the chance names only the rank');
        $this->assertStringNotContainsString('Handelsposten (Stufe', $text, 'inactive sources get no line');
        $this->assertStringNotContainsString('Kenntnis Handel (Lv', $text);
    }

    public function test_without_consul_there_is_no_negotiate_button_and_a_hint_says_what_a_junior_would_bring(): void
    {
        $this->setTradingPostLevel(1);
        $id = $this->insertOffer();

        $html = $this->dialogHtml($id);
        $text = $this->dialogText($id);

        $this->assertStringNotContainsString('negotiate('.$id, $html);
        $this->assertStringNotContainsString('Konsul (Rang', $text, 'no inactive Konsul line');
        $juniorPercent = (int) round(config('game.bar.trader_discount.1') * 100);
        $this->assertStringContainsString("Kein Konsul verfügbar — kein Verhandeln möglich. Ein Junior-Konsul brächte +{$juniorPercent} %.", $text);
        $this->assertStringNotContainsString('Chance', $text);
        $this->assertStringContainsString('sicher:', $text, 'Annehmen still shows its certain outcome');
    }

    public function test_without_trading_post_a_hint_names_the_cc_level_and_the_config_bonus(): void
    {
        $this->assignConsul(2);
        $id = $this->insertOffer();

        $text = $this->dialogText($id);

        $cc = (int) DB::table('buildings')->where('id', self::TRADING_POST_ID)->value('required_building_level');
        $percent = (int) round(config('buildings.tradingPost.merchant_price_bonus') * 100);
        $this->assertStringContainsString("Handelsposten (ab CC {$cc}) würde +{$percent} % bringen.", $text);
        $this->assertStringNotContainsString('Handelsposten (Stufe', $text);
    }

    public function test_source_line_stays_visible_when_the_advantage_is_invisible_on_a_small_amount(): void
    {
        $this->setTradingPostLevel(1); // +12 % of 3 rounds to +0
        $id = $this->insertOffer(['give_amount' => 6, 'get_amount' => 3]);

        $text = $this->dialogText($id);

        $this->assertStringContainsString('Handelsposten (Stufe I) +12 %', $text);
        $this->assertMatchesRegularExpression('/Du erhältst\s+Or\s*3\s+\+0 gegenüber Basis/', $text);
    }

    // ── Already negotiated ────────────────────────────────────────────────────

    public function test_an_already_negotiated_offer_starts_in_the_negotiated_state(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer(['is_negotiated' => true]);

        $html = $this->page();
        $text = $this->dialogText($id);

        $this->assertStringContainsString('Verhandelt:', $text);
        $this->assertStringContainsString('negotiatedOffers', $html);
        $this->assertMatchesRegularExpression('/negotiatedOffers"?\s*:\s*\{"?'.$id.'"?\s*:\s*true/', $html, 'the page script starts with this offer flagged as negotiated');
        $this->assertStringNotContainsString('negotiate('.$id, $this->dialogHtml($id), 'no second negotiation offered');
    }

    // ── Fixed-price lots and Corvan buy offers ────────────────────────────────

    public function test_sell_lot_shows_the_fixed_price_text_and_neither_advantage_nor_negotiate(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20,
            'get_resource_id' => self::RES_CREDITS, 'get_amount' => 700,
        ]);

        $text = $this->dialogText($id);

        $this->assertStringContainsString('Corvan kauft 20 Organika für 700 Cr. Festpreis: Handelsvorteil und Verhandeln gelten nicht für Verkaufs-Credits.', $text);
        $this->assertStringNotContainsString('Dein Handelsvorteil', $text);
        $this->assertStringNotContainsString('Konsul (Rang', $text);
        $this->assertStringNotContainsString('Chance', $text);
        $this->assertStringNotContainsString('Kein Konsul', $text);
        $this->assertStringNotContainsString('negotiate('.$id, $this->dialogHtml($id));
        $this->assertMatchesRegularExpression('/Cr\s*700/', $text);
    }

    public function test_corvan_buy_offer_keeps_the_price_and_raises_the_amount(): void
    {
        $this->fullAdvantage(); // +40 %
        $id = $this->insertOffer([
            'visit_id' => $this->insertVisit(),
            'give_resource_id' => self::RES_CREDITS, 'give_amount' => 1100,
            'get_resource_id' => self::RES_COMPOUNDS, 'get_amount' => 10,
        ]);

        $text = $this->dialogText($id);

        $this->assertStringContainsString('Basisangebot 10 Werkstoffe für 1100 Cr', $text);
        $this->assertStringContainsString('Dein Handelsvorteil +40 %', $text);
        $this->assertMatchesRegularExpression('/Du erhältst\s+Co\s*14\s+\+4 gegenüber Basis/', $text);
        $this->assertStringContainsString('Der Preis bleibt bei 1100 Cr, der Vorteil erhöht die Menge.', $text);
        $this->assertStringContainsString('sicher: 14 Werkstoffe (+4)', $text);
    }

    // ── Cantina header line ───────────────────────────────────────────────────

    public function test_header_line_summarises_the_advantage_with_its_sources(): void
    {
        $this->fullAdvantage();
        $this->insertOffer();

        $html = $this->page();

        $this->assertStringContainsString('class="trade-advantage-bar"', $html);
        $this->assertStringContainsString('Handelsvorteil +40 % (Konsul Rang 2 +20, Handelsposten +12, Handel Lv3 +8)', $html);
    }

    public function test_header_line_is_absent_without_any_advantage(): void
    {
        $this->insertOffer();

        $html = $this->page();

        $this->assertStringNotContainsString('class="trade-advantage-bar"', $html);
        $this->assertStringNotContainsString('Handelsvorteil +0', $html);
    }

    // ── Corvan's special inventory ────────────────────────────────────────────

    private function insertMerchantItem(int $visitId, int $cost): void
    {
        DB::table('merchant_items')->insert([
            'visit_id' => $visitId, 'item_type' => 'repair_kit', 'label' => 'Kit', 'cost_credits' => $cost,
            'payload' => '{}', 'sold' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_special_inventory_shows_the_charged_price_next_to_the_base_and_a_source_line(): void
    {
        $this->setTradingPostLevel(2);
        $this->setTradeLevel(3); // −12 −8 = −20 %
        $this->insertMerchantItem($this->insertVisit(), 600);

        $html = $this->page();

        $this->assertStringContainsString('Preisvorteil −20 % (Handelsposten −12, Handel Lv3 −8)', $html);
        $this->assertStringContainsString('"cost_credits":600', $html);
        $this->assertStringContainsString('"price_credits":480', $html, '600 x 0.80 = the price the purchase charges');
    }

    public function test_special_inventory_without_advantage_has_no_price_line_but_a_hint(): void
    {
        $this->insertMerchantItem($this->insertVisit(), 600);

        $html = $this->page();

        $this->assertStringNotContainsString('Preisvorteil', $html);
        $this->assertStringContainsString('Handelsposten ab Stufe II würde −12 % bringen.', $html);
    }

    // ── Toast state ───────────────────────────────────────────────────────────

    public function test_toast_is_bound_to_the_dialog_that_raised_it(): void
    {
        $this->fullAdvantage();
        $id = $this->insertOffer();
        $this->insertMerchantItem($this->insertVisit(), 600);

        $html = $this->page();

        $this->assertStringContainsString("toast.visible && toast.modal === 'offer_{$id}'", $html);
        $this->assertStringContainsString("toast.visible && toast.modal === 'merchant'", $html);
        $this->assertStringContainsString('hideToast()', $html, 'opening/closing a dialog resets the toast');
    }

    // ── Shown == booked ───────────────────────────────────────────────────────

    public function test_the_shown_safe_amount_is_what_accept_books(): void
    {
        $this->fullAdvantage();
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH], ['amount' => 100]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANICS], ['amount' => 0]);
        $id = $this->insertOffer();

        $this->assertStringContainsString('sicher: 14 Organika (+4)', $this->dialogText($id));

        $this->actingAs(User::find(self::USER_ID))->postJson(route('colony.bar.accept', ['offer' => $id]))
            ->assertOk()->assertJsonPath('get_amount', 14);
        $this->assertSame(14, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_ORGANICS)->value('amount'));
    }

    public function test_the_shown_negotiated_amount_is_what_negotiate_and_accept_book(): void
    {
        $this->fullAdvantage();
        config(['game.bar.negotiate_success_chance' => [0 => 0.0, 1 => 1.0, 2 => 1.0, 3 => 1.0], 'game.bar.trade_terms.negotiate_chance_max' => 1.0]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH], ['amount' => 100]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_ORGANICS], ['amount' => 0]);
        $id = $this->insertOffer();

        $this->assertStringContainsString('100 %: 16 Organika (+6)', $this->dialogText($id));

        $user = User::find(self::USER_ID);
        $this->actingAs($user)->postJson(route('colony.bar.negotiate', ['offer' => $id]))
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('terms.get_amount', 16);
        $this->actingAs($user)->postJson(route('colony.bar.accept', ['offer' => $id]))
            ->assertOk()->assertJsonPath('get_amount', 16);
        $this->assertSame(16, (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', self::RES_ORGANICS)->value('amount'));
    }
}
