<?php

namespace Tests\Feature;

/**
 * MerchantService feature tests.
 *
 * Covered scenarios:
 *
 *  shouldSpawn
 *    - test_should_spawn_returns_false_before_first_appearance_min
 *    - test_should_spawn_returns_false_when_active_visit_exists
 *    - test_should_spawn_returns_false_when_interval_min_not_elapsed
 *    - test_should_spawn_returns_true_when_conditions_met
 *
 *  getActiveVisit
 *    - test_get_active_visit_returns_visit_within_range
 *    - test_get_active_visit_returns_null_when_no_visit
 *    - test_get_active_visit_returns_null_when_tick_outside_range
 *
 *  spawnVisit
 *    - test_spawn_visit_creates_one_merchant_visits_row
 *    - test_spawn_visit_creates_correct_number_of_items
 *    - test_spawn_visit_items_have_required_fields
 *
 *  buyItem
 *    - test_buy_item_returns_false_when_item_not_found
 *    - test_buy_item_returns_false_when_already_sold
 *    - test_buy_item_returns_false_when_visit_no_longer_active
 *    - test_buy_item_returns_false_when_not_enough_credits
 *    - test_buy_item_success_deducts_credits_and_marks_sold
 *    - test_buy_item_repair_kit_increases_status_points
 *    - test_buy_item_repair_kit_caps_status_points_at_max
 *    - test_buy_item_trust_boost_increments_colony_resource
 *    - test_buy_item_information_sets_all_tiles_explored
 *
 *  getItemsForVisit
 *    - test_get_items_for_visit_returns_all_items
 *
 *  markVisited
 *    - test_mark_visited_sets_was_visited_true
 */

