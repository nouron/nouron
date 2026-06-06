<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 11 — Traveling Merchant spawn.
 *
 * MerchantService::shouldSpawn() rules (checked each tick per player colony):
 *   1. Colony must have a Bar (building_id=52, level > 0)
 *   2. tick >= first_appearance_min (15)
 *   3. No active or future visit exists
 *   4. If a previous visit existed: current_tick - last_tick_end >= interval_min (10)
 *   5. Random chance: ~1/interval_avg per tick (deterministic seed: colony*1664525 + tick*1013904223)
 *
 * spawnVisit() creates a merchant_visits row (tick_start=tick, tick_end=tick+duration-1=tick+1)
 * and merchant_items rows (3 items picked from config pool).
 *
 * Covered scenarios:
 *  Happy path:
 *  - Merchant spawn creates merchant_visits row and merchant_items rows
 *  - A merchant.visit event is created in colony_log for the colony owner
 *  - Spawned visit has correct tick_start and tick_end
 *
 *  Edge cases:
 *  - No spawn before first_appearance_min tick
 *  - No spawn without a Bar (building_id=52, level > 0)
 *  - No spawn while a visit is currently active
 *  - No spawn within interval_min ticks after last visit ended
 *  - NPC colony (user_id=null) is skipped entirely
 *
 *  Adversarial:
 *  - Merchant does not spawn twice in the same tick for the same colony
 *
 * Strategy: the random check (step 5) is deterministic per seed. We find a tick
 * number where the seed passes for colony 1 and use it for happy-path tests.
 * For negative tests we block via the guard conditions (rules 1–4) which are
 * deterministic (no randomness involved).
 *
 * Fixture summary (TestSeeder):
 *   Colony 1 (Springfield), user_id=3 (Bart) — bar (building_id=52) level=0
 *   Colony 2 (Shelbyville), user_id=null     — NPC colony
 *
 * Uses tick numbers 11600–11649.
 */
class GameTickMerchantTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID    = 1;
    private const USER_ID      = 3;
    private const BAR_ID       = 52;
    private const FIRST_MIN    = 15;   // config game.merchant.first_appearance_min
    private const INTERVAL_MIN = 10;   // config game.merchant.interval_min
    private const DURATION     = 2;    // config game.merchant.duration_ticks

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Remove any existing merchant state so tests start clean
        DB::table('merchant_visits')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('merchant_items')->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Enable the Bar for colony 1 so the merchant can spawn. */
    private function enableBar(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::BAR_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    /** Find a tick >= first_appearance_min where the merchant seed passes for colony 1. */
    private function findSpawnableTick(int $startFrom = self::FIRST_MIN): ?int
    {
        $colonyId    = self::COLONY_ID;
        $intervalMin = (int) config('game.merchant.interval_min', 10);
        $intervalMax = (int) config('game.merchant.interval_max', 15);
        $intervalAvg = ($intervalMin + $intervalMax) / 2.0;
        $threshold   = 1.0 / $intervalAvg;

        for ($tick = $startFrom; $tick < $startFrom + 200; $tick++) {
            $seed = $colonyId * 1664525 + $tick * 1013904223;
            $hash = abs($seed & 0x7FFFFFFF);
            $frac = $hash / 0x7FFFFFFF;
            if ($frac < $threshold) {
                return $tick;
            }
        }
        return null;
    }

    private function getVisit(): ?object
    {
        return DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->orderBy('id', 'desc')
            ->first();
    }

    private function getItemCountForLatestVisit(): int
    {
        $visit = $this->getVisit();
        if (!$visit) {
            return 0;
        }
        return (int) DB::table('merchant_items')->where('visit_id', $visit->id)->count();
    }

    // ── Happy path ─────────────────────────────────────────────────────────────

    /**
     * When all conditions are met, the merchant spawns:
     *   - merchant_visits row is created
     *   - merchant_items rows are created (up to items_count)
     *   - merchant.visit event is created in colony_log
     */
    public function test_merchant_spawns_when_all_conditions_met(): void
    {
        $this->enableBar();

        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick within the search range — adjust search bounds.');
        }

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $visit = $this->getVisit();
        $this->assertNotNull($visit, 'merchant_visits row must be created when merchant spawns');
    }

    /**
     * The spawned visit must have correct tick_start and tick_end.
     * tick_end = tick_start + duration_ticks - 1 = tick_start + 1 (duration=2).
     */
    public function test_merchant_visit_has_correct_tick_range(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $visit = $this->getVisit();
        $this->assertNotNull($visit);
        $this->assertEquals($spawnTick, (int) $visit->tick_start, 'tick_start must equal the spawn tick');
        $duration = (int) config('game.merchant.duration_ticks', 2);
        $this->assertEquals($spawnTick + $duration - 1, (int) $visit->tick_end,
            'tick_end must equal tick_start + duration - 1');
    }

    /**
     * The spawn creates exactly items_count merchant_items rows.
     */
    public function test_merchant_visit_creates_correct_number_of_items(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $itemCount = $this->getItemCountForLatestVisit();
        $expectedCount = (int) config('game.merchant.items_count', 3);
        $this->assertEquals($expectedCount, $itemCount,
            "Merchant visit must create exactly {$expectedCount} items");
    }

    /**
     * A merchant.visit event must be created in colony_log for the colony owner.
     */
    public function test_merchant_visit_creates_innn_event(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $event = DB::table('colony_log')
            ->where('user', self::USER_ID)
            ->where('event', 'merchant.visit')
            ->where('tick', $spawnTick)
            ->first();

        $this->assertNotNull($event, 'merchant.visit event must be created in colony_log on spawn');
    }

    // ── Edge cases ─────────────────────────────────────────────────────────────

    /**
     * Merchant must not spawn before first_appearance_min (tick 15).
     */
    public function test_merchant_does_not_spawn_before_first_appearance_min(): void
    {
        $this->enableBar();

        $earlyTick = self::FIRST_MIN - 1; // tick 14
        Artisan::call('game:tick', ['--tick' => $earlyTick]);

        $visit = $this->getVisit();
        $this->assertNull($visit, 'Merchant must not spawn before first_appearance_min tick');
    }

    /**
     * Merchant must not spawn when the colony has no Bar (building_id=52, level=0).
     */
    public function test_merchant_does_not_spawn_without_bar(): void
    {
        // Ensure bar level=0 (seeded default)
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::BAR_ID)
            ->update(['level' => 0]);

        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $visit = $this->getVisit();
        $this->assertNull($visit, 'Merchant must not spawn when colony has no Bar');
    }

    /**
     * Merchant must not spawn while a visit is currently active.
     */
    public function test_merchant_does_not_spawn_during_active_visit(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        // Insert a visit that is currently active at spawnTick
        DB::table('merchant_visits')->insert([
            'colony_id'   => self::COLONY_ID,
            'tick_start'  => $spawnTick - 1,
            'tick_end'    => $spawnTick + 1, // active at spawnTick
            'was_visited' => false,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        // Must still have exactly 1 visit (the pre-inserted one)
        $visitCount = DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        $this->assertEquals(1, $visitCount,
            'Merchant must not spawn a second visit while one is already active');
    }

    /**
     * Merchant must not spawn within interval_min ticks after the last visit ended.
     */
    public function test_merchant_does_not_spawn_within_interval_min_after_last_visit(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        // Insert a recently-ended visit (ended 1 tick before spawnTick → gap < interval_min)
        $lastEnd = $spawnTick - 1; // gap = 1 tick, but interval_min = 10
        DB::table('merchant_visits')->insert([
            'colony_id'   => self::COLONY_ID,
            'tick_start'  => $lastEnd - 1,
            'tick_end'    => $lastEnd,
            'was_visited' => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $visitCount = DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        $this->assertEquals(1, $visitCount,
            'Merchant must not spawn within interval_min ticks after last visit ended');
    }

    /**
     * Colony with user_id=NULL is skipped — no merchant spawn attempted.
     * GameTick::processMerchantSpawn() only iterates Colony::whereNotNull('user_id').
     *
     * We insert a synthetic colony with user_id=NULL and verify it never receives
     * a merchant visit even when a player colony (colony 1) receives one in the same tick.
     */
    public function test_null_user_colony_is_skipped_by_merchant_spawn(): void
    {
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        // Insert a synthetic NPC colony (user_id=NULL) into the test system
        $npcColonyId = DB::table('glx_colonies')->insertGetId([
            'name'             => 'NPC Colony Test',
            'system_object_id' => 1,
            'spot'             => 99,
            'user_id'          => null,
            'since_tick'       => 0,
            'is_primary'       => 0,
        ]);

        // Enable bar on the NPC colony so it would be eligible if not skipped
        DB::table('colony_buildings')->insert([
            'colony_id'     => $npcColonyId,
            'building_id'   => self::BAR_ID,
            'instance_id'   => 1,
            'level'         => 1,
            'status_points' => 20,
            'ap_spend'      => 0,
        ]);

        // Enable bar on colony 1 to ensure at least one player colony can spawn
        $this->enableBar();

        Artisan::call('game:tick', ['--tick' => $spawnTick]);

        $npcVisit = DB::table('merchant_visits')
            ->where('colony_id', $npcColonyId)
            ->exists();

        $this->assertFalse($npcVisit, 'Colony with user_id=NULL must never receive a merchant visit');
    }

    // ── Adversarial ────────────────────────────────────────────────────────────

    /**
     * Merchant spawns at most once per colony per tick.
     * Running the same tick twice should not create duplicate visits.
     * (This is hypothetical — in real usage each tick fires once — but guards idempotency.)
     */
    public function test_merchant_spawns_at_most_once_per_tick(): void
    {
        $this->enableBar();
        $spawnTick = $this->findSpawnableTick(self::FIRST_MIN);

        if ($spawnTick === null) {
            $this->markTestSkipped('Could not find a spawnable tick.');
        }

        // First run of this tick
        Artisan::call('game:tick', ['--tick' => $spawnTick]);
        $countAfterFirst = DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        // "Second run" of the same tick — active visit now blocks spawn
        Artisan::call('game:tick', ['--tick' => $spawnTick]);
        $countAfterSecond = DB::table('merchant_visits')
            ->where('colony_id', self::COLONY_ID)
            ->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond,
            'Running the same tick twice must not create duplicate merchant visits');
    }
}
