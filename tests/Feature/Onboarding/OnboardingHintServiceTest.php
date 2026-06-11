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

        // Baseline: user + colony.
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

        // Run at Sol 0 — keeps tick-gated hints (4/5/6) below their threshold.
        DB::table('runs')->insertOrIgnore([
            'id'           => $this->colonyId,
            'user_id'      => $this->userId,
            'colony_id'    => $this->colonyId,
            'current_tick' => 0,
            'status'       => 'active',
            'settings'     => json_encode(['tick_limit' => 100, 'bypass' => ['ap_checks' => false, 'resource_costs' => false, 'supply_checks' => false], 'supply_cap_max' => 200]),
        ]);

        // Baseline preferences: onboarding enabled, nothing dismissed.
        DB::table('user_preferences')->insertOrIgnore([
            'user_id' => $this->userId, 'onboarding_hints' => 1, 'dismissed_hints' => null,
        ]);

        // Trust (resource_id=12) above threshold.
        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 12, 'amount' => 0,
        ]);

        // Baseline buildings: CC and Housing at level 1 — mirrors real game start state.
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 25,
            'instance_id' => 1, 'level' => 1, 'status_points' => 16, 'ap_spend' => 0,
            'tile_x' => null, 'tile_y' => null,
        ]);
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 1, 'status_points' => 16, 'ap_spend' => 0,
            'tile_x' => 0, 'tile_y' => 1,
        ]);
    }

    // ── Guard: disabled / missing prefs ──────────────────────────────────────

    public function test_returns_hint_when_no_prefs_row(): void
    {
        // Missing row = hints enabled by default. Hint 1 should still fire.
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
        // Baseline: no advisor rows for this colony.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank']);
        $this->assertEquals('hint_1', $hint['key']);
    }

    public function test_hint_1_fires_even_when_cc_upgraded(): void
    {
        // Upgrading CC does NOT silence hint 1 — engineer is still missing.
        $this->upgradeCc();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank']);
    }

    public function test_hint_1_silent_when_engineer_present(): void
    {
        // Engineer present + CC upgraded + other hints suppressed → null.
        $this->placeEngineer();
        $this->upgradeCc();
        $this->suppressHints4and5();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNull($hint);
    }

    // ── Hint 2: CC still level 1 ─────────────────────────────────────────────

    public function test_hint_2_fires_when_cc_level_1(): void
    {
        // Engineer present (hint 1 resolved), CC at level=1 (start state) → hint 2 fires.
        $this->placeEngineer();

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
    }

    public function test_hint_2_silent_when_cc_level_2(): void
    {
        $this->placeEngineer();
        $this->upgradeCc();
        $this->suppressHints4and5();

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Hint 3: harvester on wrong tile ──────────────────────────────────────

    public function test_hint_3_fires_when_harvester_on_terrain(): void
    {
        $this->upgradeCc();
        $this->placeEngineer();
        $this->setRunTick(15);

        // Harvester placed on a terrain_empty tile.
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 27,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 2, 'tile_y' => 0,
        ]);
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 2, 'r' => 0, 'ring' => 2,
            'tile_type' => 'terrain_empty', 'is_explored' => 1,
            'is_colony_zone' => 1, 'is_deep_scanned' => 0,
        ]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(3, $hint['rank']);
    }

    public function test_hint_3_silent_when_harvester_on_regolith(): void
    {
        $this->upgradeCc();
        $this->placeEngineer();
        $this->setRunTick(15);
        $this->suppressHints4and5();

        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 27,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 2, 'tile_y' => 0,
        ]);
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 2, 'r' => 0, 'ring' => 2,
            'tile_type' => 'regolith_normal', 'is_explored' => 1,
            'is_colony_zone' => 0, 'is_deep_scanned' => 0,
        ]);

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_hint_3_silent_when_no_harvester_placed(): void
    {
        $this->upgradeCc();
        $this->placeEngineer();
        $this->setRunTick(15);
        $this->suppressHints4and5();
        // No harvester row at all.

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Rank priority ─────────────────────────────────────────────────────────

    public function test_higher_rank_wins_over_lower_rank(): void
    {
        // Hint 1 active (no engineer) and hint 3 would also be active — rank 1 wins.
        $this->setRunTick(15);

        // Place harvester on terrain to trigger hint 3 as well.
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 27,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 2, 'tile_y' => 0,
        ]);
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'q' => 2, 'r' => 0, 'ring' => 2,
            'tile_type' => 'terrain_empty', 'is_explored' => 1,
            'is_colony_zone' => 1, 'is_deep_scanned' => 0,
        ]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertEquals(1, $hint['rank'], 'Rank 1 must win over rank 3');
    }

    // ── Dismiss ───────────────────────────────────────────────────────────────

    public function test_dismiss_hint_saves_to_preferences(): void
    {
        $this->service->dismissHint($this->userId, 'hint_1');

        $raw = DB::table('user_preferences')
            ->where('user_id', $this->userId)
            ->value('dismissed_hints');

        $dismissed = json_decode($raw, true);
        $this->assertContains('hint_1', $dismissed);
    }

    public function test_dismiss_hint_is_idempotent(): void
    {
        $this->service->dismissHint($this->userId, 'hint_1');
        $this->service->dismissHint($this->userId, 'hint_1');

        $raw = DB::table('user_preferences')
            ->where('user_id', $this->userId)
            ->value('dismissed_hints');

        $dismissed = json_decode($raw, true);
        $this->assertCount(1, array_filter($dismissed, fn($k) => $k === 'hint_1'));
    }

    public function test_dismissed_hint_skipped_returns_next_active(): void
    {
        // Dismiss hint_1 (engineer); hint_2 (CC level < 2) should surface.
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
        // High Sol tick so tick-gated hints (4/5/6) are past their thresholds.
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

    private function upgradeCc(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 25)
            ->update(['level' => 2, 'ap_spend' => 0]);
    }

    private function placeHousing(): void
    {
        // Housing already starts at level=1; this is a no-op kept for test clarity.
        DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', 28)
            ->update(['level' => 1, 'ap_spend' => 0]);
    }

    private function setRunTick(int $tick): void
    {
        DB::table('runs')
            ->where('colony_id', $this->colonyId)
            ->update(['current_tick' => $tick]);
    }

    /** Suppress hints 4, 5, and 6 so they don't interfere with lower-rank tests. */
    private function suppressHints4and5(): void
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
            ->update(['amount' => 0]); // trust = 0, above -20 threshold
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
            'personell_id' => 35, // engineer — config('advisors.engineer.id')
            'colony_id'    => $this->colonyId,
            'rank'         => 1,
            'active_ticks' => 0,
        ]);
    }
}
