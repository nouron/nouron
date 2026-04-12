<?php

namespace Tests\Feature\Trade;

use App\Services\TradeGateway;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Test data (Simpsons fixture via TestSeeder):
 *
 * trade_resources (5 rows):
 *   (colony_id=2, direction=0, resource_id=3,  amount=11,  price=11)
 *   (colony_id=2, direction=1, resource_id=5,  amount=123, price=32)
 *   (colony_id=2, direction=0, resource_id=6,  amount=45,  price=45)
 *   (colony_id=1, direction=0, resource_id=10, amount=4,   price=3)
 *   (colony_id=1, direction=0, resource_id=8,  amount=100, price=50)
 *
 * trade_knowledge (2 rows):
 *   (colony_id=1, direction=1, research_id=90, amount=5,   price=200)
 *   (colony_id=2, direction=0, research_id=90, amount=3,   price=200)
 *
 * Colony 1 (Springfield) belongs to user_id=3 (Bart).
 */
class TradeGatewayTest extends TestCase
{
    use RefreshDatabase;

    private TradeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        // Bypass AP checks — test colonies have no traders (no economy AP).
        config(['game.dev_mode' => true]);
        $this->gateway = $this->app->make(TradeGateway::class);
    }

    // ── getResources ──────────────────────────────────────────────────────────

    public function test_get_resources_returns_all_offers(): void
    {
        $offers = $this->gateway->getResources();

        $this->assertInstanceOf(Collection::class, $offers);
        $this->assertCount(5, $offers);
        $this->assertNotNull($offers->first()->resource_id);
        $this->assertNotNull($offers->first()->amount);
    }

    public function test_get_resources_filters_by_colony_id(): void
    {
        $offers = $this->gateway->getResources(['colony_id' => 1]);

        $this->assertInstanceOf(Collection::class, $offers);
        $this->assertCount(2, $offers);
        $this->assertTrue($offers->every(fn($o) => $o->colony_id === 1));
    }

    // ── getResearches ─────────────────────────────────────────────────────────

    public function test_get_researches_returns_all_offers(): void
    {
        $offers = $this->gateway->getResearches();

        $this->assertInstanceOf(Collection::class, $offers);
        $this->assertCount(2, $offers);
    }

    public function test_get_researches_filters_by_colony_id(): void
    {
        $offers = $this->gateway->getResearches(['colony_id' => 2]);

        $this->assertInstanceOf(Collection::class, $offers);
        $this->assertCount(1, $offers);
        $this->assertTrue($offers->every(fn($o) => $o->colony_id === 2));
    }

    // ── addResourceOffer — failure cases ──────────────────────────────────────

    public function test_add_resource_offer_fails_without_user_id(): void
    {
        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 10,
        ]);

        $this->assertFalse($result);
    }

    public function test_add_resource_offer_fails_for_non_owner(): void
    {
        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 10,
            'user_id'     => 99,  // does not own colony 1
        ]);

        $this->assertFalse($result);
    }

    // ── addResourceOffer — success cases ──────────────────────────────────────

    public function test_add_resource_offer_creates_new(): void
    {
        // colony 1 + direction 1 + resource 3 does not exist in seed data
        $countBefore = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->count();
        $this->assertSame(0, $countBefore);

        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 10,
            'user_id'     => 3,  // Bart owns colony 1
        ]);

        $this->assertTrue($result);

        $countAfter = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->count();
        $this->assertSame(1, $countAfter);

        $row = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->first();
        $this->assertSame(100, (int) $row->amount);
    }

    public function test_add_resource_offer_updates_existing(): void
    {
        // Insert initial row
        $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 100,
            'price'       => 10,
            'user_id'     => 3,
        ]);

        // Update it with a new amount
        $result = $this->gateway->addResourceOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'resource_id' => 3,
            'amount'      => 500,
            'price'       => 10,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $count = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->count();
        $this->assertSame(1, $count);

        $row = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->first();
        $this->assertSame(500, (int) $row->amount);
    }

    // ── addResearchOffer — failure cases ──────────────────────────────────────

    public function test_add_research_offer_fails_without_user_id(): void
    {
        $result = $this->gateway->addResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 91,
            'amount'      => 2,
            'price'       => 5,
        ]);

        $this->assertFalse($result);
    }

    public function test_add_research_offer_fails_for_non_owner(): void
    {
        $result = $this->gateway->addResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 91,
            'amount'      => 2,
            'price'       => 5,
            'user_id'     => 99,
        ]);

        $this->assertFalse($result);
    }

    // ── addResearchOffer — success cases ──────────────────────────────────────

    public function test_add_research_offer_creates_new(): void
    {
        // colony 1 + direction 1 + research 91 does not exist in seed data
        // (seed has colony 1, direction 1, research 90 — and colony 2 has research 90)
        $countBefore = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 91)
            ->count();
        $this->assertSame(0, $countBefore);

        $result = $this->gateway->addResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 91,
            'amount'      => 2,
            'price'       => 5,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $countAfter = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 91)
            ->count();
        $this->assertSame(1, $countAfter);

        $row = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 91)
            ->first();
        $this->assertSame(2, (int) $row->amount);
    }

    public function test_add_research_offer_updates_existing(): void
    {
        // Insert initial row
        $this->gateway->addResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 91,
            'amount'      => 2,
            'price'       => 5,
            'user_id'     => 3,
        ]);

        // Update it
        $result = $this->gateway->addResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 91,
            'amount'      => 999,
            'price'       => 5,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $count = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 91)
            ->count();
        $this->assertSame(1, $count);

        $row = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 91)
            ->first();
        $this->assertSame(999, (int) $row->amount);
    }

    // ── removeResourceOffer — failure cases ───────────────────────────────────

    public function test_remove_resource_offer_fails_without_user_id(): void
    {
        $result = $this->gateway->removeResourceOffer([
            'colony_id'   => 1,
            'direction'   => 0,
            'resource_id' => 8,
        ]);

        $this->assertFalse($result);
    }

    public function test_remove_resource_offer_fails_for_non_owner(): void
    {
        $result = $this->gateway->removeResourceOffer([
            'colony_id'   => 1,
            'direction'   => 0,
            'resource_id' => 8,
            'user_id'     => 99,
        ]);

        $this->assertFalse($result);
    }

    // ── removeResourceOffer — success case ────────────────────────────────────

    public function test_remove_resource_offer_succeeds(): void
    {
        // Verify seed data is present: colony 1, buy (direction=0), lho (resource_id=8)
        $countBefore = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 8)
            ->count();
        $this->assertSame(1, $countBefore);

        $result = $this->gateway->removeResourceOffer([
            'colony_id'   => 1,
            'direction'   => 0,
            'resource_id' => 8,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $countAfter = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 8)
            ->count();
        $this->assertSame(0, $countAfter);
    }

    // ── removeResearchOffer — failure cases ───────────────────────────────────

    public function test_remove_research_offer_fails_without_user_id(): void
    {
        $result = $this->gateway->removeResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 90,
        ]);

        $this->assertFalse($result);
    }

    public function test_remove_research_offer_fails_for_non_owner(): void
    {
        $result = $this->gateway->removeResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 90,
            'user_id'     => 99,
        ]);

        $this->assertFalse($result);
    }

    // ── removeResearchOffer — success case ────────────────────────────────────

    public function test_remove_research_offer_succeeds(): void
    {
        // Verify seed data: colony 1, sell (direction=1), research_id=90 (construction)
        $countBefore = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 90)
            ->count();
        $this->assertSame(1, $countBefore);

        $result = $this->gateway->removeResearchOffer([
            'colony_id'   => 1,
            'direction'   => 1,
            'research_id' => 90,
            'user_id'     => 3,
        ]);

        $this->assertTrue($result);

        $countAfter = DB::table('trade_knowledge')
            ->where('colony_id', 1)->where('direction', 1)->where('research_id', 90)
            ->count();
        $this->assertSame(0, $countAfter);
    }
}
