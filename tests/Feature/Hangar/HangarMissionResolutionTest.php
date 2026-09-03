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
 *   - test_completion_grants_harvester_entitlement_via_salvage
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart). Hangar building_id=44,
 * instance 1 already exists in TestSeeder (freed of pre-seeded ships here).
 */
use App\Console\Commands\GameTick;
use App\Services\HarvesterEntitlementService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
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

        // Neutralize the new success-roll for pre-existing reward tests below —
        // they assert reward payout, not RNG luck. Tests that specifically cover
        // success/failure override this locally with an explicit Config::set().
        Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 1.0, 'normal' => 1.0, 'schwer' => 1.0,
        ]);

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
        ?array $target = null,
        string $difficulty = 'normal'
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
            'difficulty' => $difficulty,
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
        $this->assertSame(90, $params['rewards']['credits']);
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
        // Lv0→1 threshold is 20 (config/knowledge.php) — seed a partial 15 ap_spend first:
        // 15+8=23 would overshoot the 20 threshold, proving the reward still caps rather
        // than overflowing past the level-up requirement.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 15, 'status_points' => 20]
        );
        $this->dispatchFixture('mission_data_sweep', 3, dispatchTick: 20500, target: ['research_id' => 91]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20506]); // return_tick = 20500 + 2×3

        $row = DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', 91)->first();

        $this->assertSame(20, (int) $row->ap_spend, 'ap_spend must cap at the Lv1 threshold, not reach the full 15+8');
        $this->assertSame(0, (int) $row->level, 'research_ap reward must not auto-levelup');
    }

    public function test_completion_caps_research_ap_at_discounted_threshold_with_sciencelab_lv5(): void
    {
        // Sciencelab Lv5 (building_id 31) discounts knowledge-levelup AP costs via
        // ProjectBonusService::effectiveKnowledgeApForLevelup() — the cap used here must
        // match that discounted value, not the raw config/knowledge.php threshold.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 31],
            ['level' => 5, 'status_points' => 20, 'ap_spend' => 0]
        );

        $rawThreshold = (int) config('knowledge.cartography.levelup_costs.1');
        $curve = config('buildings.sciencelab.knowledge_ap_cost_reduction_per_lv');
        $discountPercent = (int) (($curve[4] ?? 0) + ($curve[5] ?? 0));
        $discountedThreshold = (int) max(
            ceil($rawThreshold * (float) config('game.project_min_cost_factor', 0.5)),
            round($rawThreshold * (1 - $discountPercent / 100))
        );
        $this->assertLessThan($rawThreshold, $discountedThreshold, 'precondition: Lv5 must actually discount the threshold');

        // Seed 15 ap_spend so the reward's +8 (mission_data_sweep) overshoots both the
        // raw threshold (20) and the discounted one — proving which value actually caps.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 15, 'status_points' => 20]
        );
        $this->dispatchFixture('mission_data_sweep', 3, dispatchTick: 20700, target: ['research_id' => 91]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20706]); // return_tick = 20700 + 2×3

        $row = DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)->where('research_id', 91)->first();

        $this->assertSame($discountedThreshold, (int) $row->ap_spend, 'ap_spend must cap at the discounted threshold, not the raw config value');
    }

    public function test_completion_grants_harvester_entitlement_via_salvage(): void
    {
        // mission_harvester_salvage reward: harvester_instance => true (GDD §4c
        // "Harvester-Zweitinstanz: Bezugsquelle", freigegeben 2026-08-05, Weg B). This
        // grants the earned entitlement (HarvesterEntitlementService) — it does NOT
        // place the building itself, the player still picks a Regolith tile via the
        // normal ColonyController::placeBuilding flow.
        $this->dispatchFixture('mission_harvester_salvage', 4, dispatchTick: 20600, target: ['q' => 10, 'r' => 1]);

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 20608]); // return_tick = 20600 + 2×4

        $entitlementService = $this->app->make(HarvesterEntitlementService::class);
        $this->assertTrue($entitlementService->hasEntitlement(self::USER_ID));
        $this->assertTrue($entitlementService->isSalvageSourced(self::USER_ID));

        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 20608)->where('event', 'hangar.mission_completed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertTrue($params['rewards']['harvester_instance']);
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

    // ── Success chance / difficulty (Spec: docs/superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md) ──

    public function test_success_rolls_pay_the_scaled_reward(): void
    {
        // base_chance forced to 1.0 in setUp() — success guaranteed regardless of seed.
        // reward_multiplier['schwer'] = 1.4 (config/game.php) → 90 * 1.4 = 126.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21000, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21002]);

        $this->assertSame('completed', $this->missionState($missionId));
        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 21002)->where('event', 'hangar.mission_completed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertSame(126, $params['rewards']['credits'], 'schwer multiplies the base 90 credits by 1.4');
    }

    public function test_failed_roll_pays_no_reward_and_fires_mission_failed_event(): void
    {
        DB::table('runs')->where('id', 1)->update(['rng_seed' => 999]);
        Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21100, difficulty: 'normal');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21102]);

        $this->assertSame('completed', $this->missionState($missionId), 'a failed roll is still a completed mission outcome, not aborted');
        $this->assertSame('docked', $this->shipState());
        $this->assertDatabaseMissing('colony_log', [
            'user' => self::USER_ID, 'tick' => 21102, 'event' => 'hangar.mission_completed',
        ]);
        $log = DB::table('colony_log')
            ->where('user', self::USER_ID)->where('tick', 21102)->where('event', 'hangar.mission_failed')
            ->first();
        $this->assertNotNull($log);
        $params = json_decode($log->parameters, true);
        $this->assertSame('mission_courier_run', $params['mission_key']);
        $this->assertSame('normal', $params['difficulty']);
    }

    public function test_hard_fail_on_schwer_applies_extra_wear_on_top_of_normal_wear(): void
    {
        Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        // drone wear_per_sol = 1.5 (config/ships.php), hard_fail_extra_wear = 1.0 (config/game.php).
        // 20.0 - 1.5 (normal wear, applied every tick) - 1.0 (hard fail extra) = 17.5.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21200, statusPoints: 20.0, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21202]);

        $this->assertEqualsWithDelta(17.5, $this->shipStatusPoints(), 0.001);
        $this->assertSame('completed', $this->missionState($missionId));
    }

    public function test_hard_fail_extra_wear_does_not_drop_ship_below_zero_sp(): void
    {
        Config::set('game.missions.difficulty.base_chance', [
            'leicht' => 0.0, 'normal' => 0.0, 'schwer' => 0.0,
        ]);
        // 1.6 SP - 1.5 normal wear = 0.1, stays above zero (no abort), then -1.0 hard-fail must floor at 0, not go negative.
        $missionId = $this->dispatchFixture('mission_courier_run', 1, dispatchTick: 21300, statusPoints: 1.6, difficulty: 'schwer');

        Artisan::call('game:tick', ['--run' => 1, '--tick' => 21302]);

        $this->assertSame(0.0, $this->shipStatusPoints());
        $this->assertSame('completed', $this->missionState($missionId), 'hard-fail wear floor must not retroactively trigger an abort');
    }
}
