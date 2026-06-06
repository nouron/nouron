<?php

namespace Tests\Feature\Onboarding;

use App\Models\ColonyLog;
use App\Models\User;
use App\Services\EventService;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the Nexus Briefing onboarding event.
 *
 * Covered scenarios:
 *   - setupNewPlayer() creates exactly one onboarding.nexus_briefing event
 *   - nexus_briefing event has area='nexus' and parameters containing colony_id
 *   - createNexusBriefing() is idempotent — calling it twice yields one event
 *   - Two different players each receive their own independent briefing event
 */
class NexusBriefingTest extends TestCase
{
    use RefreshDatabase;

    private OnboardingService $onboardingService;
    private EventService $eventService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->onboardingService = $this->app->make(OnboardingService::class);
        $this->eventService      = $this->app->make(EventService::class);
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_setup_new_player_creates_nexus_briefing(): void
    {
        $user = User::factory()->create();

        $colony = $this->onboardingService->setupNewPlayer($user->user_id, 'TestColony');

        $events = ColonyLog::where('user', $user->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->get();

        $this->assertCount(1, $events, 'Exactly one nexus_briefing event must be created');

        $event = $events->first();
        $this->assertEquals('nexus', $event->area, 'Event area must be "nexus"');

        $params = json_decode($event->parameters, true);
        $this->assertIsArray($params, 'Parameters must be a JSON-encoded array');
        $this->assertArrayHasKey('colony_id', $params, 'Parameters must contain colony_id');
        $this->assertEquals($colony->id, $params['colony_id'], 'colony_id must match the created colony');
    }

    // ── Idempotency ───────────────────────────────────────────────────────────

    public function test_nexus_briefing_is_idempotent(): void
    {
        $user = User::factory()->create();

        // Call createNexusBriefing twice with the same user
        $this->eventService->createNexusBriefing($user->user_id, 1, 999);
        $this->eventService->createNexusBriefing($user->user_id, 2, 999);

        $count = ColonyLog::where('user', $user->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();

        $this->assertEquals(1, $count, 'Duplicate nexus_briefing events must not be created');
    }

    public function test_setup_new_player_called_twice_only_creates_one_briefing(): void
    {
        // Simulate a scenario where onboarding is triggered a second time for the
        // same user (e.g. via a replayed or retried request).
        $user = User::factory()->create();

        // First setup — creates colony and event normally
        $this->onboardingService->setupNewPlayer($user->user_id, 'FirstColony');

        // Second setup — uses a different planet but must not duplicate the event
        $this->onboardingService->setupNewPlayer($user->user_id, 'SecondColony');

        $count = ColonyLog::where('user', $user->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();

        $this->assertEquals(1, $count, 'Second setupNewPlayer call must not add a second nexus_briefing');
    }

    // ── Multi-player isolation ────────────────────────────────────────────────

    public function test_second_player_gets_own_briefing(): void
    {
        $userAlpha = User::factory()->create();
        $userBeta  = User::factory()->create();

        $this->onboardingService->setupNewPlayer($userAlpha->user_id, 'AlphaColony');
        $this->onboardingService->setupNewPlayer($userBeta->user_id, 'BetaColony');

        $totalEvents = ColonyLog::where('event', 'onboarding.nexus_briefing')->count();
        $this->assertEquals(2, $totalEvents, 'Each player must have their own nexus_briefing event');

        $alphaCount = ColonyLog::where('user', $userAlpha->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();
        $this->assertEquals(1, $alphaCount, 'Alpha must have exactly 1 nexus_briefing');

        $betaCount = ColonyLog::where('user', $userBeta->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();
        $this->assertEquals(1, $betaCount, 'Beta must have exactly 1 nexus_briefing');
    }

    // ── Adversarial / edge cases ──────────────────────────────────────────────

    public function test_nexus_briefing_event_references_correct_colony(): void
    {
        // Two users with separate colonies — each event must point to its own colony_id
        $userAlpha = User::factory()->create();
        $userBeta  = User::factory()->create();

        $colonyAlpha = $this->onboardingService->setupNewPlayer($userAlpha->user_id, 'AlphaColony');
        $colonyBeta  = $this->onboardingService->setupNewPlayer($userBeta->user_id, 'BetaColony');

        $alphaEvent = ColonyLog::where('user', $userAlpha->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->first();

        $betaEvent = ColonyLog::where('user', $userBeta->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->first();

        $alphaParams = json_decode($alphaEvent->parameters, true);
        $betaParams  = json_decode($betaEvent->parameters, true);

        $this->assertEquals($colonyAlpha->id, $alphaParams['colony_id'], 'Alpha event must reference Alpha colony');
        $this->assertEquals($colonyBeta->id,  $betaParams['colony_id'],  'Beta event must reference Beta colony');
        $this->assertNotEquals($alphaParams['colony_id'], $betaParams['colony_id'], 'Two players must not share a colony_id in their briefings');
    }

    public function test_nexus_briefing_guard_does_not_overwrite_existing_event(): void
    {
        // Insert a briefing manually, then call createNexusBriefing — it must not
        // overwrite the original tick value, proving the guard short-circuits.
        $user = User::factory()->create();

        ColonyLog::create([
            'user'       => $user->user_id,
            'tick'       => 42,
            'event'      => 'onboarding.nexus_briefing',
            'area'       => 'nexus',
            'parameters' => json_encode(['colony_id' => 1]),
            'is_read'    => false,
            'created_at' => now(),
        ]);

        // Now call the service with a different tick — must not insert a new row
        $this->eventService->createNexusBriefing($user->user_id, 9999, 1);

        $events = ColonyLog::where('user', $user->user_id)
            ->where('event', 'onboarding.nexus_briefing')
            ->get();

        $this->assertCount(1, $events, 'Guard must prevent a second row from being inserted');
        $this->assertEquals(42, $events->first()->tick, 'Original tick value must be preserved');
    }
}
