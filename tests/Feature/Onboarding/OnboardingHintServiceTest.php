<?php

namespace Tests\Feature\Onboarding;

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
    private TickService $tickService;

    private int $userId   = 999;
    private int $colonyId = 999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->service     = $this->app->make(OnboardingHintService::class);
        $this->tickService = $this->app->make(TickService::class);

        // Baseline: user + colony.
        // Reuse glx_system_objects id=3 (free — not used by testdata colonies 1/2).
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
            'system_object_id' => 3, 'name' => 'TestColony',
            'spot' => 1, 'since_tick' => 1, 'is_primary' => 1,
        ]);

        // Baseline preferences: onboarding enabled, nothing dismissed.
        DB::table('user_preferences')->insertOrIgnore([
            'user_id' => $this->userId, 'onboarding_hints' => 1, 'dismissed_hints' => null,
        ]);

        // Moral (resource_id=12) above threshold.
        DB::table('colony_resources')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'resource_id' => 12, 'amount' => 0,
        ]);

        // Tick well below all thresholds by default.
        $this->tickService->setTickCount(1);
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

    // ── Hint 1: no housing placed ─────────────────────────────────────────────

    public function test_hint_1_fires_when_no_housing_placed(): void
    {
        // No housing rows at all.
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank']);
        $this->assertEquals('hint_1', $hint['key']);
    }

    public function test_hint_1_silent_when_housing_placed(): void
    {
        // Housing with tile_x set = placed on the map.
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 1, 'tile_y' => 0,
        ]);

        // With tick=1, hint 2 also shouldn't fire (below threshold).
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNull($hint);
    }

    public function test_hint_1_requires_tile_x_to_be_set(): void
    {
        // Housing row exists but NOT placed (tile_x = null).
        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 0, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => null, 'tile_y' => null,
        ]);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(1, $hint['rank'], 'Unplaced housing must not satisfy hint 1');
    }

    // ── Hint 2: no engineer ───────────────────────────────────────────────────

    public function test_hint_2_fires_after_tick_threshold(): void
    {
        $this->placeHousing();
        $this->tickService->setTickCount(15); // above threshold of 3

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
    }

    public function test_hint_2_silent_before_tick_threshold(): void
    {
        $this->placeHousing();
        $this->tickService->setTickCount(1); // below threshold

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_hint_2_silent_when_engineer_is_present(): void
    {
        $this->placeHousing();
        $this->tickService->setTickCount(15);
        $this->placeEngineer();
        $this->suppressHints4and5(); // prevent hint_4 from surfacing at tick=15

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Hint 3: harvester on wrong tile ──────────────────────────────────────

    public function test_hint_3_fires_when_harvester_on_terrain(): void
    {
        $this->placeHousing();
        $this->placeEngineer();
        $this->tickService->setTickCount(15);

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
        $this->placeHousing();
        $this->placeEngineer();
        $this->tickService->setTickCount(15);
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
        $this->placeHousing();
        $this->placeEngineer();
        $this->tickService->setTickCount(15);
        $this->suppressHints4and5();
        // No harvester row at all.

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    // ── Rank priority ─────────────────────────────────────────────────────────

    public function test_higher_rank_wins_over_lower_rank(): void
    {
        // Hint 1 active (no housing) and hint 3 would also be active — rank 1 wins.
        $this->tickService->setTickCount(15);

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
        // Dismiss hint_1; hint_2 should surface (tick above threshold, no engineer).
        $this->service->dismissHint($this->userId, 'hint_1');
        $this->tickService->setTickCount(15);

        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertEquals(2, $hint['rank']);
    }

    public function test_all_hints_dismissed_returns_null(): void
    {
        foreach (['hint_1', 'hint_2', 'hint_3', 'hint_4', 'hint_5'] as $key) {
            $this->service->dismissHint($this->userId, $key);
        }
        $this->tickService->setTickCount(99);

        $this->assertNull($this->service->getActiveHint($this->colonyId, $this->userId));
    }

    public function test_returns_correct_text_key_and_target_url(): void
    {
        $hint = $this->service->getActiveHint($this->colonyId, $this->userId);

        $this->assertNotNull($hint);
        $this->assertArrayHasKey('text_key', $hint);
        $this->assertArrayHasKey('target_url', $hint);
        $this->assertEquals('colony.onboarding_hint_1', $hint['text_key']);
        $this->assertEquals('/colony/view', $hint['target_url']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function placeHousing(): void
    {
        DB::table('colony_buildings')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'building_id' => 28,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 1, 'tile_y' => 0,
        ]);
    }

    /** Insert a knowledge research at level 1 and set trust above threshold so hints 4+5 stay silent. */
    private function suppressHints4and5(): void
    {
        DB::table('colony_researches')->insertOrIgnore([
            'colony_id' => $this->colonyId, 'research_id' => 90, 'level' => 1,
            'status_points' => 20, 'ap_spend' => 0,
        ]);
        DB::table('colony_resources')
            ->where('colony_id', $this->colonyId)
            ->where('resource_id', 12)
            ->update(['amount' => 0]); // trust = 0, above -20 threshold
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
