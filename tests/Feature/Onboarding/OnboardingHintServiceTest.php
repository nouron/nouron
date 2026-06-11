<?php

namespace Tests\Feature\Onboarding;

use App\Services\OnboardingHintService;
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

    private int $userId   = 999;
    private int $colonyId = 999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->service = $this->app->make(OnboardingHintService::class);

        DB::table('user')->insertOrIgnore([
            'user_id'        => $this->userId,
            'username'       => 'TestUser',
            'display_name'   => 'Test User',
            'role'           => 'player',
            'password'       => bcrypt('pw'),
            'email'          => 'test@test.de',
            'activation_key' => 'testkey',
            'faction_id'     => 7,
        ]);
        DB::table('glx_colonies')->insertOrIgnore([
            'id' => $this->colonyId, 'user_id' => $this->userId,
            'system_object_id' => null, 'name' => 'TestColony',
            'spot' => 1, 'since_tick' => 1, 'is_primary' => 1,
        ]);

        // Run at Sol 0 — keeps tick-gated hints (3/4/5/6) below their thresholds.
        DB::table('runs')->insertOrIgnore([
            'id'           => $this->colonyId,
            'user_id'      => $this->userId,
            'colony_id'    => $this->colonyId,
            'current_tick' => 0,
            'status'       => 'active',
            'settings'     => json_encode(['tick_limit' => 100, 'bypass' => ['ap_checks' => false, 'resource_costs' => false, 'supply_checks' => false], 'supply_cap_max' => 200]),
        ]);

        DB::table('user_preferences')->insertOrIgnore([
            'user_id' => $this->userId, 'onboarding_hints' => 1, 'dismissed_hints' => null,
        ]);

        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 12, 'amount' => 0,
        ]);

        // Baseline buildings: CC, Harvester, Housing at level 1 — mirrors real game start state.
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 25,
            'instance_id' => 1, 'level' => 1, 'status_points' => 16, 'ap_spend' => 0,
            'tile_x' => null, 'tile_y' => null,
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 27,
            'instance_id' => 1, 'level' => 1, 'status_points' => 16, 'ap_spend' => 0,
            'tile_x' => 1, 'tile_y' => 0, // ring 1 — inside colony zone
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 1, 'status_points' => 16, 'ap_spend' => 0,
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
        // Sol=0 → hint 3 (CC upgrade) below threshold → silent
        $this->suppressLateHints();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Hint 2: harvester inside colony zone ─────────────────────────────────

    public function test_hint_2_fires_when_harvester_in_colony_zone(): void
    {
        // Engineer hired (hint 1 resolved); harvester at (1,0) = colony_zone=1 → hint 2 fires.
        $this->placeEngineer();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
        $this->assertEquals('hint_2', $hint['key']);
    }

    public function test_hint_2_silent_when_harvester_outside_colony_zone(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside(); // tile (2,0) = colony_zone=0
        // Sol=0 → hint 3 threshold not met → silent
        $this->suppressLateHints();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_hint_2_silent_when_no_harvester_placed(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 27)
            ->update(['tile_x' => null, 'tile_y' => null]);

        $this->placeEngineer();
        // Sol=0 → hint 3 silent
        $this->suppressLateHints();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Hint 3: CC level < 2 (fires from Sol 2) ──────────────────────────────

    public function test_hint_3_fires_when_cc_level_1_after_sol2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->setRunTick(2); // meet Sol threshold

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(3, $hint['rank']);
        $this->assertEquals('hint_3', $hint['key']);
    }

    public function test_hint_3_silent_before_sol2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        // Sol stays at 0 (below threshold of 2)
        $this->suppressLateHints();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_hint_3_silent_when_cc_level_2(): void
    {
        $this->placeEngineer();
        $this->moveHarvesterOutside();
        $this->setRunTick(2);
        $this->upgradeCc();
        $this->suppressLateHints();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
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

        $raw      = DB::table('user_preferences')->where('user_id', $this->userId)->value('dismissed_hints');
        $dismissed = json_decode($raw, true);
        $this->assertContains('hint_1', $dismissed);
    }

    public function test_dismiss_hint_is_idempotent(): void
    {
        $this->service->dismissHint($this->userId, 'hint_1');
        $this->service->dismissHint($this->userId, 'hint_1');

        $raw      = DB::table('user_preferences')->where('user_id', $this->userId)->value('dismissed_hints');
        $dismissed = json_decode($raw, true);
        $this->assertCount(1, array_filter($dismissed, fn($k) => $k === 'hint_1'));
    }

    public function test_dismissed_hint_skipped_returns_next_active(): void
    {
        // Dismiss hint_1 (engineer); hint_2 (Harvester in colony zone) surfaces.
        $this->service->dismissHint($this->userId, 'hint_1');

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
    }

    public function test_all_hints_dismissed_returns_null(): void
    {
        foreach (['hint_1', 'hint_2', 'hint_3', 'hint_4', 'hint_5', 'hint_6'] as $key) {
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

    /** Suppress hints 4, 5, and 6 so they don't interfere with lower-rank tests. */
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
        // hint 6: cantina exists
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 52,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => null, 'tile_y' => null,
        ]);
    }

    private function placeEngineer(): void
    {
        DB::table('advisors')->insertOrIgnore([
            'user_id'      => $this->userId,
            'personell_id' => 35,
            'colony_id'    => $this->colonyId,
            'rank'         => 1,
            'active_ticks' => 0,
        ]);
    }
}
