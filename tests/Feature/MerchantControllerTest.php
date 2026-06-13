<?php

namespace Tests\Feature;

/**
 * MerchantController feature tests.
 *
 * Covered scenarios:
 *
 *  AUTH GUARD
 *    - test_buy_requires_auth
 *    - test_open_requires_auth
 *
 *  BUY (POST /colony/merchant/buy/{itemId})
 *    - test_buy_returns_ok_true_for_valid_purchase
 *    - test_buy_returns_422_when_not_enough_credits
 *    - test_buy_returns_422_when_item_already_sold
 *    - test_buy_returns_422_when_item_not_found
 *    - test_buy_returns_422_when_visit_expired
 *
 *  OPEN (POST /colony/merchant/visit/{visitId}/open)
 *    - test_open_returns_ok_true_for_authenticated_user
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MerchantControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const USER_ID = 3;   // Bart

    private const COLONY_ID = 1;   // Springfield (user_id=3)

    // ── Setup ─────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config(['game.merchant' => [
            'first_appearance_min' => 15,
            'first_appearance_max' => 20,
            'interval_min' => 10,
            'interval_max' => 15,
            'duration_ticks' => 2,
            'items_count' => 3,
            'items' => [
                'repair_kit' => ['label' => 'Reparatur-Kit',   'cost' => 100, 'sp_amount' => 30],
                'trust_boost' => ['label' => 'Vertrauensschub', 'cost' => 150, 'trust_amount' => 15],
                'information' => ['label' => 'Systemkarte',     'cost' => 200],
            ],
        ]]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bart(): User
    {
        return User::find(self::USER_ID);
    }

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function insertActiveVisit(int $tickStart = 20, int $tickEnd = 21): int
    {
        return DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'tick_start' => $tickStart,
            'tick_end' => $tickEnd,
            'was_visited' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertItem(int $visitId, array $overrides = []): int
    {
        $defaults = [
            'visit_id' => $visitId,
            'item_type' => 'trust_boost',
            'label' => 'Vertrauensschub',
            'cost_credits' => 150,
            'payload' => json_encode(['trust_amount' => 15]),
            'sold' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('merchant_items')->insertGetId(array_merge($defaults, $overrides));
    }

    private function setCredits(int $amount): void
    {
        DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->update(['credits' => $amount]);
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_buy_requires_auth(): void
    {
        $response = $this->postJson(route('colony.merchant.buy', ['itemId' => 1]));

        // JSON clients receive 401, not a redirect.
        $response->assertUnauthorized();
    }

    public function test_open_requires_auth(): void
    {
        $response = $this->postJson(route('colony.merchant.open', ['visitId' => 1]));

        $response->assertUnauthorized();
    }

    // ── BUY ───────────────────────────────────────────────────────────────────

    public function test_buy_returns_ok_true_for_valid_purchase(): void
    {
        $this->mockTick(20);

        $visitId = $this->insertActiveVisit(20, 21);
        $itemId = $this->insertItem($visitId, ['cost_credits' => 150]);
        $this->setCredits(500);
        $this->setColonyResource(12, 50);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.buy', ['itemId' => $itemId]));

        $response->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_buy_returns_422_when_not_enough_credits(): void
    {
        $this->mockTick(20);

        $visitId = $this->insertActiveVisit(20, 21);
        $itemId = $this->insertItem($visitId, ['cost_credits' => 500]);
        $this->setCredits(10); // insufficient

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.buy', ['itemId' => $itemId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_buy_returns_422_when_item_already_sold(): void
    {
        $this->mockTick(20);

        $visitId = $this->insertActiveVisit(20, 21);
        $itemId = $this->insertItem($visitId, ['sold' => true]);
        $this->setCredits(500);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.buy', ['itemId' => $itemId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_buy_returns_422_when_item_not_found(): void
    {
        $this->mockTick(20);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.buy', ['itemId' => 99999]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_buy_returns_422_when_visit_expired(): void
    {
        // Current tick=30 but visit ended at tick=21 → expired.
        $this->mockTick(30);

        $visitId = $this->insertActiveVisit(20, 21);
        $itemId = $this->insertItem($visitId);
        $this->setCredits(500);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.buy', ['itemId' => $itemId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    // ── OPEN ─────────────────────────────────────────────────────────────────

    public function test_open_returns_ok_true_for_authenticated_user(): void
    {
        $visitId = $this->insertActiveVisit(20, 21);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.merchant.open', ['visitId' => $visitId]));

        $response->assertOk()
            ->assertJson(['ok' => true]);

        // Verify the was_visited flag was actually set in the DB.
        $wasVisited = (bool) DB::table('merchant_visits')
            ->where('id', $visitId)
            ->value('was_visited');

        $this->assertTrue($wasVisited, 'POST open must persist was_visited=true on the visit');
    }
}
