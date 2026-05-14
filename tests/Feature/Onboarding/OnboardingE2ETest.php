<?php

namespace Tests\Feature\Onboarding;

use App\Models\InnnEvent;
use App\Services\EventService;
use App\Services\OnboardingHintService;
use App\Services\OnboardingService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * End-to-end onboarding flow (Schritt 7).
 *
 * Covers: setupNewPlayer → nexus briefing → hint rank 1 → build housing →
 * hint rank 2 → dismiss → hint advances → disable toggle → hint = null.
 *
 * All steps use service calls only (no HTTP) to keep the test fast and
 * deterministic without the full middleware stack.
 */
class OnboardingE2ETest extends TestCase
{
    use RefreshDatabase;

    private OnboardingService  $onboardingService;
    private OnboardingHintService $hintService;
    private TickService        $tickService;

    /** User ID that does not collide with any testdata fixture. */
    private int $userId = 7001;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->onboardingService = $this->app->make(OnboardingService::class);
        $this->hintService       = $this->app->make(OnboardingHintService::class);
        $this->tickService       = $this->app->make(TickService::class);

        DB::table('user')->insertOrIgnore([
            'user_id'        => $this->userId,
            'username'       => 'e2e_player',
            'display_name'   => 'E2E Player',
            'role'           => 'player',
            'password'       => bcrypt('pw'),
            'email'          => 'e2e@test.local',
            'activation_key' => 'e2ekey001',
            'faction_id'     => 7,
        ]);
    }

    /**
     * Full happy-path: new run → briefing → rank-1 hint → build housing →
     * rank-2 hint → disable toggle → null.
     */
    public function test_full_onboarding_flow_new_run_to_disabled(): void
    {
        // ── 1. Setup new player ──────────────────────────────────────────────
        $colony = $this->onboardingService->setupNewPlayer($this->userId, 'E2E-Kolonie');
        $this->assertNotNull($colony);

        // ── 2. Nexus-Briefing exists in INNN ────────────────────────────────
        $this->assertDatabaseHas('innn_events', [
            'user'  => $this->userId,
            'event' => 'onboarding.nexus_briefing',
        ]);

        // ── 3. Rank-1 hint active (no housing complex placed) ───────────────
        $hint = $this->hintService->getActiveHint($colony->id, $this->userId);
        $this->assertNotNull($hint, 'Rank-1 hint should be active after setup');
        $this->assertSame(1, $hint['rank']);
        $this->assertSame('hint_1', $hint['key']);
        $this->assertStringContainsString('/colony', $hint['target_url']);

        // ── 4. Place housing complex → rank-1 condition resolved ─────────────
        // Need a colony tile to place on (tile_x must be NOT NULL for hint check).
        DB::table('colony_tiles')->insertOrIgnore([
            'colony_id' => $colony->id,
            'q'         => 1,
            'r'         => 0,
            'tile_type' => 'terrain_plains',
            'is_colony_zone' => 1,
            'is_explored'    => 1,
        ]);

        DB::table('colony_buildings')->insert([
            'colony_id'     => $colony->id,
            'building_id'   => 28,   // housingComplex
            'level'         => 1,
            'status_points' => 20,
            'ap_spend'      => 0,
            'tile_x'        => 1,
            'tile_y'        => 0,
        ]);

        // ── 5. Rank-2 hint active (no engineer, tick above threshold) ────────
        $threshold = (int) config('game.onboarding.hint_no_engineer_ticks', 3);
        $this->tickService->setTickCount($threshold + 1);

        $hint = $this->hintService->getActiveHint($colony->id, $this->userId);
        $this->assertNotNull($hint, 'Rank-2 hint should appear after housing is placed');
        $this->assertSame(2, $hint['rank']);
        $this->assertSame('hint_2', $hint['key']);
        $this->assertStringContainsString('/techtree', $hint['target_url']);

        // ── 6. Disable onboarding hints → no hint returned ──────────────────
        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => $this->userId],
            ['onboarding_hints' => 0]
        );

        $this->assertNull(
            $this->hintService->getActiveHint($colony->id, $this->userId),
            'No hint should be returned when onboarding_hints = 0'
        );
    }

    /**
     * Dismissing rank-1 hint while it is active advances past it.
     * After dismissal, hint_1 must not reappear even though the condition is still true.
     */
    public function test_dismissing_hint_advances_past_dismissed_rank(): void
    {
        $colony = $this->onboardingService->setupNewPlayer($this->userId, 'Dismiss-Test');

        // Rank 1 is active (no housing).
        $hint = $this->hintService->getActiveHint($colony->id, $this->userId);
        $this->assertSame('hint_1', $hint['key']);

        // Dismiss it.
        $this->hintService->dismissHint($this->userId, 'hint_1');

        // hint_1 condition still true but it's dismissed — must not be returned again.
        $hint = $this->hintService->getActiveHint($colony->id, $this->userId);
        if ($hint !== null) {
            $this->assertNotSame('hint_1', $hint['key'], 'Dismissed hint must not reappear');
        }
    }

    /**
     * Nexus-Briefing is created exactly once per user even if the factory is
     * called a second time (idempotency guard).
     */
    public function test_nexus_briefing_is_created_exactly_once(): void
    {
        $colony = $this->onboardingService->setupNewPlayer($this->userId, 'Briefing-Test');

        $eventService = $this->app->make(EventService::class);
        $tick         = $this->tickService->getTickCount();

        // Second call must be a no-op.
        $eventService->createNexusBriefing($this->userId, $tick, $colony->id);

        $count = InnnEvent::where('user', $this->userId)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();

        $this->assertSame(1, $count, 'Nexus briefing must be idempotent');
    }

    /**
     * When onboarding_hints preference is missing entirely (no DB row),
     * the hint service defaults to enabled and returns a hint.
     */
    public function test_hint_defaults_to_enabled_when_no_preferences_row(): void
    {
        $colony = $this->onboardingService->setupNewPlayer($this->userId, 'NoPref-Test');

        // Ensure no preferences row exists.
        DB::table('user_preferences')->where('user_id', $this->userId)->delete();

        $hint = $this->hintService->getActiveHint($colony->id, $this->userId);
        $this->assertNotNull($hint, 'Hints should be active by default when no prefs row exists');
    }
}