use App\Services\MerchantService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MerchantServiceTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const COLONY_ID = 1;   // Springfield — user_id=3 (Bart)

    private const USER_ID = 3;   // Bart

    // Building from TestSeeder: colony_id=1, building_id=25, max_status_points=20
    private const BUILDING_ID = 25;

    private const INSTANCE_ID = 1;

    private const MAX_SP = 20;

    private const TRUST_RESOURCE_ID = 12;

    private MerchantService $service;

    // ── Merchant config used across all tests ─────────────────────────────────

    private static function merchantConfig(): array
    {
        return [
            'first_appearance_min' => 15,
            'first_appearance_max' => 20,
            'interval_min' => 10,
            'interval_max' => 15,
            'duration_ticks' => 2,
            'items_count' => 3,
            'items' => [
                'repair_kit' => ['label' => 'Reparatur-Kit',  'cost' => 100, 'sp_amount' => 30],
                'trust_boost' => ['label' => 'Vertrauensschub', 'cost' => 150, 'trust_amount' => 15],
                'information' => ['label' => 'Systemkarte',    'cost' => 200],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.merchant' => self::merchantConfig()]);
        $this->service = $this->app->make(MerchantService::class);

        // Merchant requires bar built (building_id=52, level>0). Testdata seeds it at lv=0.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 52)
            ->update(['level' => 1]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function insertVisit(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'tick_start' => 20,
            'tick_end' => 21,
            'was_visited' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('merchant_visits')->insertGetId(array_merge($defaults, $overrides));
    }

    private function insertItem(int $visitId, array $overrides = []): int
    {
        $defaults = [
            'visit_id' => $visitId,
            'item_type' => 'repair_kit',
            'label' => 'Reparatur-Kit',
            'cost_credits' => 100,
            'payload' => json_encode(['sp_amount' => 30]),
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

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function getColonyResource(int $resourceId): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', $resourceId)
            ->value('amount');
    }

    // ── shouldSpawn ───────────────────────────────────────────────────────────

    public function test_should_spawn_returns_false_before_first_appearance_min(): void
    {
        // Tick 14 is below first_appearance_min=15 → must return false unconditionally.
        $result = $this->service->shouldSpawn(self::COLONY_ID, 14);

        $this->assertFalse($result, 'shouldSpawn must return false when tick < first_appearance_min');
    }

    public function test_should_spawn_returns_false_when_active_visit_exists(): void
    {
        // tick_end=30 >= currentTick=20 → active visit exists.
        $this->insertVisit(['tick_start' => 20, 'tick_end' => 30]);

        $result = $this->service->shouldSpawn(self::COLONY_ID, 20);

        $this->assertFalse($result, 'shouldSpawn must return false when an active visit exists');
    }

    public function test_should_spawn_returns_false_when_interval_min_not_elapsed(): void
    {
        // Last visit ended at tick_end=30. interval_min=10 means next spawn earliest at tick=40.
        // We check tick=39 — not yet past the interval.
        $this->insertVisit(['tick_start' => 28, 'tick_end' => 30]);

        $result = $this->service->shouldSpawn(self::COLONY_ID, 39);

        $this->assertFalse($result, 'shouldSpawn must return false when interval_min has not elapsed since last visit');
    }

    public function test_should_spawn_returns_true_when_conditions_met(): void
    {
        // No prior visits, tick=17 >= first_appearance_min=15.
        // Deterministic seed check for colonyId=1, tick=17:
        //   seed = 1*1664525 + 17*1013904223 = 17238036316
        //   hash = 58167132, frac ≈ 0.0271 < 0.08 (=1/12.5) → true
        $result = $this->service->shouldSpawn(self::COLONY_ID, 17);

        $this->assertTrue($result, 'shouldSpawn must return true when no prior visits and tick passes the random threshold');
    }

    // ── getActiveVisit ────────────────────────────────────────────────────────

    public function test_get_active_visit_returns_visit_within_range(): void
    {
        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);

        $visit = $this->service->getActiveVisit(self::COLONY_ID, 20);

        $this->assertNotNull($visit, 'getActiveVisit must return the visit when tick is within [tick_start, tick_end]');
        $this->assertEquals($visitId, $visit->id);
    }

    public function test_get_active_visit_returns_null_when_no_visit(): void
    {
        $visit = $this->service->getActiveVisit(self::COLONY_ID, 50);

        $this->assertNull($visit, 'getActiveVisit must return null when no visit row exists for the colony');
    }

    public function test_get_active_visit_returns_null_when_tick_outside_range(): void
    {
        // Visit covers ticks 20–21; we query at tick=25 → should return null.
        $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);

        $visit = $this->service->getActiveVisit(self::COLONY_ID, 25);

        $this->assertNull($visit, 'getActiveVisit must return null when tick is past tick_end');
    }

    // ── spawnVisit ────────────────────────────────────────────────────────────

    public function test_spawn_visit_creates_one_merchant_visits_row(): void
    {
        $this->service->spawnVisit(self::COLONY_ID, 20);

        $count = DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        $this->assertEquals(1, $count, 'spawnVisit must create exactly one merchant_visits row');

        $visit = DB::table('merchant_visits')->where('colony_id', self::COLONY_ID)->first();
        $this->assertEquals(20, $visit->tick_start);
        // duration_ticks=2 → tick_end = 20 + 2 - 1 = 21
        $this->assertEquals(21, $visit->tick_end);
    }

    public function test_spawn_visit_creates_correct_number_of_items(): void
    {
        // items_count=3 in config, items pool has exactly 3 types → expect 3 items.
        $this->service->spawnVisit(self::COLONY_ID, 20);

        $visit = DB::table('merchant_visits')->where('colony_id', self::COLONY_ID)->first();
        $itemCount = DB::table('merchant_items')
            ->where('visit_id', $visit->id)
            ->count();

        $this->assertEquals(3, $itemCount, 'spawnVisit must create exactly items_count merchant_items rows');
    }

    public function test_spawn_visit_items_have_required_fields(): void
    {
        $this->service->spawnVisit(self::COLONY_ID, 20);

        $visit = DB::table('merchant_visits')->where('colony_id', self::COLONY_ID)->first();
        $items = DB::table('merchant_items')->where('visit_id', $visit->id)->get();

        foreach ($items as $item) {
            $this->assertNotEmpty($item->item_type, 'Each item must have a non-empty item_type');
            $this->assertNotEmpty($item->label, 'Each item must have a non-empty label');
            $this->assertGreaterThan(0, $item->cost_credits, 'Each item must have a positive cost_credits');
        }
    }

    // ── buyItem ───────────────────────────────────────────────────────────────

    public function test_buy_item_returns_false_when_item_not_found(): void
    {
        $this->mockTick(20);

        $result = $this->service->buyItem(99999, self::COLONY_ID, self::USER_ID);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_buy_item_returns_false_when_already_sold(): void
    {
        $this->mockTick(20);
        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, ['sold' => true]);

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertFalse($result['ok'], 'buyItem must return ok=false when item is already sold');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_buy_item_returns_false_when_visit_no_longer_active(): void
    {
        // Visit ended at tick=21, but current tick is 30 → expired.
        $this->mockTick(30);
        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId);
        $this->setCredits(10000);

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertFalse($result['ok'], 'buyItem must return ok=false when the visit is no longer active');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_buy_item_returns_false_when_not_enough_credits(): void
    {
        $this->mockTick(20);
        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, ['cost_credits' => 500]);
        $this->setCredits(50); // less than 500

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertFalse($result['ok'], 'buyItem must return ok=false when credits are insufficient');
        $this->assertArrayHasKey('error', $result);
    }

    public function test_buy_item_success_deducts_credits_and_marks_sold(): void
    {
        $this->mockTick(20);
        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'trust_boost',
            'payload' => json_encode(['trust_amount' => 15]),
            'cost_credits' => 100,
        ]);
        $this->setCredits(300);
        $this->setColonyResource(self::TRUST_RESOURCE_ID, 50);

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertTrue($result['ok'], 'buyItem must return ok=true on a valid purchase');
        $this->assertEquals(200, $this->getCredits(), 'buyItem must deduct cost_credits from user_resources');

        $sold = (bool) DB::table('merchant_items')->where('id', $itemId)->value('sold');
        $this->assertTrue($sold, 'buyItem must mark the item as sold=true');

        $this->assertArrayHasKey('credits', $result);
        $this->assertEquals(200, $result['credits']);
    }

    public function test_buy_item_repair_kit_increases_status_points(): void
    {
        $this->mockTick(20);

        // Max out all colony buildings first so only the target has a low SP ratio.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->update(['status_points' => self::MAX_SP]);

        // Now give the target building the lowest SP → it will be selected for repair.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BUILDING_ID)
            ->where('instance_id', self::INSTANCE_ID)
            ->update(['status_points' => 5, 'level' => 1]);

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'repair_kit',
            'payload' => json_encode(['sp_amount' => 10]),
            'cost_credits' => 100,
        ]);
        $this->setCredits(500);

        $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $spAfter = (int) DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BUILDING_ID)
            ->where('instance_id', self::INSTANCE_ID)
            ->value('status_points');

        $this->assertGreaterThan(5, $spAfter, 'repair_kit effect must increase the status_points of the target building');
    }

    public function test_buy_item_repair_kit_caps_status_points_at_max(): void
    {
        $this->mockTick(20);

        // max_status_points=20 for building_id=25 (see TestSeeder + buildings table).
        // Max out all buildings first, then set the target just below max.
        // This ensures the target has the lowest relative SP and gets selected.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->update(['status_points' => self::MAX_SP]);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BUILDING_ID)
            ->where('instance_id', self::INSTANCE_ID)
            ->update(['status_points' => 15, 'level' => 1]);

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'repair_kit',
            'payload' => json_encode(['sp_amount' => 30]),
            'cost_credits' => 100,
        ]);
        $this->setCredits(500);

        $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $spAfter = (int) DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BUILDING_ID)
            ->where('instance_id', self::INSTANCE_ID)
            ->value('status_points');

        $this->assertEquals(self::MAX_SP, $spAfter, 'repair_kit must cap status_points at max_status_points');
    }

    public function test_buy_item_trust_boost_increments_colony_resource(): void
    {
        $this->mockTick(20);

        $initialTrust = 100;
        $trustAmount = 15;
        $this->setColonyResource(self::TRUST_RESOURCE_ID, $initialTrust);

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'trust_boost',
            'payload' => json_encode(['trust_amount' => $trustAmount]),
            'cost_credits' => 150,
        ]);
        $this->setCredits(500);

        $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $trustAfter = $this->getColonyResource(self::TRUST_RESOURCE_ID);

        $this->assertEquals(
            $initialTrust + $trustAmount,
            $trustAfter,
            'trust_boost effect must increment colony_resources amount for resource_id=12'
        );
    }

    public function test_buy_item_information_sets_all_tiles_explored(): void
    {
        $this->mockTick(20);

        // Insert two unexplored tiles for the colony.
        DB::table('colony_tiles')->insert([
            [
                'colony_id' => self::COLONY_ID,
                'q' => 0,
                'r' => 0,
                'ring' => 0,
                'tile_type' => 'plain',
                'is_explored' => false,
                'is_deep_scanned' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'colony_id' => self::COLONY_ID,
                'q' => 1,
                'r' => 0,
                'ring' => 1,
                'tile_type' => 'plain',
                'is_explored' => false,
                'is_deep_scanned' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'information',
            'payload' => null,
            'cost_credits' => 200,
        ]);
        $this->setCredits(500);

        $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $unexploredCount = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)
            ->where('is_explored', false)
            ->count();

        $this->assertEquals(
            0,
            $unexploredCount,
            'information effect must set is_explored=true on all colony tiles'
        );
    }

    // ── getItemsForVisit ──────────────────────────────────────────────────────

    public function test_get_items_for_visit_returns_all_items(): void
    {
        $visitId = $this->insertVisit();
        $this->insertItem($visitId, ['item_type' => 'repair_kit']);
        $this->insertItem($visitId, ['item_type' => 'trust_boost', 'cost_credits' => 150]);
        $this->insertItem($visitId, ['item_type' => 'information', 'cost_credits' => 200]);

        $items = $this->service->getItemsForVisit($visitId);

        $this->assertCount(3, $items, 'getItemsForVisit must return all items linked to the given visit_id');
        $types = $items->pluck('item_type')->sort()->values()->all();
        $this->assertContains('repair_kit', $types);
        $this->assertContains('trust_boost', $types);
        $this->assertContains('information', $types);
    }

    // ── markVisited ───────────────────────────────────────────────────────────

    public function test_mark_visited_sets_was_visited_true(): void
    {
        $visitId = $this->insertVisit(['was_visited' => false]);

        $this->service->markVisited($visitId, self::COLONY_ID);

        $wasVisited = (bool) DB::table('merchant_visits')
            ->where('id', $visitId)
            ->value('was_visited');

        $this->assertTrue($wasVisited, 'markVisited must set was_visited=true on the visit');
    }

    public function test_mark_visited_does_not_affect_other_colony_visits(): void
    {
        // Insert a visit for colony 2 (Shelbyville) — must not be touched.
        $foreignVisitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => 2,
            'tick_start' => 20,
            'tick_end' => 21,
            'was_visited' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ownVisitId = $this->insertVisit(['was_visited' => false]);

        // Mark own visit as visited using colony 1.
        $this->service->markVisited($ownVisitId, self::COLONY_ID);

        $foreignStillFalse = ! (bool) DB::table('merchant_visits')
            ->where('id', $foreignVisitId)
            ->value('was_visited');

        $this->assertTrue($foreignStillFalse, 'markVisited must not affect visits belonging to other colonies');
    }
}
