<?php

namespace Tests\Feature\Trade;

/**
 * Nexus-Kanal / Handelsposten Stufe III (A13 P4, GDD §4 "Handelsposten", §12 "Handelsvorteil"):
 * the Handelsvorteil of the 'nexus' channel lowers the PRICE of the Nexus direct
 * import (Werkstoffe instant, Regolith/Organika delayed). The delivery time stays
 * exclusively an Uplink-Station-level lever (Owner-Entscheidung F5).
 *
 * One price, three consumers: the quote (Kommandozentrale preview), the credit
 * check and the debit must all read the same rounded per-unit price.
 */

use App\Models\User;
use App\Services\NexusImportService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NexusImportPriceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private const UPLINK_ID = 54;

    private const TRADING_POST_ID = 55;

    private const TRADER_ADVISOR_ID = 92;

    private NexusImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.resource_costs' => false]);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->where('personell_id', self::TRADER_ADVISOR_ID)->delete();
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
        $this->setUplinkLevel(1);
        $this->service = $this->app->make(NexusImportService::class);
    }

    private function bart(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function setUplinkLevel(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::UPLINK_ID, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
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

    private function assignConsul(int $rank): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'personell_id' => self::TRADER_ADVISOR_ID],
            ['rank' => $rank, 'user_id' => self::USER_ID, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => $amount]);
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::USER_ID)->value('credits');
    }

    // ── Central price method ─────────────────────────────────────────────────

    public function test_base_prices_come_from_config(): void
    {
        $this->assertSame(165, $this->service->basePrice(self::RES_COMPOUNDS));
        $this->assertSame(35, $this->service->basePrice(self::RES_REGOLITH));
        $this->assertSame(65, $this->service->basePrice(self::RES_ORGANICS));
        $this->assertNull($this->service->basePrice(1));
    }

    public function test_without_any_advantage_the_price_is_the_base_price(): void
    {
        $this->assertSame(165, $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS));
        $this->assertSame(35, $this->service->unitPrice(self::COLONY_ID, self::RES_REGOLITH));
        $this->assertSame(65, $this->service->unitPrice(self::COLONY_ID, self::RES_ORGANICS));
    }

    /** @return array<string, array{int, int, int, int, int}> */
    public static function priceProvider(): array
    {
        // tradingPostLevel, tradeLevel, Werkstoffe (165), Regolith (35), Organika (65)
        return [
            'trading post tier 3 (12 %)' => [3, 0, 145, 31, 57],      // 145.2 / 30.8 / 57.2
            'trade knowledge Lv3 (8 %)' => [0, 3, 152, 32, 60],       // 151.8 / 32.2 / 59.8
            'trade knowledge Lv5 (12 %)' => [0, 5, 145, 31, 57],
            'tier 3 + trade Lv3 (20 %)' => [3, 3, 132, 28, 52],       // 132 / 28 / 52
            'tier 3 + trade Lv5 (24 %, max)' => [3, 5, 125, 27, 49],  // 125.4 / 26.6 / 49.4
        ];
    }

    #[DataProvider('priceProvider')]
    public function test_price_per_resource_is_rounded_to_whole_credits(int $tier, int $trade, int $compounds, int $regolith, int $organics): void
    {
        $this->setTradingPostLevel($tier);
        $this->setTradeLevel($trade);

        $this->assertSame($compounds, $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS));
        $this->assertSame($regolith, $this->service->unitPrice(self::COLONY_ID, self::RES_REGOLITH));
        $this->assertSame($organics, $this->service->unitPrice(self::COLONY_ID, self::RES_ORGANICS));
    }

    public function test_trading_post_below_tier_3_does_not_touch_the_nexus_price(): void
    {
        foreach ([1, 2] as $tier) {
            $this->setTradingPostLevel($tier);

            $this->assertSame(165, $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS), "tier {$tier}");
        }
    }

    public function test_consul_is_not_a_source_of_the_nexus_price(): void
    {
        $this->assignConsul(3);

        $this->assertSame(165, $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS));
    }

    public function test_silent_cap_limits_the_price_discount(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);
        config(['game.bar.trade_terms.silent_cap.nexus' => 0.10]);

        $this->assertSame(149, $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS)); // 165 x 0.90 = 148.5 -> 149
    }

    public function test_total_cost_is_amount_times_the_rounded_unit_price(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);

        $this->assertSame(10 * 27, $this->service->totalCost(self::COLONY_ID, self::RES_REGOLITH, 10));
    }

    public function test_quote_lists_every_importable_resource_with_base_and_price(): void
    {
        $this->setTradingPostLevel(3);

        $quote = $this->service->quote(self::COLONY_ID);

        $this->assertSame([self::RES_REGOLITH, self::RES_COMPOUNDS, self::RES_ORGANICS], array_keys($quote['resources']));
        $this->assertSame(['base' => 165, 'price' => 145], $quote['resources'][self::RES_COMPOUNDS]);
        $this->assertSame(['base' => 35, 'price' => 31], $quote['resources'][self::RES_REGOLITH]);
        $this->assertSame(12, $quote['advantage']['total_percent']);
        $this->assertSame('nexus', $quote['advantage']['channel']);
    }

    // ── Execution: charged == quoted == logged ────────────────────────────────

    public function test_compound_import_charges_the_quoted_price_and_logs_it(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(3);
        $this->setCredits(10_000);
        $unit = $this->service->unitPrice(self::COLONY_ID, self::RES_COMPOUNDS);
        $this->assertSame(132, $unit);

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import'), ['amount' => 10])
            ->assertOk()
            ->assertJsonPath('cost', 1320)
            ->assertJsonPath('unit_price', 132)
            ->assertJsonPath('credits', 10_000 - 1320);

        $this->assertSame(10_000 - 1320, $this->credits());

        $event = DB::table('colony_log')->where('event', 'colony.compounds_imported')->latest('id')->first();
        $params = json_decode($event->parameters, true);
        $this->assertSame(1320, $params['cost']);
        $this->assertSame(132, $params['unit_price']);
    }

    public function test_compound_import_credit_check_uses_the_discounted_price(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5); // 24 % -> 125 Cr

        $this->setCredits(1249); // 1 Cr short of 10 x 125 (undiscounted would be 1650)
        $this->actingAs($this->bart())->postJson(route('colony.nexus.import'), ['amount' => 10])
            ->assertStatus(422)->assertJsonPath('error', 'credit_limit');
        $this->assertSame(1249, $this->credits());

        $this->setCredits(1250); // exactly enough at the discounted price
        $this->actingAs($this->bart())->postJson(route('colony.nexus.import'), ['amount' => 10])
            ->assertOk();
        $this->assertSame(0, $this->credits());
    }

    public function test_delayed_import_charges_the_quoted_price_and_logs_it(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);
        $this->setCredits(10_000);

        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_ORGANICS, 'amount' => 20])
            ->assertOk()
            ->assertJsonPath('cost', 20 * 49)
            ->assertJsonPath('unit_price', 49)
            ->assertJsonPath('credits', 10_000 - 20 * 49);

        $this->assertSame(10_000 - 980, $this->credits());

        $params = json_decode(
            DB::table('colony_log')->where('event', 'colony.nexus_import_requested')->latest('id')->value('parameters'),
            true
        );
        $this->assertSame(980, $params['cost']);
        $this->assertSame(49, $params['unit_price']);
        $this->assertSame(20, (int) DB::table('nexus_imports')->where('colony_id', self::COLONY_ID)->value('amount'));
    }

    public function test_delayed_import_credit_check_uses_the_discounted_price(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5); // Regolith 35 -> 27

        $this->setCredits(269);
        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 10])
            ->assertStatus(422)->assertJsonPath('error', 'credit_limit');
        $this->assertNull(DB::table('nexus_imports')->first());

        $this->setCredits(270);
        $this->actingAs($this->bart())
            ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 10])
            ->assertOk();
        $this->assertSame(0, $this->credits());
    }

    public function test_quote_price_equals_charged_price_for_every_resource(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(2);
        $this->setCredits(100_000);
        $quote = $this->service->quote(self::COLONY_ID);

        $before = $this->credits();
        $this->actingAs($this->bart())->postJson(route('colony.nexus.import'), ['amount' => 7])->assertOk();
        $this->assertSame($before - 7 * $quote['resources'][self::RES_COMPOUNDS]['price'], $this->credits());

        foreach ([self::RES_REGOLITH, self::RES_ORGANICS] as $resourceId) {
            $before = $this->credits();
            $this->actingAs($this->bart())
                ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => $resourceId, 'amount' => 7])
                ->assertOk();
            $this->assertSame($before - 7 * $quote['resources'][$resourceId]['price'], $this->credits(), "resource {$resourceId}");
        }
    }

    // ── Delivery time stays an Uplink lever ───────────────────────────────────

    public function test_trading_post_does_not_change_the_delivery_time(): void
    {
        $this->setUplinkLevel(2);
        $this->setCredits(100_000);
        $tick = app(TickService::class)->getTickCount();
        $expected = $tick + (int) config('game.economy.delayed_import_delivery_ticks.2');

        foreach ([0, 3] as $tier) {
            DB::table('nexus_imports')->delete();
            $this->setTradingPostLevel($tier);
            $this->setTradeLevel($tier === 3 ? 5 : 0);

            $this->actingAs($this->bart())
                ->postJson(route('colony.nexus.import-delayed'), ['resource_id' => self::RES_REGOLITH, 'amount' => 3])
                ->assertOk()
                ->assertJsonPath('delivery_ticks', (int) config('game.economy.delayed_import_delivery_ticks.2'));

            $this->assertSame($expected, (int) DB::table('nexus_imports')->value('deliver_at_tick'), "tier {$tier}");
        }
    }

    // ── Kommandozentrale preview ──────────────────────────────────────────────

    public function test_command_center_preview_uses_the_central_quote(): void
    {
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertOk();
        $response->assertViewHas('nexusImport', function (array $quote) {
            return $quote['resources'][self::RES_COMPOUNDS] === ['base' => 165, 'price' => 125]
                && $quote['resources'][self::RES_REGOLITH] === ['base' => 35, 'price' => 27]
                && $quote['resources'][self::RES_ORGANICS] === ['base' => 65, 'price' => 49]
                && $quote['advantage']['total_percent'] === 24;
        });
    }

    public function test_command_center_shows_sources_line_and_base_price_with_advantage(): void
    {
        app()->setLocale('de'); // the player-facing strings exist in German only (lang/en is not maintained)
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(3);

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertSee(__('colony.trade_line_trading_post', ['tier' => 'III']).' −12 %', false);
        $response->assertSee(__('colony.trade_line_trade_knowledge', ['level' => 3]).' −8 %', false);
        $response->assertSee('132', false);
        $response->assertSee('165', false);
    }

    public function test_command_center_without_advantage_hints_at_the_trading_post(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertOk();
        $response->assertSee(__('colony.trade_hint_trading_post_tier', ['tier' => 'III', 'percent' => 12]), false);
        $response->assertDontSee('cc-nexus-price-sources', false);
    }

    public function test_command_center_hides_the_price_hint_when_tier_3_is_built(): void
    {
        $this->setTradingPostLevel(3);

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertDontSee(__('colony.trade_hint_trading_post_tier', ['tier' => 'III', 'percent' => 12]), false);
    }
}
