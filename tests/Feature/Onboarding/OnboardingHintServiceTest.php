<?php

namespace Tests\Feature\Onboarding;

use App\Services\AdvisorService;
use App\Services\OnboardingHintService;
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

        // Hint 1 is silent; suppressLateHints() places all buildings finished, so
        // neither the Agrardom hint nor invest_site (no active site) fires — the
        // explore hint is the Sol-1 floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_explore', $hint['key']);
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
        // SP=4 is above the urgent threshold (3) but below the 70% display threshold
        // → urgent silent; teaching repair (rank 5) wins instead. suppressLateHints()
        // places the Agrardom so its build hint (rank 4) doesn't outrank repair.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
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
        // building is ignored. With no Agrardom placed, its build hint (rank 4,
        // the colony's first project from Sol 1) is the floor.
        $this->assertSame('hint_agrardome', $hint['key']);
    }

    // ── Repair hint: any building below max status ───────────────────────────

    public function test_repair_hint_fires_when_building_below_display_threshold(): void
    {
        // A building at 13/20 (65%) is below the 70% display threshold → the
        // teaching repair hint (rank 5) fires. suppressLateHints() places the
        // Agrardom so its build hint (rank 4) doesn't outrank it.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->damageBuilding(25, 13);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(5, $hint['rank']);
        $this->assertEquals('hint_repair', $hint['key']);
        $this->assertEquals('colony.onboarding_hint_repair', $hint['text_key']);
        $this->assertEquals('/colony/view', $hint['target_url']);
    }

    public function test_repair_hint_silent_above_display_threshold(): void
    {
        // Core of the invisible-pacing-timer design (playtest review 2026-07-14):
        // the 16/20 (80%) starting damage sits ABOVE the 70% display threshold —
        // the repair hint must stay silent until natural decay pushes a building
        // below it (~Sol 4 for the Harvester).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->damageBuilding(25); // 16/20 = 80%

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_repair', $hint['key'] ?? null);
    }

    public function test_repair_hint_fires_from_sol_1_without_tick_gate(): void
    {
        // No tick gate: fires even at Sol 0 (run current_tick stays 0 from setUp)
        // as long as a building is below the display threshold.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->damageBuilding(28, 13);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals('hint_repair', $hint['key']);
    }

    public function test_repair_hint_silent_when_all_buildings_full(): void
    {
        // Baseline buildings are full (20/20) → repair hint silent; with everything
        // placed by suppressLateHints() and no active site, the explore hint shows.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_explore', $hint['key']);
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

        // Hint 2 silent; everything placed by suppressLateHints(), no active site →
        // the explore hint is the floor.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_explore', $hint['key']);
    }

    public function test_hint_2_silent_when_no_harvester_placed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 27)
            ->update(['tile_x' => null, 'tile_y' => null]);

        $this->placeEngineer();
        $this->suppressLateHints();

        // Hint 2 silent (no harvester tile); everything placed, no active site →
        // explore hint fills the bar.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertSame('hint_explore', $hint['key']);
    }

    // ── Hint 3: CC level < 2 (fires from Sol 2) ──────────────────────────────

    public function test_hint_3_fires_when_ramp_done_and_cc_level_1(): void
    {
        // State-based gate: Agrardom >= Lv1 AND a path building >= Lv1 (both via
        // suppressLateHints()), CC still level 1, tick >= floor (2) → hint_3 fires.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(12, $hint['rank']);
        $this->assertEquals('hint_3', $hint['key']);
    }

    public function test_hint_3_silent_before_tick_floor(): void
    {
        // All state conditions met (Agrardom + path building via suppressLateHints)
        // but tick 1 < floor (2) → hint_3 stays silent.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->setRunTick(1);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertNotSame('hint_3', $hint['key'] ?? null);
    }

    public function test_hint_3_silent_without_finished_path_building(): void
    {
        // Agrardom finished but NO path building yet → CC Lv2 wouldn't pay off
        // (advisor slot 2 couldn't be filled) — hint_3 must wait.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->whereIn('building_id', [31, 44, 52])
            ->update(['level' => 0]);
        $this->setRunTick(3);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);
        $this->assertNotSame('hint_3', $hint['key'] ?? null);
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
        $this->assertSame(7, $hint['rank']);
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

    public function test_advisor_slot2_hint_silent_without_path_building(): void
    {
        // Playtest regression (2026-07-11): CC2 frees a slot, but hiring into slots
        // 2-4 requires a path building (Sciencelab/Hangar/Cantina). Without one the
        // hint sent the player to /advisors where hiring fails with
        // path_building_missing — a dead end. Must stay silent so the path-build
        // hints (ranks 13-15) fire instead. NO suppressLateHints() here — that
        // helper places Cantina + Sciencelab.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->upgradeCc();

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

    // ── Hint invest_site: invest Bau-AP into the active construction project ──

    public function test_invest_site_hint_fires_for_placed_construction_site(): void
    {
        // Agrardom placed but unfinished (level 0) = active construction site;
        // Bau-AP available → invest_site (rank 6) fires. The Agrardom build hint
        // (rank 4) is silent because the building is already placed.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 41)
            ->update(['level' => 0, 'ap_spend' => 2, 'status_points' => 4]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(6, $hint['rank']);
        $this->assertSame('hint_invest_site', $hint['key']);
        $this->assertSame('colony.onboarding_hint_invest_site', $hint['text_key']);
        $this->assertSame('/colony/view', $hint['target_url']);
    }

    public function test_invest_site_hint_fires_for_started_cc_upgrade(): void
    {
        // A started CC upgrade (ap_spend > 0, no tile — the CC is anchored, not
        // placed) also counts as an active site.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['ap_spend' => 3]);
        $this->setRunTick(1);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_invest_site', $hint['key']);
    }

    public function test_invest_site_hint_silent_without_active_site(): void
    {
        // Everything placed and finished, nothing mid-levelup → no site, no hint;
        // the explore hint surfaces instead.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_explore', $hint['key']);
    }

    public function test_invest_site_hint_retires_once_cc_level_2(): void
    {
        // CC at level 2 → the build ramp is done; even with an open site the hint
        // stays retired.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 31)
            ->update(['level' => 0, 'ap_spend' => 1]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_invest_site', $hint['key'] ?? null);
    }

    public function test_invest_site_hint_silent_when_no_construction_ap(): void
    {
        // Fix the tick to 0 so lock + read share the same tick, then lock the full
        // construction AP pool → invest_site self-clears (Bau-AP exhausted).
        $this->app->instance(TickService::class, new TickService(0));
        $service = $this->app->make(OnboardingHintService::class);
        $personell = $this->app->make(AdvisorService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 41)
            ->update(['level' => 0, 'ap_spend' => 2]);

        $available = $personell->getAvailableActionPoints($this->colonyId);
        $this->assertGreaterThan(0, $available, 'precondition: AP available before lock');
        $personell->lockActionPoints($this->colonyId, $available);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertNotSame('hint_invest_site', $hint['key']);
    }

    public function test_agrardome_hint_fires_on_sol1_when_todos_done(): void
    {
        // New Sol-1 ramp (playtest review 2026-07-14): engineer hired, Harvester
        // relocated, no Agrardom placed → the Agrardom build hint (rank 4) is the
        // Sol-1 floor, ahead of everything else on the Bau-AP track.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        // Deliberately NO suppressLateHints() — that helper places a finished Agrardom.

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(4, $hint['rank']);
        $this->assertSame('hint_agrardome', $hint['key']);
    }

    // ── Hint explore: scout unexplored tiles (rank 8, Sol 1–3) ───────────────

    public function test_explore_hint_fires_on_sol1_when_cc_done_and_fog_remains(): void
    {
        // Engineer + Harvester done, CC already level 2 (invest_site retired), fog
        // present and Nav-AP available at Sol 1 → explore hint (rank 13) fires.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(13, $hint['rank']);
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
        $personell = $this->app->make(AdvisorService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();

        $available = $personell->getAvailableActionPoints($this->colonyId);
        $this->assertGreaterThan(1, $available, 'precondition: available AP must exceed 1 to test the lock-down');
        $personell->lockActionPoints($this->colonyId, $available - 1);

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
        // and the explore hint (no fog left) are all exhausted, unused AP surfaces
        // hint_spend_remaining_ap (rank 16) rather than the true end-sol floor.
        // A finished Sciencelab is among the placed buildings — bestRemainingApPool()
        // checks research first (no domain-amount comparison with one shared pool,
        // see OnboardingHintService::bestRemainingApPool docblock), so this points
        // to research, not construction.
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
        $this->assertSame('colony.onboarding_hint_spend_ap_research', $hint['text_key']);
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
        // "Construction exhausted" is no longer a distinct pool state (one
        // shared pool, GDD §13.1) — this now covers the plain case: a built
        // Sciencelab wins the priority chain regardless of remaining AP.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        $this->clearFog();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
        $this->assertSame('colony.onboarding_hint_spend_ap_research', $hint['text_key']);
        $this->assertSame('/techtree', $hint['target_url']);
    }

    public function test_end_sol_silent_while_usable_nav_ap_remains(): void
    {
        // Playtest finding (2026-07-14, Sol 2): "alle sinnvollen Aktionen getätigt"
        // while Nav-AP + affordable fog remained. With fog present and no
        // Sciencelab/Cantina built, spend_remaining_ap must surface the
        // navigation action instead of the end-sol fallback.
        $this->app->instance(TickService::class, new TickService(1));
        $service = $this->app->make(OnboardingHintService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        // Real Sol-2 state: no finished Sciencelab/Cantina yet → research and
        // economy are unusable, only navigation can act.
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->whereIn('building_id', [31, 52])
            ->update(['level' => 0]);
        $this->setRunTick(1); // Sol 2 — explore hint (Sol-1-only) is gone

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
        $this->assertSame('colony.onboarding_hint_spend_ap_navigation', $hint['text_key']);
    }

    public function test_spend_remaining_ap_skips_research_pool_without_sciencelab(): void
    {
        // Research is unusable without a built Sciencelab (techtree locked) —
        // it must not be suggested. With Cantina also unbuilt and the shared
        // pool fully locked, there is nothing left to point the player at.
        $this->app->instance(TickService::class, new TickService(1));
        $service = $this->app->make(OnboardingHintService::class);
        $personell = $this->app->make(AdvisorService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->whereIn('building_id', [31, 52])
            ->update(['level' => 0]); // sciencelab + cantina unusable
        $this->setRunTick(1);
        $personell->lockActionPoints($this->colonyId, 9999);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        // Sciencelab/Cantina both unbuilt (research/economy unusable) and the
        // shared pool fully locked → the genuine "nothing left" fallback wins.
        $this->assertNotNull($hint);
        $this->assertSame('hint_end_sol', $hint['key']);
    }

    public function test_spend_remaining_ap_points_to_cantina_when_built_and_economy_ap_idle(): void
    {
        // Playtest finding (2026-07-14, Sol 4): after CC Lv2 + Konsul hire the bar
        // said "Sol beenden" although the freshly built Cantina + idle economy AP
        // were waiting. Economy counts as usable once the Cantina is built and
        // both research (no Sciencelab here) and navigation (no fog) are ruled out.
        $this->app->instance(TickService::class, new TickService(3));
        $service = $this->app->make(OnboardingHintService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints(); // places a finished Cantina (52) among others
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)
            ->update(['level' => 0]); // sciencelab unbuilt → research unusable
        $this->clearFog(); // navigation unusable
        $this->setRunTick(3);

        $hint = $service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_spend_remaining_ap', $hint['key']);
        $this->assertSame('colony.onboarding_hint_spend_ap_economy', $hint['text_key']);
        $this->assertSame('/colony/bar', $hint['target_url']);
    }

    public function test_path_hints_silent_while_first_path_building_placed_and_cc_below_2(): void
    {
        // Playtest finding (2026-07-14, Sol 3): Cantina finished, but the bar
        // nagged "Kein Analytik-Labor". While the first path building exists and
        // the CC is still below level 2, the other path hints must stay silent —
        // hint_3 (CC Lv2 → advisor slot) takes over.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->placeAgrardome();
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 52,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 5, 'tile_y' => 5, 'placed_at_tick' => 1,
        ]);
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertNotContains($hint['key'], ['hint_analytik', 'hint_hangar_path', 'hint_6', 'hint_build_priority']);
        $this->assertSame('hint_3', $hint['key']);
    }

    public function test_hint_3_silent_once_cc_upgrade_started(): void
    {
        // Playtest finding (2026-07-14): starting the CC upgrade manually made the
        // "jetzt CC auf Level 2" hint appear AFTER the fact. Once ap_spend > 0 the
        // invest_site hint owns the guidance; hint_3 must stay silent.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->placeAgrardome();
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 52,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 5, 'tile_y' => 5, 'placed_at_tick' => 1,
        ]);
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['ap_spend' => 3]);
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotSame('hint_3', $hint['key'] ?? null);
    }

    public function test_end_sol_bridge_hint_self_clears_after_sol_advance(): void
    {
        // Same state two Sols later (current_tick 2, past hint_3's floor): the
        // bridge hint self-clears; hint_3 (CC upgrade — Agrardom + path building
        // stand via suppressLateHints) takes over.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->setRunTick(2);

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

    public function test_agrardome_hint_silent_when_ap_exhausted(): void
    {
        // The Agrardom hint fires from Sol 1 now, but never with an empty Bau-AP
        // pool (canAffordBuildingPlacement) — once every AP pool is spent it must
        // yield to the "Sol beenden" bridge instead of nagging about an action the
        // player can no longer take this Sol.
        // suppressLateHints() places Cantina/Sciencelab/Hangar so
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
            'personell_id' => AdvisorService::idFor('trader'),
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

    public function test_analytik_hint_silent_without_agrardome_placed(): void
    {
        // Path buildings require a placed Agrardom server-side — the hint must not
        // suggest a placement that would be rejected.
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 41)->delete();
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
        // Agrardom placed (path-building prereq) but no path building yet — all
        // three (Cantina/Analytik/Hangar) become eligible at tick >= 1, so the
        // "pick one" hint (rank 7) fires. suppressLateHints() is deliberately NOT
        // called (it would place all three, leaving 0 eligible).
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->placeAgrardome();
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]); // silence hint_4
        $this->setRunTick(2);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame(8, $hint['rank']);
        $this->assertSame('hint_build_priority', $hint['key']);
        $this->assertSame('colony.onboarding_hint_build_priority', $hint['text_key']);
    }

    public function test_build_priority_hint_silent_when_only_one_eligible(): void
    {
        // Agrardom NOT placed → all three path-building prereqs unmet (server-side
        // Agrardom gate) — zero eligible, hint silent.
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
        $this->placeAgrardome();
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]);
        $this->setRunTick(2);
        $this->service->dismissHint($this->userId, 'hint_build_priority');

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertNotSame('hint_build_priority', $hint['key']);
        $this->assertContains($hint['key'], ['hint_6', 'hint_analytik', 'hint_hangar_path']);
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
        $personell = $this->app->make(AdvisorService::class);

        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->suppressLateHints();
        $this->upgradeCc();
        $this->placeSecondAdvisor();
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)->where('building_id', 31)->delete();

        $available = $personell->getAvailableActionPoints($this->colonyId);
        $personell->lockActionPoints($this->colonyId, $available);

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
        foreach (['hint_1', 'hint_repair_urgent', 'hint_repair', 'hint_2', 'hint_3', 'hint_advisor_slot2', 'hint_cc_invest', 'hint_explore', 'hint_4', 'hint_5', 'hint_build_priority', 'hint_6', 'hint_agrardome', 'hint_analytik', 'hint_hangar_path', 'hint_spend_remaining_ap', 'hint_end_sol'] as $key) {
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

    /**
     * The `onboarding_encounter` trigger (fired once by GameTick when any of the
     * three danger types — Sturm/Instabilität/Seuche — triggers for the first
     * time, GDD §9) must surface a real hint-bar entry — previously it fired an
     * event with no corresponding hint registration, so it never reached the
     * player. Lowest rank (18): only surfaces once every higher-ranked hint is
     * dismissed.
     */
    public function test_encounter_trigger_fired_surfaces_hint_once_everything_else_is_dismissed(): void
    {
        foreach (['hint_1', 'hint_repair_urgent', 'hint_repair', 'hint_2', 'hint_3', 'hint_advisor_slot2', 'hint_cc_invest', 'hint_explore', 'hint_4', 'hint_5', 'hint_build_priority', 'hint_6', 'hint_agrardome', 'hint_analytik', 'hint_hangar_path', 'hint_spend_remaining_ap', 'hint_end_sol'] as $key) {
            $this->service->dismissHint($this->userId, $key);
        }
        $this->setRunTick(99);

        // Without the trigger fired, still no hint at all — same as the existing
        // "all dismissed" baseline (test_all_hints_dismissed_returns_null).
        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));

        DB::table('user_preferences')->where('user_id', $this->userId)
            ->update(['fired_triggers' => json_encode(['onboarding_encounter'])]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertSame('hint_encounter', $hint['key']);
        $this->assertSame(18, $hint['rank']);
        $this->assertSame('colony.onboarding_hint_encounter', $hint['text_key']);
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

    /**
     * Drop a building's status points. Default 16/20 (80%) mirrors the real game
     * start — deliberately ABOVE the 70% display threshold, so the repair hint
     * stays silent. Pass e.g. 13 (65%) to push below the threshold.
     */
    private function damageBuilding(int $buildingId, int $sp = 16): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $buildingId)
            ->update(['status_points' => $sp]);
    }

    /** Places a finished Agrardom (level 1) — path-building prereq without the full suppressLateHints(). */
    private function placeAgrardome(): void
    {
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 41,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 6, 'tile_y' => 5,
        ]);
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

    /** Locks the entire shared AP pool (GDD §13.1) so none remains unspent. */
    private function exhaustAllActionPoints(): void
    {
        $this->app->make(AdvisorService::class)->lockActionPoints($this->colonyId, 9999);
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
            'personell_id' => AdvisorService::idFor('scientist'),
            'colony_id' => $this->colonyId,
            'rank' => 1,
            'active_ticks' => 0,
        ]);
    }
}
