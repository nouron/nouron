<?php

namespace Tests\Feature\Onboarding;

use App\Services\OnboardingHintService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for the OnboardingHintService hint-ranking and dismiss logic.
 */
class OnboardingHintServiceTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingHintService $service;

    private int $userId = 999;

    private int $colonyId = 999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->service = $this->app->make(OnboardingHintService::class);

        DB::table('user')->insertOrIgnore([
            'user_id' => $this->userId,
            'username' => 'TestUser',
            'display_name' => 'Test User',
            'role' => 'player',
            'password' => bcrypt('pw'),
            'email' => 'test@test.de',
            'activation_key' => 'testkey',
            'faction_id' => 7,
        ]);
        DB::table('glx_colonies')->insertOrIgnore([
            'id' => $this->colonyId, 'user_id' => $this->userId,
            'name' => 'TestColony',
            'since_tick' => 1, 'is_primary' => 1,
        ]);

        // Run at Sol 0 — keeps tick-gated hints (3/4/5/6) below their thresholds.
        DB::table('runs')->insertOrIgnore([
            'id' => $this->colonyId,
            'user_id' => $this->userId,
            'colony_id' => $this->colonyId,
            'current_tick' => 0,
            'status' => 'active',
            'settings' => json_encode(['tick_limit' => 100, 'bypass' => ['ap_checks' => false, 'resource_costs' => false, 'supply_checks' => false], 'supply_cap_max' => 200]),
        ]);

        DB::table('user_preferences')->insertOrIgnore([
            'user_id' => $this->userId, 'onboarding_hints' => 1, 'dismissed_hints' => null,
        ]);

        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 12, 'amount' => 0,
        ]);
        // Generous Regolith/Werkstoffe/Supply by default so the build-affordability
        // check on hint_6/hint_agrardome/hint_analytik doesn't interfere with tests
        // that aren't specifically about resource scarcity — see canAffordBuilding*
        // tests below for the cases that exercise the scarcity path directly.
        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 3, 'amount' => 500,
        ]);
        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 4, 'amount' => 100,
        ]);
        DB::table('user_resources')->insertOrIgnore([
            'user_id' => $this->userId, 'credits' => 3000, 'supply' => 200,
        ]);

        // Baseline buildings: CC, Harvester, Housing at level 1, full status (20/20).
        // Real game start seeds these damaged (16/20); kept full here so the repair
        // hint stays silent and the other hint-ranking tests can be isolated.
        // The repair hint is exercised explicitly via damageBuilding().
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 25,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => null, 'tile_y' => null,
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 27,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 1, 'tile_y' => 0, // ring 1 — inside colony zone
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 0, 'tile_y' => 1,
        ]);

        // Harvester start position: ring 1, colony_zone=1, no regolith (colony zone is building area).
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 1, 'r' => 0, 'ring' => 1,
            'tile_type' => 'terrain_empty', 'is_explored' => 1,
            'is_colony_zone' => 1, 'is_deep_scanned' => 0,
        ]);
        // Ring 2: fog at CC Level 1 — NOT colony zone yet (unlocked by CC upgrade).
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 2, 'r' => 0, 'ring' => 2,
            'tile_type' => 'terrain_empty', 'is_explored' => 0,
            'is_colony_zone' => 0, 'is_deep_scanned' => 0,
        ]);
        // Pre-explored ring-3 regolith (Nexus scout tile — guaranteed Harvester move target).
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 3, 'r' => 0, 'ring' => 3,
            'tile_type' => 'regolith_normal', 'is_explored' => 1,
            'is_colony_zone' => 0, 'is_deep_scanned' => 0,
        ]);
    }

    // ── Guard: disabled / missing prefs ──────────────────────────────────────

    public function test_returns_hint_when_no_prefs_row(): void
    {
        DB::table('user_preferences')->where('user_id', $this->userId)->delete();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint, 'Missing prefs row must not suppress hints (default = enabled)');
        $this->assertEquals(1, $hint['rank']);
    }

    public function test_returns_null_when_onboarding_hints_disabled(): void
    {
        DB::table('user_preferences')
            ->where('user_id', $this->userId)
            ->update(['onboarding_hints' => 0]);

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Hint 1: no engineer ──────────────────────────────────────────────────

    public function test_hint_1_fires_when_no_engineer(): void
    {
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank']);
        $this->assertEquals('hint_1', $hint['key']);
    }

    public function test_hint_1_fires_even_when_harvester_moved_outside(): void
    {
        // Moving harvester doesn't silence hint 1 — engineer still missing.
        $this->moveHarvesterOutside();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank']);
    }

    public function test_hint_1_silent_when_engineer_present(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside(); // silence hint 2
        $this->suppressLateHints();    // silence hints 4-6

        // Hint 1 is silent; with every Sol-1 to-do done at tick 0 and the CC still
        // at level 1 with Bau-AP available, the CC pre-invest hint (rank 6) is the
        // active Sol-1 floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    // ── Urgent repair hint: building near level-down ─────────────────────────

    public function test_urgent_repair_hint_fires_when_building_critical(): void
    {
        // Engineer hired; a building at SP=3 (<= threshold) → urgent repair wins (rank 2),
        // ahead of the Harvester-move hint (rank 3).
        $this->placeEngineer();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['status_points' => 3]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
        $this->assertEquals('hint_repair_urgent', $hint['key']);
        $this->assertEquals('colony.onboarding_hint_repair_urgent', $hint['text_key']);
    }

    public function test_urgent_repair_hint_silent_above_threshold(): void
    {
        // SP=4 is above the threshold (3) → urgent silent; teaching repair (rank 4) wins instead.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['status_points' => 4]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals('hint_repair', $hint['key']);
    }

    public function test_urgent_repair_hint_ignores_buildings_under_construction(): void
    {
        // A level-0 (under-construction) building at low SP must not trigger the urgent hint.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 46,
            'instance_id' => 1, 'level' => 0, 'status_points' => 1, 'ap_spend' => 0,
            'tile_x' => 2, 'tile_y' => 0,
        ]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        // Buildings are otherwise full → no repair hint of either kind; the level-0
        // building is ignored. The CC pre-invest hint (rank 6) is the Sol-1 floor.
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    // ── Repair hint: any building below max status ───────────────────────────

    public function test_repair_hint_fires_when_building_damaged(): void
    {
        // Engineer hired (hint 1 resolved) and Harvester moved outside (hint 2 resolved);
        // a lightly damaged building (16/20, above the urgent threshold) surfaces the
        // teaching repair hint (rank 4).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->damageBuilding(25);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(4, $hint['rank']);
        $this->assertEquals('hint_repair', $hint['key']);
        $this->assertEquals('colony.onboarding_hint_repair', $hint['text_key']);
        $this->assertEquals('/colony/view', $hint['target_url']);
    }

    public function test_repair_hint_fires_from_sol_1_without_tick_gate(): void
    {
        // No tick gate: fires even at Sol 0 (run current_tick stays 0 from setUp).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->damageBuilding(28);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals('hint_repair', $hint['key']);
    }

    public function test_repair_hint_silent_when_all_buildings_full(): void
    {
        // Baseline buildings are full (20/20) → repair hint silent; with CC at level 1
        // and Bau-AP available at Sol 1, the CC pre-invest hint (rank 6) shows.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    public function test_repair_hint_yields_to_missing_engineer(): void
    {
        // Building damaged but no engineer → hint_1 (rank 1) still wins over repair (rank 3).
        $this->damageBuilding(25);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertEquals(1, $hint['rank']);
        $this->assertEquals('hint_1', $hint['key']);
    }

    // ── Hint 2: harvester inside colony zone ─────────────────────────────────

    public function test_hint_2_fires_when_harvester_in_colony_zone(): void
    {
        // Engineer hired (hint 1 resolved); harvester at (1,0) = colony_zone=1 → hint 2 fires (rank 3).
        $this->placeEngineer();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(3, $hint['rank']);
        $this->assertEquals('hint_2', $hint['key']);
    }

    public function test_hint_2_silent_when_harvester_outside_colony_zone(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside(); // tile (3,0) = colony_zone=0
        $this->suppressLateHints();

        // Hint 2 silent; Sol-1 to-dos done at tick 0 with CC level 1 + Bau-AP →
        // the CC pre-invest hint (rank 6) is the floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    public function test_hint_2_silent_when_no_harvester_placed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 27)
            ->update(['tile_x' => null, 'tile_y' => null]);

        $this->placeEngineer();
        $this->suppressLateHints();

        // Hint 2 silent (no harvester tile); CC pre-invest hint (rank 6) fills the
        // bar at tick 0 (CC level 1, Bau-AP available).
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    // ── Hint 3: CC level < 2 (fires from Sol 2) ──────────────────────────────

    public function test_hint_3_fires_when_cc_level_1_after_sol2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->setRunTick(2); // meet Sol threshold

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(5, $hint['rank']);
        $this->assertEquals('hint_3', $hint['key']);
    }

    public function test_hint_3_silent_before_sol2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        // Sol stays at 0 (current_tick < gate of 1) → hint 3 silent
        $this->suppressLateHints();

        // Hint 3 silent before Sol 2; with CC level 1 + Bau-AP at tick 0 the CC
        // pre-invest hint (rank 7) is the active floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_cc_invest', $hint['key']);
    }

    public function test_hint_3_silent_when_cc_level_2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->setRunTick(2);
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->suppressLateHints();

        // CC is level 2 → both hint_3 (gate tick 1, requires level < 2) and the CC
        // pre-invest hint are silent. The explore hint is Sol-1-only now
        // (until_tick 0), so at Sol 2 it no longer fills the gap either.
        // suppressLateHints() places Cantina/Agrardom/Sciencelab/Hangar, so unused
        // Bau-AP surfaces hint_spend_remaining_ap (rank 16) instead of the end-sol floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
    }

    // ── Hint advisor_slot2: CC2 unlocks a second advisor slot (rank 6) ──────

    public function test_advisor_slot2_hint_fires_when_cc2_and_slot_free(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(6, $hint['rank']);
        $this->assertSame('hint_advisor_slot2', $hint['key']);
        $this->assertSame('colony.onboarding_hint_advisor_slot2', $hint['text_key']);
        $this->assertSame('/advisors', $hint['target_url']);
    }

    public function test_advisor_slot2_hint_silent_when_cc_below_level2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_advisor_slot2', $hint['key'] ?? null);
    }

    public function test_advisor_slot2_hint_silent_when_slot_already_filled(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_advisor_slot2', $hint['key'] ?? null);
    }

    public function test_advisor_slot2_hint_silent_for_fresh_cc1_colony_without_any_advisor(): void
    {
        // Regression: a brand-new CC1 colony with zero advisors hired also has a
        // "free slot" (slot 1) by the raw slot math — that's hint_1's job, not
        // this hint's. Explicit CC>=2 gate must keep this silent here.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_advisor_slot2', $hint['key'] ?? null);
    }

    // ── Hint cc_invest: pre-invest Bau-AP into CC (rank 7, Sol 1 only) ───────

    public function test_cc_invest_hint_fires_on_sol1_when_todos_done(): void
    {
        // Sol 1 (tick 0): engineer hired, Harvester relocated, buildings full,
        // CC still level 1, Bau-AP available → CC pre-invest hint (rank 6) fires.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(7, $hint['rank']);
        $this->assertSame('hint_cc_invest', $hint['key']);
        $this->assertSame('colony.onboarding_hint_cc_invest', $hint['text_key']);
        $this->assertSame('/colony/view', $hint['target_url']);
    }

    public function test_cc_invest_hint_silent_when_cc_already_level_2(): void
    {
        // CC at level 2 → cc_invest pointless; with fog + Nav-AP at Sol 1 the
        // explore hint (rank 8) surfaces instead.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_explore', $hint['key']);
    }

    public function test_cc_invest_hint_silent_when_no_construction_ap(): void
    {
        // Fix the tick to 0 so lock + read share the same tick, then lock the full
        // construction AP pool → cc_invest self-clears (Bau-AP exhausted).
        $this->app->instance(TickService::class, new TickService(0));
        $service = $this->app->make(OnboardingHintService::class);
        $personell = $this->app->make(PersonellService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $available = $personell->getConstructionPoints($this->colonyId);
        $this->assertGreaterThan(0, $available, 'precondition: construction AP available before lock');
        $personell->lockActionPoints('construction', $this->colonyId, $available);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        // cc_invest must no longer win; explore (rank 8) takes over while Nav-AP + fog remain.
        $this->assertNotNull($hint);
        $this->assertNotSame('hint_cc_invest', $hint['key']);
    }

    // ── Hint explore: scout unexplored tiles (rank 8, Sol 1–3) ───────────────

    public function test_explore_hint_fires_on_sol1_when_cc_done_and_fog_remains(): void
    {
        // Engineer + Harvester done, CC already level 2 (cc_invest silent), fog
        // present and Nav-AP available at Sol 1 → explore hint (rank 8) fires.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(8, $hint['rank']);
        $this->assertSame('hint_explore', $hint['key']);
        $this->assertSame('colony.onboarding_hint_explore', $hint['text_key']);
        $this->assertSame('/colony/view', $hint['target_url']);
    }

    public function test_explore_hint_silent_when_remaining_nav_ap_cant_afford_cheapest_fog_tile(): void
    {
        // Regression: only checking "Nav-AP > 0" let the hint nag the player to
        // explore even when the cheapest remaining fog tile costs more than what's
        // left (ring 2 = 2 AP/tile here). Lock down to 1 Nav-AP — unaffordable.
        $this->app->instance(TickService::class, new TickService(0));
        $service = $this->app->make(OnboardingHintService::class);
        $personell = $this->app->make(PersonellService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $available = $personell->getAvailableActionPoints('navigation', $this->colonyId);
        $this->assertGreaterThan(1, $available, 'precondition: base Nav-AP must exceed 1 to test the lock-down');
        $personell->lockActionPoints('navigation', $this->colonyId, $available - 1);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_explore', $hint['key'] ?? null);
    }

    public function test_explore_hint_silent_when_explored_tile_count_reaches_throttle(): void
    {
        // Still Sol 1 (within until_tick) and fog remains, but the player already
        // explored >= hint_explore_max_explored_tiles (6) ring>=2 tiles this run →
        // explore hint throttles off even though fog + Nav-AP are both present.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();

        for ($i = 0; $i < 6; $i++) {
            DB::table('colony_tiles')->insertOrIgnore([
                'colony_id' => $this->colonyId, 'q' => 10 + $i, 'r' => 0, 'ring' => 2,
                'tile_type' => 'terrain_empty', 'is_explored' => 1,
                'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            ]);
        }

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertNotSame('hint_explore', $hint['key']);
    }

    public function test_explore_hint_silent_after_until_tick(): void
    {
        // Beyond hint_explore_until_tick (0, Sol 1 only) → explore silent. CC still
        // level 1 and tick 3 >= CC gate (1) → hint_3 (rank 5) takes over.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->setRunTick(3);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_3', $hint['key']);
    }

    public function test_explore_hint_silent_when_no_fog_left(): void
    {
        // No unexplored tiles → explore silent; CC level 2 → cc_invest silent.
        // suppressLateHints() places Cantina/Agrardom/Sciencelab/Hangar, so unused
        // Bau-AP surfaces hint_spend_remaining_ap (rank 16) rather than the end-sol floor.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->clearFog();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
    }

    // ── Bridge hint: "Sol beenden" (rank 11, Sol-1 only) ─────────────────────

    public function test_end_sol_bridge_hint_fires_when_sol1_actions_done(): void
    {
        // Sol 1 (current_tick 0): engineer hired, Harvester relocated, buildings full.
        // suppressLateHints() places Cantina/Agrardom/Sciencelab/Hangar, so once the
        // CC pre-invest hint (CC >= level 2), the advisor-slot-2 hint (slot filled),
        // and the explore hint (no fog left) are all exhausted, unused Bau-AP surfaces
        // hint_spend_remaining_ap (rank 16) rather than the true end-sol floor.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();   // CC level 2 → cc_invest silent
        $this->placeSecondAdvisor(); // fills CC2 slot → hint_advisor_slot2 silent
        $this->clearFog();    // every tile explored → explore silent

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(16, $hint['rank']);
        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
        $this->assertSame('colony.onboarding_hint_spend_ap_construction', $hint['text_key']);
    }

    public function test_end_sol_hint_fires_when_choice_buildings_placed_and_no_ap_left(): void
    {
        // Genuine "nothing left" state: Cantina/Agrardom/Analytik all placed AND
        // every AP pool exhausted — this is the only case hint_end_sol should win.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->clearFog();
        $this->exhaustAllActionPoints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(17, $hint['rank']);
        $this->assertSame('hint_end_sol', $hint['key']);
        $this->assertSame('colony.onboarding_end_sol', $hint['text_key']);
    }

    public function test_spend_remaining_ap_hint_silent_while_a_choice_building_is_missing(): void
    {
        // Only Cantina + Agrardom placed, Analytik still missing — even with idle
        // Bau-AP this must NOT fire; the missing must-have building hint wins instead.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->upgradeCc();
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 52,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 5, 'tile_y' => 5,
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 41,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 6, 'tile_y' => 5,
        ]);
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_spend_remaining_ap', $hint['key']);
    }

    public function test_spend_remaining_ap_hint_points_to_research_when_construction_exhausted(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->clearFog();

        $this->app->make(PersonellService::class)->lockActionPoints('construction', $this->colonyId, 9999);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
        $this->assertSame('colony.onboarding_hint_spend_ap_research', $hint['text_key']);
        $this->assertSame('/techtree', $hint['target_url']);
    }

    public function test_end_sol_bridge_hint_self_clears_after_sol_advance(): void
    {
        // Same Sol-1 state but one Sol later (current_tick 1): the bridge hint is
        // Sol-1-only and self-clears; hint_3 (CC upgrade, gate now 1) takes over.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->setRunTick(1);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_3', $hint['key']); // CC still level 1 → upgrade hint
    }

    // ── Hint Agrardom (bioFacility) ──────────────────────────────────────────

    public function test_agrardome_hint_fires_when_harvester_built_and_no_bio_facility(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc(); // silence hint_3 (CC lv1)
        $this->placeSecondAdvisor(); // silence hint_advisor_slot2
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete();
        $this->setRunTick(6);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_agrardome', $hint['key']);
    }

    public function test_agrardome_hint_silent_once_placed_even_if_still_under_construction(): void
    {
        // Regression: a building "in progress" (placed, level 0) still counts as
        // "handled" — the hint must not nag the player to build something they
        // already started just because it isn't finished yet this Sol.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)
            ->update(['level' => 0, 'tile_x' => 8, 'tile_y' => 8]);
        $this->setRunTick(6);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_agrardome', $hint['key'] ?? null);
    }

    public function test_agrardome_hint_silent_on_sol1_even_with_harvester_built(): void
    {
        // Regression: Agrardom has no CC-level prerequisite (unlike Cantina/Analytik),
        // so without an explicit tick floor it would fire on Sol 1 the moment Bau-AP
        // runs out — crowding out the "Sol beenden" bridge hint with an action the
        // player can no longer act on this Sol.
        // suppressLateHints() places Cantina/Agrardom/Sciencelab/Hangar so
        // allChoiceBuildingsPlaced() = true; exhausting ALL AP pools is required for
        // hint_end_sol to fire (otherwise hint_spend_remaining_ap wins on idle research AP).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete();
        $this->clearFog();
        $this->exhaustAllActionPoints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_agrardome', $hint['key'] ?? null);
        $this->assertSame('hint_end_sol', $hint['key'] ?? null);
    }

    public function test_agrardome_hint_silent_without_harvester(): void
    {
        // Past the tick gate (Sol 2+) and every higher-rank hint resolved — only the
        // Harvester>=1 prerequisite is left to keep this silent.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 27)->delete();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->setRunTick(1);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_agrardome', $hint['key'] ?? null);
    }

    public function test_agrardome_hint_silent_when_bio_facility_built(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints(); // builds bioFacility level 1 among others
        $this->setRunTick(6);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_agrardome', $hint['key'] ?? null);
    }

    // ── Hint Analytik-Labor (sciencelab) ─────────────────────────────────────

    public function test_analytik_hint_fires_when_cc_level2_and_no_sciencelab(): void
    {
        // Tested at CC3 due to path-gate: CC2 allows only 1 path building; with
        // Cantina already placed by suppressLateHints(), pathGateFree(31) at CC2
        // returns false (placed=1 >= cc-1=1). At CC3 with only Cantina placed
        // (Hangar deleted below), placed=1 < cc-1=2 → gate is free.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        // Upgrade CC to 3 (not 2) so pathGateFree(31) is true with 1 placed path building.
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['level' => 3, 'ap_spend' => 0]);
        $this->placeSecondAdvisor();
        // Fill the 3rd slot CC3 unlocks — otherwise hint_advisor_slot2 (rank 6) wins first.
        DB::table('advisors')->insertOrIgnore([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('trader'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 0,
        ]);
        // Delete sciencelab (the hint's subject) and hangar (suppress would leave 2
        // path buildings; with 2 placed at CC3 pathGateFree(31) is false).
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 44)->delete();
        // Dismiss hint_build_priority: hangar + analytik are both eligible at CC3 (2 ≥ 2),
        // which would outrank hint_analytik (rank 11 < rank 14) without this dismiss.
        $this->service->dismissHint($this->userId, 'hint_build_priority');
        $this->setRunTick(8);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_analytik', $hint['key']);
    }

    public function test_analytik_hint_silent_when_cc_below_level2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();
        $this->setRunTick(8);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_analytik', $hint['key'] ?? null);
    }

    public function test_analytik_hint_silent_when_sciencelab_built(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints(); // builds sciencelab level 1 among others
        $this->upgradeCc();
        $this->setRunTick(8);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_analytik', $hint['key'] ?? null);
    }

    // ── Hint build_priority: 2+ of Cantina/Agrardom/Analytik eligible at once ─

    public function test_build_priority_hint_fires_when_two_buildings_eligible(): void
    {
        // Default fixture: harvester>=1 (agrardome) + CC2 (analytik) both met,
        // neither placed — suppressLateHints() is deliberately NOT called here
        // (it would place all three, leaving 0 eligible).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]); // silence hint_4
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(11, $hint['rank']);
        $this->assertSame('hint_build_priority', $hint['key']);
        $this->assertSame('colony.onboarding_hint_build_priority', $hint['text_key']);
    }

    public function test_build_priority_hint_silent_when_only_one_eligible(): void
    {
        // CC stays level 1 → analytik prereq unmet, cantina prereq unmet (needs CC>=2
        // too) — only agrardome is eligible. Below the 2-candidate threshold.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints(); // places bar+bioFacility+sciencelab...
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete(); // ...un-place just agrardome
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_build_priority', $hint['key'] ?? null);
    }

    public function test_build_priority_hint_dismiss_falls_through_to_individual_hint(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]);
        $this->setRunTick(2);
        $this->service->dismissHint($this->userId, 'hint_build_priority');

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertNotSame('hint_build_priority', $hint['key']);
        $this->assertContains($hint['key'], ['hint_6', 'hint_agrardome', 'hint_analytik']);
    }

    // ── Build-affordability gate (Cantina/Agrardom/Analytik) ────────────────
    // Regression: these hints must not nag the player to build something they
    // can no longer afford this Sol (Bau-AP or Regolith already spent on an
    // earlier hint's building) — same bug class as the fixed Sol-1 Agrardom leak.

    public function test_analytik_hint_silent_when_not_enough_regolith(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)->where('resource_id', 3)->update(['amount' => 10]); // sciencelab needs 80
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_analytik', $hint['key'] ?? null);
    }

    public function test_analytik_hint_silent_when_no_construction_ap_left(): void
    {
        $this->app->instance(TickService::class, new TickService(0));
        $service = $this->app->make(OnboardingHintService::class);
        $personell = $this->app->make(PersonellService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();

        $available = $personell->getConstructionPoints($this->colonyId);
        $personell->lockActionPoints('construction', $this->colonyId, $available);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_analytik', $hint['key'] ?? null);
    }

    public function test_cantina_hint_silent_when_not_enough_regolith(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 52)->delete();
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)->where('resource_id', 3)->update(['amount' => 10]); // bar needs 50

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_6', $hint['key'] ?? null);
    }

    public function test_agrardome_hint_silent_when_not_enough_regolith(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete();
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)->where('resource_id', 3)->update(['amount' => 10]); // bioFacility needs 40
        $this->setRunTick(1);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_agrardome', $hint['key'] ?? null);
    }

    // ── Rank priority ─────────────────────────────────────────────────────────

    public function test_higher_rank_wins_over_lower_rank(): void
    {
        // hint_1 (no engineer), hint_2 (harvester in colony zone), hint_3 (CC lv1, Sol>=2) all active.
        $this->setRunTick(2);
        // No engineer and harvester still in colony zone — rank 1 must win.

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertEquals(1, $hint['rank'], 'Rank 1 must win over rank 2 and 3');
    }

    // ── Dismiss ───────────────────────────────────────────────────────────────

    public function test_dismiss_hint_saves_to_preferences(): void
    {
        $this->service->dismissHint($this->userId, 'hint_1');

        $raw = DB::table('user_preferences')->where('user_id', $this->userId)->value('dismissed_hints');
        $dismissed = json_decode($raw, true);
        $this->assertContains('hint_1', $dismissed);
    }

    public function test_dismiss_hint_is_idempotent(): void
    {
        $this->service->dismissHint($this->userId, 'hint_1');
        $this->service->dismissHint($this->userId, 'hint_1');

        $raw = DB::table('user_preferences')->where('user_id', $this->userId)->value('dismissed_hints');
        $dismissed = json_decode($raw, true);
        $this->assertCount(1, array_filter($dismissed, fn ($k) => $k === 'hint_1'));
    }

    public function test_dismissed_hint_skipped_returns_next_active(): void
    {
        // Dismiss hint_1 (engineer); urgent + teaching repair hints silent (buildings
        // full), so hint_2 (Harvester in colony zone, rank 3) surfaces next.
        $this->service->dismissHint($this->userId, 'hint_1');

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(3, $hint['rank']);
        $this->assertEquals('hint_2', $hint['key']);
    }

    public function test_all_hints_dismissed_returns_null(): void
    {
        foreach (['hint_1', 'hint_repair_urgent', 'hint_repair', 'hint_2', 'hint_3', 'hint_advisor_slot2', 'hint_cc_invest', 'hint_explore', 'hint_4', 'hint_5', 'hint_6', 'hint_agrardome', 'hint_end_sol'] as $key) {
            $this->service->dismissHint($this->userId, $key);
        }
        $this->setRunTick(99);

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_returns_correct_text_key_and_target_url(): void
    {
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertArrayHasKey('text_key', $hint);
        $this->assertArrayHasKey('target_url', $hint);
        $this->assertEquals('colony.onboarding_hint_1', $hint['text_key']);
        $this->assertEquals('/advisors', $hint['target_url']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function moveHarvesterOutside(): void
    {
        // Move Harvester to pre-explored ring-3 tile (3,0) — colony_zone=0, regolith_normal.
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 27)
            ->update(['tile_x' => 3, 'tile_y' => 0]);
    }

    /** Drop a building below its max status points so the repair hint activates. */
    private function damageBuilding(int $buildingId): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $buildingId)
            ->update(['status_points' => 16]);
    }

    private function upgradeCc(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['level' => 2, 'ap_spend' => 0]);
    }

    private function setRunTick(int $tick): void
    {
        DB::table('runs')
            ->where('colony_id', $this->colonyId)
            ->update(['current_tick' => $tick]);
    }

    /** Mark every colony tile as explored so the explore hint self-clears (no fog left). */
    private function clearFog(): void
    {
        DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->update(['is_explored' => 1]);
    }

    /** Locks every AP pool (construction/research/navigation/economy) so none remain unspent. */
    private function exhaustAllActionPoints(): void
    {
        $personellService = $this->app->make(PersonellService::class);
        foreach (['construction', 'research', 'navigation', 'economy'] as $type) {
            $personellService->lockActionPoints($type, $this->colonyId, 9999);
        }
    }

    /**
     * Suppress hints 4, 5, 6, agrardome, analytik and hangar_path so they don't
     * interfere with lower-rank tests. Places Cantina/Agrardom/Sciencelab/Hangar —
     * the three path buildings plus Agrardom — so allChoiceBuildingsPlaced() returns
     * true and hint_spend_remaining_ap can surface as the Sol-1 floor hint.
     */
    private function suppressLateHints(): void
    {
        // hint 4: knowledge present
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]);
        // hint 5: trust above threshold
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', 12)
            ->update(['amount' => 0]);
        // hint 6: cantina (path building) placed — placed_at_tick required for slot logic
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 52,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 5, 'tile_y' => 5, 'placed_at_tick' => 1,
        ]);
        // hint_agrardome: bioFacility placed (not a path building, no placed_at_tick)
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 41,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 6, 'tile_y' => 5,
        ]);
        // hint_analytik: sciencelab (path building) placed
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 31,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 7, 'tile_y' => 5, 'placed_at_tick' => 2,
        ]);
        // hint_hangar_path: hangar (path building) placed — required for allChoiceBuildingsPlaced()
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 44,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 8, 'tile_y' => 5, 'placed_at_tick' => 3,
        ]);
    }

    private function placeEngineer(): void
    {
        DB::table('advisors')->insertOrIgnore([
            'user_id' => $this->userId,
            'personell_id' => 35,
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 0,
        ]);
    }

    /** Fills the second advisor slot (CC2) so hint_advisor_slot2 doesn't outrank tests below it. */
    private function placeSecondAdvisor(): void
    {
        DB::table('advisors')->insertOrIgnore([
            'user_id' => $this->userId,
            'personell_id' => PersonellService::idFor('scientist'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 0,
        ]);
    }
}
