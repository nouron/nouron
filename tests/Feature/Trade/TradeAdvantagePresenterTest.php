<?php

namespace Tests\Feature\Trade;

/**
 * TradeAdvantagePresenter (GDD §12 "Was der Angebotsdialog zeigt", A13/P2b):
 * turns the TradeAdvantageService result into the player-facing lines — every
 * ACTIVE source as its own line (with its qualifier: rank / tier / level), a
 * short one-line summary for the Cantina header, and quiet hints for missing
 * sources. It only formats numbers the service computed; it never re-derives them.
 */

use App\Services\TradeAdvantagePresenter;
use App\Services\TradeAdvantageService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TradeAdvantagePresenterTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADER_ADVISOR_ID = 92;

    private const TRADING_POST_ID = 55;

    private TradeAdvantagePresenter $presenter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        app()->setLocale('de');
        $this->presenter = $this->app->make(TradeAdvantagePresenter::class);
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

    // ── Cantina channel ───────────────────────────────────────────────────────

    public function test_bar_channel_lists_every_active_source_with_its_qualifier(): void
    {
        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(3);

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);

        $this->assertSame(40, $view['total_percent']);
        $this->assertSame('+40 %', $view['total_text']);
        $this->assertSame(
            [
                ['key' => 'consul', 'label' => 'Konsul (Rang 2)', 'percent' => 20, 'percent_text' => '+20 %'],
                ['key' => 'trading_post', 'label' => 'Handelsposten (Stufe I)', 'percent' => 12, 'percent_text' => '+12 %'],
                ['key' => 'trade_knowledge', 'label' => 'Kenntnis Handel (Lv 3)', 'percent' => 8, 'percent_text' => '+8 %'],
            ],
            array_map(fn (array $l) => array_intersect_key($l, array_flip(['key', 'label', 'percent', 'percent_text'])), $view['lines'])
        );
        $this->assertSame([], $view['hints'], 'nothing missing => no hints');
    }

    public function test_bar_channel_summary_line_names_short_labels_with_signs(): void
    {
        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(3);

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);

        $this->assertSame('Konsul Rang 2 +20, Handelsposten +12, Handel Lv3 +8', $view['sources_text']);
    }

    public function test_summary_is_the_one_line_the_cantina_header_shows_and_absent_without_advantage(): void
    {
        $none = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);
        $this->assertNull($none['summary'], 'no advantage => no header line');

        $this->setConsulRank(2);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(3);
        $bar = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);
        $this->assertSame('Handelsvorteil +40 % (Konsul Rang 2 +20, Handelsposten +12, Handel Lv3 +8)', $bar['summary']);
    }

    public function test_price_channels_summary_speaks_of_a_price_advantage(): void
    {
        $this->setTradingPostLevel(2);
        $this->setTradeLevel(3);

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_MERCHANT);

        $this->assertSame('Preisvorteil −20 % (Handelsposten −12, Handel Lv3 −8)', $view['summary']);
    }

    public function test_inactive_sources_are_not_listed_as_lines(): void
    {
        $this->setConsulRank(1); // +10 %
        // no trading post, no trade knowledge

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);

        $this->assertCount(1, $view['lines']);
        $this->assertSame('consul', $view['lines'][0]['key']);
        $this->assertSame('Konsul (Rang 1)', $view['lines'][0]['label']);
        $this->assertSame(10, $view['total_percent']);
    }

    public function test_without_any_source_there_are_no_lines_and_two_hints_with_config_values(): void
    {
        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);

        $this->assertSame([], $view['lines']);
        $this->assertSame(0, $view['total_percent']);
        $this->assertSame('', $view['sources_text']);
        $this->assertCount(2, $view['hints']);

        $juniorPercent = (int) round(config('game.bar.trader_discount.1') * 100);
        $postPercent = (int) round(config('buildings.tradingPost.merchant_price_bonus') * 100);
        $ccLevel = (int) DB::table('buildings')->where('id', self::TRADING_POST_ID)->value('required_building_level');

        $this->assertStringContainsString('Kein Konsul', $view['hints'][0]);
        $this->assertStringContainsString('Junior', $view['hints'][0]);
        $this->assertStringContainsString("+{$juniorPercent} %", $view['hints'][0]);
        $this->assertStringContainsString('Handelsposten', $view['hints'][1]);
        $this->assertStringContainsString("CC {$ccLevel}", $view['hints'][1]);
        $this->assertStringContainsString("+{$postPercent} %", $view['hints'][1]);
    }

    public function test_consul_hint_disappears_once_a_consul_is_available_and_post_hint_once_built(): void
    {
        $this->setConsulRank(3);
        $withPost = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR);
        $this->assertCount(1, $withPost['hints'], 'only the trading post is missing');
        $this->assertStringContainsString('Handelsposten', $withPost['hints'][0]);

        $this->setTradingPostLevel(1);
        $this->assertSame([], $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_BAR)['hints']);
    }

    // ── Credit-priced channels ────────────────────────────────────────────────

    public function test_merchant_channel_shows_price_discount_with_minus_and_no_consul_line(): void
    {
        $this->setConsulRank(3); // must NOT show up on the merchant channel
        $this->setTradingPostLevel(2);
        $this->setTradeLevel(3);

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_MERCHANT);

        $this->assertSame('−20 %', $view['total_text']);
        $this->assertSame(['trading_post', 'trade_knowledge'], array_column($view['lines'], 'key'));
        $this->assertSame('Handelsposten (Stufe II)', $view['lines'][0]['label']);
        $this->assertSame('−12 %', $view['lines'][0]['percent_text']);
        $this->assertSame('Handelsposten −12, Handel Lv3 −8', $view['sources_text']);
        $this->assertSame([], $view['hints']);
    }

    public function test_merchant_channel_hints_the_tier_that_unlocks_it_when_the_post_is_too_low(): void
    {
        $this->setTradingPostLevel(1); // Cantina only

        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_MERCHANT);

        $this->assertSame([], $view['lines']);
        $this->assertCount(1, $view['hints']);
        $this->assertStringContainsString('Stufe II', $view['hints'][0]);
        $this->assertStringContainsString('−12 %', $view['hints'][0]);
    }

    public function test_nexus_channel_has_no_consul_hint(): void
    {
        $view = $this->presenter->forChannel(self::COLONY_ID, TradeAdvantageService::CHANNEL_NEXUS);

        foreach ($view['hints'] as $hint) {
            $this->assertStringNotContainsString('Konsul', $hint);
        }
    }

    // ── Fixed-price lots ──────────────────────────────────────────────────────

    public function test_an_advantage_without_sources_yields_neither_lines_nor_hints(): void
    {
        $this->setConsulRank(3);
        $advantage = app(TradeAdvantageService::class)->forChannel(
            self::COLONY_ID,
            TradeAdvantageService::CHANNEL_BAR,
            [TradeAdvantageService::SOURCE_CONSUL, TradeAdvantageService::SOURCE_TRADING_POST, TradeAdvantageService::SOURCE_TRADE_KNOWLEDGE],
        );

        $view = $this->presenter->present(self::COLONY_ID, $advantage);

        $this->assertSame([], $view['lines']);
        $this->assertSame([], $view['hints']);
        $this->assertSame(0, $view['total_percent']);
    }
}
