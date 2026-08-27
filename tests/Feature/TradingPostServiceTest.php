<?php

namespace Tests\Feature;

use App\Services\TradingPostService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TradingPostService::discountFor() — Kanal-Rabatt-Schwellen (Design-Spec
 * 2026-08-23, Abschnitt "Handelsposten"): Stufe 1 = Cantina, Stufe 2 = +Reisender
 * Händler, Stufe 3 = +Nexus/Corporate Contact. Kumulativ, nicht exklusiv.
 */
class TradingPostServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TRADING_POST_ID = 55;

    private TradingPostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(TradingPostService::class);
    }

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::TRADING_POST_ID)->delete();

        if ($level !== null) {
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

    public function test_no_trading_post_gives_zero_discount_on_every_channel(): void
    {
        $this->setTradingPostLevel(null);

        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_1_unlocks_only_bar_channel(): void
    {
        $this->setTradingPostLevel(1);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_2_unlocks_bar_and_merchant_cumulatively(): void
    {
        $this->setTradingPostLevel(2);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_3_unlocks_all_three_channels(): void
    {
        $this->setTradingPostLevel(3);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_unknown_channel_returns_zero_not_an_error(): void
    {
        $this->setTradingPostLevel(3);

        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'not_a_real_channel'));
    }
}
