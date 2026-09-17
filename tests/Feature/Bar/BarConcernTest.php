<?php

namespace Tests\Feature\Bar;

/**
 * BarService Charakter-Anliegen tests (GDD §12 Kanal 1, A41).
 *
 * Covered scenarios:
 *  GENERATE
 *    - test_generate_does_nothing_when_encounter_fired_this_tick
 *    - test_generate_does_nothing_when_bar_not_built
 *    - test_generate_skips_when_a_pending_concern_already_exists
 *    - test_generate_creates_a_concern_for_an_eligible_character
 *    - test_generate_respects_character_cooldown
 *    - test_generate_allows_character_again_after_cooldown_expires
 *
 *  RESOLVE — shared validation
 *    - test_resolve_returns_error_for_expired_concern
 *    - test_resolve_returns_error_when_already_resolved
 *    - test_resolve_returns_error_when_not_found
 *    - test_resolve_returns_error_when_insufficient_ap
 *
 *  RESOLVE — per character
 *    - test_resolve_smuggler_grants_a_ship
 *    - test_resolve_information_broker_grants_a_no_expiry_voucher
 *    - test_resolve_mechanic_injects_ap_bonus_into_chosen_knowledge
 *    - test_resolve_mechanic_without_knowledge_id_grants_no_bonus
 *    - test_resolve_doctor_grants_compounds_scaled_by_bar_level
 *    - test_resolve_prospector_success_grants_regolith
 *    - test_resolve_prospector_failure_grants_nothing_but_still_spends_ap
 *    - test_resolve_mercenary_grants_credits
 *    - test_resolve_founder_success_grants_an_expiring_voucher
 *    - test_resolve_founder_failure_grants_no_voucher
 *    - test_resolve_preacher_success_fires_positive_trust_event
 *    - test_resolve_preacher_failure_fires_negative_trust_event
 *    - test_resolve_stranger_success_pays_out_and_consumes_stake
 *    - test_resolve_stranger_failure_loses_stake_only
 *    - test_resolve_stranger_returns_error_when_insufficient_stake
 */

