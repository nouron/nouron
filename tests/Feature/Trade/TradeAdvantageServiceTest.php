<?php

namespace Tests\Feature\Trade;

/**
 * TradeAdvantageService (GDD §12 "Handelsvorteil", A13/P2a): the single source
 * of the additive per-channel trade advantage, used for BOTH execution and
 * display of bar offers, Corvan's special inventory and Orin.
 */

use App\Services\BarService;
use App\Services\TradeAdvantageService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Tests\TestCase;

class TradeAdvantageServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADER_ADVISOR_ID = 92;

    private const TRADING_POST_ID = 55;

    private TradeAdvantageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(TradeAdvantageService::class);
        $this->setConsulRank(0);
        $this->setTradingPostLevel(0);
        $this->setTradeLevel(0);
    }

    // ── Fixture helpers ───────────────────────────────────────────────────────

    private function setConsulRank(int $rank, ?int $unavailableUntilTick = null): void
    {
        DB::table('advisors')
            ->where('colony_id', self::COLONY_ID)
            ->where('personell_id', self::TRADER_ADVISOR_ID)
            ->delete();

        if ($rank > 0) {
            DB::table('advisors')->insert([
                'colony_id' => self::COLONY_ID,
                'personell_id' => self::TRADER_ADVISOR_ID,
                'rank' => $rank,
                'user_id' => self::USER_ID,
                'active_ticks' => 0,
                'unavailable_until_tick' => $unavailableUntilTick,
            ]);
        }
    }

    private function setTradingPostLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::TRADING_POST_ID)
            ->delete();

        if ($level > 0) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => self::TRADING_POST_ID,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
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

    /**
     * @return array<string, int> source key => percent
     */
    private function percentBySource(array $advantage): array
    {
        $out = [];
        foreach ($advantage['sources'] as $source) {
            $out[$source['key']] = $source['percent'];
        }

        return $out;
    }

    // ── Sources per channel / rank / tier / knowledge level ───────────────────

    /**
     * @return array<string, array{0: string, 1: int, 2: int, 3: int, 4: array<string, int>, 5: int}>
     */
    public static function sourceTable(): array
    {
        // channel, consul rank, trading post level, trade level, expected percent per source, expected total percent
        return [
            'bar: nothing' => ['bar', 0, 0, 0, ['consul' => 0, 'trading_post' => 0, 'trade_knowledge' => 0], 0],
            'bar: consul rank 1' => ['bar', 1, 0, 0, ['consul' => 10, 'trading_post' => 0, 'trade_knowledge' => 0], 10],
            'bar: consul rank 2' => ['bar', 2, 0, 0, ['consul' => 20, 'trading_post' => 0, 'trade_knowledge' => 0], 20],
            'bar: consul rank 3' => ['bar', 3, 0, 0, ['consul' => 30, 'trading_post' => 0, 'trade_knowledge' => 0], 30],
            'bar: trading post tier 1' => ['bar', 0, 1, 0, ['consul' => 0, 'trading_post' => 12, 'trade_knowledge' => 0], 12],
            'bar: trade knowledge lv3 (cumulative 2+3+3)' => ['bar', 0, 0, 3, ['consul' => 0, 'trading_post' => 0, 'trade_knowledge' => 8], 8],
            'bar: trade knowledge lv5' => ['bar', 0, 0, 5, ['consul' => 0, 'trading_post' => 0, 'trade_knowledge' => 12], 12],
            'bar: everything (max 54)' => ['bar', 3, 3, 5, ['consul' => 30, 'trading_post' => 12, 'trade_knowledge' => 12], 54],
            'bar: rank 2 + tier 1 + trade lv3 (additive)' => ['bar', 2, 1, 3, ['consul' => 20, 'trading_post' => 12, 'trade_knowledge' => 8], 40],
            'merchant: tier 1 gives nothing yet' => ['merchant', 0, 1, 0, ['trading_post' => 0, 'trade_knowledge' => 0], 0],
            'merchant: tier 2' => ['merchant', 0, 2, 0, ['trading_post' => 12, 'trade_knowledge' => 0], 12],
            'merchant: tier 3 + trade lv5 (max 24)' => ['merchant', 0, 3, 5, ['trading_post' => 12, 'trade_knowledge' => 12], 24],
            'merchant: consul is not a source' => ['merchant', 3, 2, 3, ['trading_post' => 12, 'trade_knowledge' => 8], 20],
            'nexus: tier 2 gives nothing yet' => ['nexus', 0, 2, 0, ['trading_post' => 0, 'trade_knowledge' => 0], 0],
            'nexus: tier 3' => ['nexus', 0, 3, 0, ['trading_post' => 12, 'trade_knowledge' => 0], 12],
            'nexus: tier 3 + trade lv5 (max 24)' => ['nexus', 0, 3, 5, ['trading_post' => 12, 'trade_knowledge' => 12], 24],
            'nexus: consul is not a source' => ['nexus', 3, 3, 0, ['trading_post' => 12, 'trade_knowledge' => 0], 12],
        ];
    }

    /**
     * @dataProvider sourceTable
     */
    public function test_sources_and_total_per_channel(string $channel, int $rank, int $tier, int $tradeLevel, array $expectedSources, int $expectedTotalPercent): void
    {
        $this->setConsulRank($rank);
        $this->setTradingPostLevel($tier);
        $this->setTradeLevel($tradeLevel);

        $advantage = $this->service->forChannel(self::COLONY_ID, $channel);

        $this->assertSame($channel, $advantage['channel']);
        $this->assertEquals($expectedSources, $this->percentBySource($advantage));
        $this->assertSame($expectedTotalPercent, $advantage['total_percent']);
        $this->assertEqualsWithDelta($expectedTotalPercent / 100, $advantage['total'], 1e-9);
        $this->assertFalse($advantage['capped']);
    }

    public function test_merchant_and_nexus_channels_never_list_a_consul_source(): void
    {
        $this->setConsulRank(3);
        $this->setTradingPostLevel(3);

        foreach (['merchant', 'nexus'] as $channel) {
            $keys = array_column($this->service->forChannel(self::COLONY_ID, $channel)['sources'], 'key');
            $this->assertNotContains('consul', $keys, "{$channel} must have no Konsul source (GDD §12 / F5)");
        }
    }

    public function test_each_source_carries_a_display_label_key_and_value(): void
    {
        $this->setConsulRank(1);
        $advantage = $this->service->forChannel(self::COLONY_ID, 'bar');

        foreach ($advantage['sources'] as $source) {
            $this->assertArrayHasKey('key', $source);
            $this->assertArrayHasKey('label_key', $source);
            $this->assertArrayHasKey('value', $source);
            $this->assertArrayHasKey('percent', $source);
            $this->assertTrue(Lang::has($source['label_key'], 'de'), "label key {$source['label_key']} must exist in lang/de");
        }
    }

    public function test_unavailable_consul_contributes_nothing(): void
    {
        $this->setConsulRank(3, unavailableUntilTick: 99);

        $advantage = $this->service->forChannel(self::COLONY_ID, 'bar');

        $this->assertSame(0, $advantage['total_percent']);
    }

    public function test_consul_rank_matches_bar_service_trader_rank(): void
    {
        $this->setConsulRank(2);

        $this->assertSame(2, $this->service->consulRank(self::COLONY_ID));
        $this->assertSame(2, $this->app->make(BarService::class)->traderRank(self::COLONY_ID));
    }

    public function test_sum_is_additive_never_multiplicative(): void
    {
        $this->setConsulRank(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(5);

        $advantage = $this->service->forChannel(self::COLONY_ID, 'bar');

        $this->assertSame(54, $advantage['total_percent']);
        $multiplicative = (1.30 * 1.12 * 1.12) - 1;
        $this->assertNotEqualsWithDelta($multiplicative, $advantage['total'], 0.01);
    }

    public function test_exclude_sources_drops_them_from_the_sum(): void
    {
        $this->setConsulRank(1);
        $this->setTradingPostLevel(1);

        $advantage = $this->service->forChannel(self::COLONY_ID, 'bar', ['trading_post']);

        $this->assertSame(10, $advantage['total_percent']);
        $this->assertNotContains('trading_post', array_column($advantage['sources'], 'key'));
    }

    public function test_unknown_channel_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->forChannel(self::COLONY_ID, 'black_market');
    }

    // ── Silent guard rail ─────────────────────────────────────────────────────

    public function test_silent_cap_clamps_total_and_sets_capped_flag(): void
    {
        config(['game.bar.trade_terms.silent_cap.bar' => 0.40]);
        $this->setConsulRank(3);
        $this->setTradingPostLevel(1);
        $this->setTradeLevel(5); // raw sum 54 %

        $advantage = $this->service->forChannel(self::COLONY_ID, 'bar');

        $this->assertTrue($advantage['capped']);
        $this->assertSame(40, $advantage['total_percent']);
        $this->assertEqualsWithDelta(0.40, $advantage['total'], 1e-9);
        $this->assertCount(3, $advantage['sources'], 'sources stay listed unchanged; only the total is clamped');
    }

    public function test_default_silent_caps_are_never_reached_by_current_sources(): void
    {
        $this->assertSame(['bar' => 0.60, 'merchant' => 0.60, 'nexus' => 0.25], config('game.bar.trade_terms.silent_cap'));

        $this->setConsulRank(3);
        $this->setTradingPostLevel(3);
        $this->setTradeLevel(5);

        foreach (['bar' => 54, 'merchant' => 24, 'nexus' => 24] as $channel => $max) {
            $advantage = $this->service->forChannel(self::COLONY_ID, $channel);
            $this->assertSame($max, $advantage['total_percent']);
            $this->assertFalse($advantage['capped'], "{$channel} must not hit its silent cap");
        }
    }

    // ── Application: amount (Get side, more goods) ────────────────────────────

    /**
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    public static function amountTable(): array
    {
        // base amount, total percent, expected final amount (round half up)
        return [
            'no advantage' => [10, 0, 10],
            '10 % on 10' => [10, 10, 11],
            '10 % on 5 rounds half up (5.5 -> 6)' => [5, 10, 6],
            '10 % on 3 has no visible effect (3.3 -> 3)' => [3, 10, 3],
            '24 % on 1 has no visible effect' => [1, 24, 1],
            '54 % on 30' => [30, 54, 46],
            '22 % on 25 rounds half up (30.5 -> 31)' => [25, 22, 31],
            '30 % on 20' => [20, 30, 26],
        ];
    }

    /**
     * @dataProvider amountTable
     */
    public function test_apply_to_amount_rounds_half_up(int $base, int $percent, int $expected): void
    {
        $this->assertSame($expected, $this->service->applyToAmount($base, $this->fakeAdvantage($percent)));
    }

    public function test_apply_to_amount_never_returns_less_than_the_base(): void
    {
        for ($base = 1; $base <= 60; $base++) {
            foreach ([0, 8, 10, 12, 20, 24, 30, 54] as $percent) {
                $this->assertGreaterThanOrEqual($base, $this->service->applyToAmount($base, $this->fakeAdvantage($percent)));
            }
        }
    }

    // ── Application: price (Credits at fixed price, discount) ─────────────────

    /**
     * @return array<string, array{0: int, 1: int, 2: int}>
     */
    public static function priceTable(): array
    {
        // base price, total percent, expected final price
        return [
            'no advantage' => [100, 0, 100],
            '12 % on 100' => [100, 12, 88],
            '8 % on 495' => [495, 8, 455],
            '24 % on 800' => [800, 24, 608],
            'small price 12 % on 3 rounds back up (2.64 -> 3)' => [3, 12, 3],
            'never below 1' => [1, 24, 1],
            'a free item stays free' => [0, 12, 0],
        ];
    }

    /**
     * @dataProvider priceTable
     */
    public function test_apply_to_price_rounds_to_nearest_and_never_below_one(int $base, int $percent, int $expected): void
    {
        $this->assertSame($expected, $this->service->applyToPrice($base, $this->fakeAdvantage($percent)));
    }

    /** Minimal advantage array in the shape forChannel() returns. */
    private function fakeAdvantage(int $percent): array
    {
        return [
            'channel' => 'bar',
            'sources' => [],
            'total' => $percent / 100,
            'total_percent' => $percent,
            'capped' => false,
        ];
    }
}
