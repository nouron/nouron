<?php

namespace Tests\Feature\Hangar;

/**
 * GameTick::processHangarMissions — mission wear, completion and reward payout
 * (GDD §7 Schiffs-Verschleiß, §8b Missionskatalog).
 *
 * Fixtures are inserted directly into colony_hangar_missions / colony_ships,
 * bypassing HangarService::dispatchShip — dispatch-time gates are covered by
 * HangarServiceTest; this file only exercises tick-time resolution.
 *
 * Covered scenarios:
 *   - test_wear_reduces_status_points_each_tick_while_dispatched
 *   - test_docked_ship_has_no_wear
 *   - test_completion_pays_credit_reward_and_docks_ship
 *   - test_completion_does_not_fire_before_return_tick
 *   - test_abort_when_status_points_reach_zero_grants_no_reward
 *   - test_completion_reveals_unexplored_tiles
 *   - test_completion_grants_research_ap_capped_at_levelup_threshold
 *   - test_seeded_roll_is_deterministic_for_same_seed
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). Hangar building_id=44,
 * instance 1 already exists in TestSeeder (freed of pre-seeded ships here).
 */
use App\Console\Commands\GameTick;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HangarMissionResolutionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const HANGAR_INSTANCE = 1;

    private const SHIP_DRONE = 85;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Free the bay from TestSeeder's pre-populated fixture ships (a real bay
        // only ever holds one) so each test controls exactly one dispatched ship.
        DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)
            ->where('hangar_instance_id', self::HANGAR_INSTANCE)
            ->update(['hangar_instance_id' => null]);

        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
    }

    /**
     * Inserts a dispatched drone plus its active mission row directly (bypasses
     * dispatchShip's dispatch-time gates — this file tests resolution only).
     */
    private function dispatchFixture(
        string $missionKey,
        int $solDistance,
        int $dispatchTick,
        float $statusPoints = 20.0,
        ?array $target = null
    ): int {
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_DRONE],
            [
                'hangar_instance_id' => self::HANGAR_INSTANCE,
                'ship_state' => 'dispatched',
                'level' => 1,
                'status_points' => $statusPoints,
                'ap_spend' => 0,
            ]
        );

        return DB::table('colony_hangar_missions')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'instance_id' => self::HANGAR_INSTANCE,
            'ship_id' => self::SHIP_DRONE,
            'destination' => $missionKey,
            'sol_distance' => $solDistance,
            'target' => $target !== null ? json_encode($target) : null,
            'dispatch_tick' => $dispatchTick,
            'recall_tick' => null,
            'state' => 'active',
            'created_at' => now(),
        ]);
    }

    private function shipStatusPoints(): float
    {
        return (float) DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_DRONE)->value('status_points');
    }

    private function shipState(): string
    {
        return DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_DRONE)->value('ship_state');
    }

    private function missionState(int $missionId): string
    {
        return DB::table('colony_hangar_missions')->where('id', $missionId)->value('state');
    }

    // ── Wear ──────────────────────────────────────────────────────────────────

    public function test_wear_reduces_status_points_each_tick_while_dispatched(): void
    {
        // mission_courier_run: sol_distance 1 → return_tick = dispatch_tick + 2.
        // Tick once, one sol before return — only wear applies, mission stays active.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 20000);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20001]);

        $this->assertEqualsWithDelta(18.5, $this->shipStatusPoints(), 0.001, 'drone wear_per_sol = 1.5');
        $this->assertSame('dispatched', $this->shipState(), 'must not complete before return_tick');
        $this->assertSame('active', $this->missionState($missionId));
    }

    public function test_docked_ship_has_no_wear(): void
    {
        // A docked (non-dispatched) ship must be untouched by the wear step.
        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_DRONE],
            ['hangar_instance_id' => self::HANGAR_INSTANCE, 'ship_state' => 'docked', 'level' => 1, 'status_points' => 20.0, 'ap_spend' => 0]
        );

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20010]);

        $this->assertSame(20.0, $this->shipStatusPoints());
    }

    // ── Completion ────────────────────────────────────────────────────────────

    public function test_completion_pays_credit_reward_and_docks_ship(): void
    {
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 20100);

        // return_tick = 20100 + 2×1 = 20102. Note: total credits also move from the
        // unrelated passive-income step (GameTick::generatePassiveCredits) — the
        // mission reward is verified via the logged reward details, not the raw total.
        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20102]);

        $this->assertSame('completed', $this->missionState($missionId));
        $this->assertSame('docked', $this->shipState());

        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 20102)->where('event', 'hangar.mission_completed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertSame('mission_courier_run', $params['mission_key']);
        $this->assertSame(60, $params['rewards']['credits']);
    }

    public function test_completion_does_not_fire_before_return_tick(): void
    {
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 20200);

        // One sol short of return_tick (20202) — must still be active.
        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20201]);

        $this->assertSame('active', $this->missionState($missionId));
        $this->assertSame('dispatched', $this->shipState());
        $this->assertDatabaseMissing('colony_log', [
            'user' => self::USER_ID,
            'tick' => 20201,
            'event' => 'hangar.mission_completed',
        ]);
    }

    // ── Abort ─────────────────────────────────────────────────────────────────

    public function test_abort_when_status_points_reach_zero_grants_no_reward(): void
    {
        // 1.0 SP − 1.5 wear = below zero → must abort, regardless of return_tick.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 20300, statusPoints: 1.0);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20300]);

        $this->assertDatabaseMissing('colony_log', [
            'user' => self::USER_ID,
            'tick' => 20300,
            'event' => 'hangar.mission_completed',
        ]);
        $this->assertSame('aborted', $this->missionState($missionId));
        $this->assertSame('docked', $this->shipState(), 'worn-out ship limps home');
        $this->assertSame(0.0, $this->shipStatusPoints());

        $this->assertDatabaseHas('colony_log', [
            'user' => self::USER_ID,
            'tick' => 20300,
            'event' => 'hangar.mission_aborted',
        ]);
    }

    // ── Reward types ──────────────────────────────────────────────────────────

    public function test_completion_reveals_unexplored_tiles(): void
    {
        // mission_recon_flight reward: reveal_tiles => 2.
        DB::table('colony_tiles')->insert([
            ['colony_id' => self::COLONY_ID, 'q' => 10, 'r' => 1, 'ring' => 3, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => 10, 'r' => 2, 'ring' => 3, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 0],
            ['colony_id' => self::COLONY_ID, 'q' => 10, 'r' => 3, 'ring' => 3, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 0],
        ]);

        $this->dispatchFixture('mission_recon_flight', 1, dispatchTick: 20400);
        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20402]);

        $explored = DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('is_colony_zone', 0)->where('is_explored', 1)->count();
        $this->assertSame(2, $explored, 'recon_flight reveals exactly 2 tiles');
    }

    public function test_completion_grants_research_ap_capped_at_levelup_threshold(): void
    {
        // mission_data_sweep reward: research_ap => 8, invested into cartography (id 91).
        // Lv0→1 threshold is 12 (config/knowledge.php) — the reward alone (8) no
        // longer exceeds it, so seed a partial 6 ap_spend first: 6+8=14 would
        // overshoot the 12 threshold, proving the reward still caps rather than
        // overflowing past the level-up requirement.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 6, 'status_points' => 20]
        );
        $this->dispatchFixture('mission_data_sweep', 3, dispatchTick: 20500, target: ['research_id' => 91]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20506]); // return_tick = 20500 + 2×3

        $row = DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', 91)->first();

        $this->assertSame(12, (int) $row->ap_spend, 'ap_spend must cap at the Lv1 threshold, not reach the full 6+8');
        $this->assertSame(0, (int) $row->level, 'research_ap reward must not auto-levelup');
    }

    // ── Determinism (ADR 0003 rng_seed) ──────────────────────────────────────

    public function test_seeded_roll_is_deterministic_for_same_seed(): void
    {
        $method = new \ReflectionMethod(GameTick::class, 'seededRoll');
        $method->setAccessible(true);
        $command = $this->app->make(GameTick::class);

        $first = $method->invoke($command, 42, 20, 30);
        $second = $method->invoke($command, 42, 20, 30);

        $this->assertSame($first, $second, 'same seed must roll the same value');
        $this->assertGreaterThanOrEqual(20, $first);
        $this->assertLessThanOrEqual(30, $first);
    }
}