use App\Services\AdvisorService;
use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarConcernTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3; // Bart

    private const BAR_BUILDING_ID = 52;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_CREDITS = 1;

    private const KNOWLEDGE_ID = 96; // defense — colony_researches level=0, ap_spend=0

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
        config(['game.bypass.ap_checks' => false]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setBarLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => $level]);
    }

    private function insertConcern(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'character_slug' => 'mercenary',
            'created_tick' => 10,
            'expires_tick' => 20,
            'is_resolved' => false,
            'success' => null,
            'outcome' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_concerns')->insertGetId(array_merge($defaults, $overrides));
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

    private function drainSharedApPool(): void
    {
        $advisorService = $this->app->make(AdvisorService::class);
        $totalAp = $advisorService->getTotalActionPoints(self::COLONY_ID);
        $advisorService->lockActionPoints(self::COLONY_ID, $totalAp);
    }

    // ── GENERATE ─────────────────────────────────────────────────────────────

    public function test_generate_does_nothing_when_encounter_fired_this_tick(): void
    {
        $this->setBarLevel(5);
        config(['game.bar.concern.spawn_chance_per_level' => [5 => 1.0]]);

        $this->barService->generateConcernForColony(self::COLONY_ID, 10, true);

        $this->assertSame(0, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_does_nothing_when_bar_not_built(): void
    {
        $this->setBarLevel(0);
        config(['game.bar.concern.spawn_chance_per_level' => [0 => 1.0]]);

        $this->barService->generateConcernForColony(self::COLONY_ID, 10, false);

        $this->assertSame(0, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_skips_when_a_pending_concern_already_exists(): void
    {
        $this->setBarLevel(5);
        $this->insertConcern(['expires_tick' => 99]);
        config(['game.bar.concern.spawn_chance_per_level' => [5 => 1.0]]);

        $this->barService->generateConcernForColony(self::COLONY_ID, 10, false);

        $this->assertSame(1, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_creates_a_concern_for_an_eligible_character(): void
    {
        $this->setBarLevel(5);
        config(['game.bar.concern.spawn_chance_per_level' => [5 => 1.0]]);

        $this->barService->generateConcernForColony(self::COLONY_ID, 10, false);

        $concern = DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($concern);
        $this->assertContains($concern->character_slug, [
            'smuggler', 'information_broker', 'mechanic', 'doctor',
            'prospector', 'mercenary', 'founder', 'preacher', 'stranger',
        ]);
        $this->assertFalse((bool) $concern->is_resolved);
    }

    public function test_generate_respects_character_cooldown(): void
    {
        $this->setBarLevel(5);
        // Pin the weight roster to a single character so we know exactly who
        // must be excluded once on cooldown.
        config([
            'game.bar.concern.spawn_chance_per_level' => [5 => 1.0],
            'game.bar.concern.character_weights' => ['mercenary' => 1],
            'game.bar.concern.character_cooldown_sols' => 5,
        ]);
        $this->insertConcern(['character_slug' => 'mercenary', 'created_tick' => 8, 'is_resolved' => true, 'expires_tick' => 8]);

        $this->barService->generateConcernForColony(self::COLONY_ID, 10, false);

        // Only mercenary is weighted and it's on cooldown (10 - 8 = 2 < 5) -> no eligible character -> no spawn.
        $this->assertSame(1, DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_generate_allows_character_again_after_cooldown_expires(): void
    {
        $this->setBarLevel(5);
        config([
            'game.bar.concern.spawn_chance_per_level' => [5 => 1.0],
            'game.bar.concern.character_weights' => ['mercenary' => 1],
            'game.bar.concern.character_cooldown_sols' => 5,
        ]);
        $this->insertConcern(['character_slug' => 'mercenary', 'created_tick' => 4, 'is_resolved' => true, 'expires_tick' => 4]);

        // 10 - 4 = 6 >= cooldown 5 -> mercenary eligible again.
        $this->barService->generateConcernForColony(self::COLONY_ID, 10, false);

        $new = DB::table('bar_concerns')->where('colony_id', self::COLONY_ID)->where('created_tick', 10)->first();
        $this->assertNotNull($new);
        $this->assertSame('mercenary', $new->character_slug);
    }

    // ── RESOLVE — shared validation ─────────────────────────────────────────────

    public function test_resolve_returns_error_for_expired_concern(): void
    {
        $id = $this->insertConcern(['expires_tick' => 5]);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_resolve_returns_error_when_already_resolved(): void
    {
        $id = $this->insertConcern(['is_resolved' => true]);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_resolve_returns_error_when_not_found(): void
    {
        $result = $this->barService->resolveConcern(self::COLONY_ID, 999999, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    public function test_resolve_returns_error_when_insufficient_ap(): void
    {
        $id = $this->insertConcern(['character_slug' => 'mercenary', 'expires_tick' => 20]);
        $this->drainSharedApPool();

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }

    // ── RESOLVE — smuggler (Dax) ─────────────────────────────────────────────────

    public function test_resolve_smuggler_grants_a_ship(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
        $id = $this->insertConcern(['character_slug' => 'smuggler']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['success']);
        $shipId = (int) config('game.bar.concern.smuggler.ship_id');
        $ship = DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->where('ship_id', $shipId)->first();
        $this->assertNotNull($ship, 'a colony_ships row must be created for the granted ship');
    }

    // ── RESOLVE — information_broker (Vesper) ────────────────────────────────────

    public function test_resolve_information_broker_grants_a_no_expiry_voucher(): void
    {
        $id = $this->insertConcern(['character_slug' => 'information_broker']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $voucher = DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($voucher);
        $this->assertSame(25, (int) $voucher->discount_pct);
        $this->assertSame('information_broker', $voucher->source);
        $this->assertNull($voucher->expires_tick);
        $this->assertNull($voucher->consumed_tick);
    }

    // ── RESOLVE — mechanic (Sarka) ───────────────────────────────────────────────

    public function test_resolve_mechanic_injects_ap_bonus_into_chosen_knowledge(): void
    {
        $id = $this->insertConcern(['character_slug' => 'mechanic']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10, self::KNOWLEDGE_ID);

        $this->assertTrue($result['ok']);
        $apSpend = (int) DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)
            ->where('research_id', self::KNOWLEDGE_ID)
            ->value('ap_spend');
        $this->assertSame((int) config('game.bar.concern.mechanic.ap_bonus'), $apSpend);
    }

    public function test_resolve_mechanic_without_knowledge_id_grants_no_bonus(): void
    {
        $id = $this->insertConcern(['character_slug' => 'mechanic']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['ap_bonus']);
    }

    // ── RESOLVE — doctor (Maret) ─────────────────────────────────────────────────

    public function test_resolve_doctor_grants_compounds_scaled_by_bar_level(): void
    {
        $this->setBarLevel(3);
        $this->setColonyResource(self::RES_COMPOUNDS, 0);
        $id = $this->insertConcern(['character_slug' => 'doctor']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $expected = (int) config('game.bar.concern.doctor.compounds_amount_per_level')[3];
        $this->assertSame($expected, $this->getColonyResource(self::RES_COMPOUNDS));
    }

    // ── RESOLVE — prospector (Fen) ────────────────────────────────────────────────

    public function test_resolve_prospector_success_grants_regolith(): void
    {
        config(['game.bar.concern.success_chance.prospector' => 1.0]);
        $this->setColonyResource(self::RES_REGOLITH, 0);
        $id = $this->insertConcern(['character_slug' => 'prospector']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(20, $this->getColonyResource(self::RES_REGOLITH));
        $this->assertLessThanOrEqual(30, $this->getColonyResource(self::RES_REGOLITH));
    }

    public function test_resolve_prospector_failure_grants_nothing_but_still_spends_ap(): void
    {
        config(['game.bar.concern.success_chance.prospector' => 0.0]);
        $this->setColonyResource(self::RES_REGOLITH, 0);
        $id = $this->insertConcern(['character_slug' => 'prospector']);
        $advisorService = $this->app->make(AdvisorService::class);
        $before = $advisorService->getAvailableActionPoints(self::COLONY_ID);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['success']);
        $this->assertSame(0, $this->getColonyResource(self::RES_REGOLITH));

        $after = $advisorService->getAvailableActionPoints(self::COLONY_ID);
        $this->assertSame((int) config('game.bar.concern.ap_cost.prospector'), $before - $after);
    }

    // ── RESOLVE — mercenary (Juno) ────────────────────────────────────────────────

    public function test_resolve_mercenary_grants_credits(): void
    {
        $this->setCredits(0);
        $id = $this->insertConcern(['character_slug' => 'mercenary']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertGreaterThanOrEqual(30, $this->getCredits());
        $this->assertLessThanOrEqual(50, $this->getCredits());
    }

    // ── RESOLVE — founder (Aldra) ─────────────────────────────────────────────────

    public function test_resolve_founder_success_grants_an_expiring_voucher(): void
    {
        config(['game.bar.concern.success_chance.founder' => 1.0]);
        $id = $this->insertConcern(['character_slug' => 'founder']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $voucher = DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->first();
        $this->assertNotNull($voucher);
        $this->assertSame(15, (int) $voucher->discount_pct);
        $this->assertSame('founder', $voucher->source);
        $this->assertNotNull($voucher->expires_tick);
    }

    public function test_resolve_founder_failure_grants_no_voucher(): void
    {
        config(['game.bar.concern.success_chance.founder' => 0.0]);
        $id = $this->insertConcern(['character_slug' => 'founder']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['success']);
        $this->assertSame(0, DB::table('colony_building_discount_vouchers')->where('colony_id', self::COLONY_ID)->count());
    }

    // ── RESOLVE — preacher (Sorel) ────────────────────────────────────────────────

    public function test_resolve_preacher_success_fires_positive_trust_event(): void
    {
        config(['game.bar.concern.success_chance.preacher' => 1.0]);
        $id = $this->insertConcern(['character_slug' => 'preacher']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['success']);
        $event = DB::table('trust_events')->where('colony_id', self::COLONY_ID)->where('tick', 10)->first();
        $this->assertNotNull($event);
        $this->assertSame('story_concern_resolved', $event->event_type);
    }

    public function test_resolve_preacher_failure_fires_negative_trust_event(): void
    {
        config(['game.bar.concern.success_chance.preacher' => 0.0]);
        $id = $this->insertConcern(['character_slug' => 'preacher']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['success']);
        $event = DB::table('trust_events')->where('colony_id', self::COLONY_ID)->where('tick', 10)->first();
        $this->assertNotNull($event);
        $this->assertSame('story_concern_failed', $event->event_type);
    }

    // ── RESOLVE — stranger ────────────────────────────────────────────────────────

    public function test_resolve_stranger_success_pays_out_and_consumes_stake(): void
    {
        config(['game.bar.concern.success_chance.stranger' => 1.0]);
        $this->setColonyResource(self::RES_COMPOUNDS, 100);
        $this->setCredits(0);
        $id = $this->insertConcern(['character_slug' => 'stranger']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(50, $this->getColonyResource(self::RES_COMPOUNDS)); // 100 - stake(40-50)
        $this->assertLessThanOrEqual(60, $this->getColonyResource(self::RES_COMPOUNDS));
        $this->assertGreaterThanOrEqual(150, $this->getCredits());
        $this->assertLessThanOrEqual(180, $this->getCredits());
    }

    public function test_resolve_stranger_failure_loses_stake_only(): void
    {
        config(['game.bar.concern.success_chance.stranger' => 0.0]);
        $this->setColonyResource(self::RES_COMPOUNDS, 100);
        $this->setCredits(0);
        $id = $this->insertConcern(['character_slug' => 'stranger']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertFalse($result['success']);
        $this->assertLessThan(100, $this->getColonyResource(self::RES_COMPOUNDS));
        $this->assertSame(0, $this->getCredits());
    }

    public function test_resolve_stranger_returns_error_when_insufficient_stake(): void
    {
        $this->setColonyResource(self::RES_COMPOUNDS, 5);
        $id = $this->insertConcern(['character_slug' => 'stranger']);

        $result = $this->barService->resolveConcern(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertFalse($result['ok']);
    }
}
