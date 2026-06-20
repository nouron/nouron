<?php

namespace Tests\Feature;

/**
 * HTTP endpoint tests for LobbyController::newRun().
 *
 * Covered scenarios:
 *   POST /run/new
 *     - new_run_requires_authentication
 *     - new_run_redirects_when_active_run_already_exists
 *     - new_run_creates_run_and_resets_colony
 *     - new_run_resets_research_levels
 *     - new_run_resets_tiles_to_unexplored
 *     - new_run_releases_advisors
 *     - new_run_seeds_starting_buildings
 */

use App\Models\Run;
use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LobbyNewRunTest extends TestCase
{
    use RefreshDatabase;

    protected int $userId = 3;

    protected int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Ensure no active runs exist before each test.
        DB::table('runs')->where('user_id', $this->userId)->delete();

        // Ensure user_resources row exists for this user.
        DB::table('user_resources')->updateOrInsert(
            ['user_id' => $this->userId],
            ['credits' => 10000, 'supply' => 20]
        );
    }

    private function user(): User
    {
        return User::where('user_id', $this->userId)->firstOrFail();
    }

    public function test_new_run_requires_authentication(): void
    {
        $this->post(route('run.new'))
            ->assertRedirect(route('login'));
    }

    public function test_new_run_redirects_when_active_run_already_exists(): void
    {
        Run::create([
            'user_id' => $this->userId,
            'colony_id' => $this->colonyId,
            'current_tick' => 5,
            'status' => 'active',
            'phase' => 1,
        ]);

        $response = $this->actingAs($this->user())->post(route('run.new'));

        $response->assertRedirect(route('lobby'));
        $response->assertSessionHas('error');

        // No additional run must have been created.
        $this->assertEquals(1, Run::where('user_id', $this->userId)->count());
    }

    public function test_new_run_creates_run_record_and_redirects(): void
    {
        $response = $this->actingAs($this->user())->post(route('run.new'));

        $response->assertRedirect(route('lobby'));
        $response->assertSessionHas('success');

        $run = Run::where('user_id', $this->userId)->where('status', 'active')->first();
        $this->assertNotNull($run, 'A new active Run must be created');
        $this->assertEquals(1, $run->phase);
        $this->assertEquals(3000, $run->nexus_debt);
        $this->assertNull($run->started_at, 'New run must be pending (started_at = null)');
    }

    public function test_new_run_resets_research_levels_to_zero(): void
    {
        // Confirm testdata has high-level researches before the reset.
        $highLevel = DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('level', '>', 0)
            ->exists();
        $this->assertTrue($highLevel, 'Precondition: testdata must have researches with level > 0');

        $this->actingAs($this->user())->post(route('run.new'));

        $remaining = DB::table('colony_researches')
            ->where('colony_id', $this->colonyId)
            ->where('level', '>', 0)
            ->exists();

        $this->assertFalse($remaining, 'All research levels must be reset to 0 after newRun()');
    }

    public function test_new_run_resets_tiles_to_sol1_explored_state(): void
    {
        // Pre-mark tiles outside the Sol-1 explored set as explored, to prove
        // the colony_tiles table is rebuilt from scratch rather than patched.
        DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('ring', '>=', 2)
            ->update(['is_explored' => true]);

        $this->actingAs($this->user())->post(route('run.new'));

        // Sol-1 canonical state (matches setupNewPlayer()): ring 0+1 fully
        // explored via assignColonyZone(), plus the pre-explored ring-3
        // regolith tile (Harvester relocation target). Ring 2 and the rest
        // of ring 3 stay foggy.
        $exploredRing2Plus = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('ring', '>=', 2)
            ->where('is_explored', 1)
            ->count();

        $this->assertEquals(
            1,
            $exploredRing2Plus,
            'Only the single pre-explored ring-3 regolith tile may be explored at ring >= 2 after newRun()'
        );
    }

    public function test_new_run_releases_advisors_from_colony(): void
    {
        // Testdata seeds personell_id=35 to colony 1 — no extra insert needed.
        $this->assertGreaterThan(0, DB::table('advisors')->where('colony_id', $this->colonyId)->count());

        $this->actingAs($this->user())->post(route('run.new'));

        $count = DB::table('advisors')
            ->where('colony_id', $this->colonyId)
            ->count();

        $this->assertEquals(0, $count, 'All advisors must be released from colony after newRun()');
    }

    public function test_new_run_seeds_starting_buildings(): void
    {
        $this->actingAs($this->user())->post(route('run.new'));

        $ccId = config('buildings.commandCenter.id', 25);
        $harvesterId = config('buildings.harvester.id', 27);
        $housingId = 28;

        $cc = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $ccId)
            ->first();

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $harvesterId)
            ->first();

        $housing = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $housingId)
            ->first();

        $this->assertNotNull($cc, 'CommandCenter must be seeded after newRun()');
        $this->assertEquals(1, $cc->level, 'CC must start at level 1');
        $this->assertEquals(16, $cc->status_points, 'CC must start at 80% status (Sol-1 onboarding design)');

        $this->assertNotNull($harvester, 'Harvester must be seeded after newRun()');
        $this->assertEquals(1, $harvester->level, 'Harvester must start at level 1');
        $this->assertEquals(16, $harvester->status_points, 'Harvester must start at 80% status (Sol-1 onboarding design)');

        $this->assertNotNull($housing, 'HousingComplex must be seeded after newRun() — matches setupNewPlayer() Sol-1 state');
        $this->assertEquals(1, $housing->level, 'HousingComplex must start at level 1');
        $this->assertEquals(16, $housing->status_points, 'HousingComplex must start at 80% status (Sol-1 onboarding design)');

        $this->assertEquals(
            3,
            DB::table('colony_buildings')->where('colony_id', $this->colonyId)->count(),
            'Exactly 3 starting buildings must exist after newRun() — no leftovers from the previous run'
        );
    }

    public function test_new_run_assigns_colony_zone_and_explores_ring_0_and_1(): void
    {
        $this->actingAs($this->user())->post(route('run.new'));

        $exploredRing01 = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('ring', '<=', 1)
            ->where('is_explored', 1)
            ->count();

        $totalRing01 = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('ring', '<=', 1)
            ->count();

        $this->assertGreaterThan(0, $totalRing01, 'Precondition: ring 0+1 tiles must exist');
        $this->assertEquals($totalRing01, $exploredRing01, 'Ring 0+1 must be auto-explored via assignColonyZone() after newRun()');

        $colonyZoneTiles = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('is_colony_zone', 1)
            ->count();

        $this->assertGreaterThan(0, $colonyZoneTiles, 'assignColonyZone() must mark at least one tile as colony zone');
    }

    public function test_new_run_resets_onboarding_hints_and_triggers(): void
    {
        // Simulate stale onboarding state from the previous run.
        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => $this->userId],
            [
                'dismissed_hints' => json_encode(['hint_1', 'hint_2', 'hint_cc_invest']),
                'fired_triggers' => json_encode(['some_trigger']),
            ]
        );

        $this->actingAs($this->user())->post(route('run.new'));

        $prefs = DB::table('user_preferences')->where('user_id', $this->userId)->first();

        $this->assertNull($prefs, 'user_preferences row must be cleared so Sol-1 hints fire again in the new run');
    }

    public function test_new_run_creates_nexus_briefing_event(): void
    {
        $this->actingAs($this->user())->post(route('run.new'));

        $briefing = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'onboarding.nexus_briefing')
            ->first();

        $this->assertNotNull($briefing, 'A Nexus briefing event must be created for the new run');
    }

    public function test_new_run_clears_stale_colony_log_so_briefing_is_not_suppressed(): void
    {
        // A leftover colony_log entry from the previous run must not block the
        // idempotency guard in EventService::createNexusBriefing().
        DB::table('colony_log')->insert([
            'user' => $this->userId,
            'tick' => 5,
            'event' => 'onboarding.nexus_briefing',
            'area' => 'nexus',
            'parameters' => json_encode(['colony_id' => $this->colonyId]),
            'created_at' => now(),
            'is_read' => true,
        ]);

        $this->actingAs($this->user())->post(route('run.new'));

        $count = DB::table('colony_log')
            ->where('user', $this->userId)
            ->where('event', 'onboarding.nexus_briefing')
            ->count();

        $this->assertEquals(1, $count, 'Exactly one fresh nexus_briefing event must exist after newRun()');
    }

    /**
     * Parity test: newRun() and setupNewPlayer() must produce the identical
     * Sol-1 colony state (building count/levels/status, tile explored count).
     */
    public function test_new_run_produces_identical_state_to_setup_new_player(): void
    {
        $onboardingService = $this->app->make(OnboardingService::class);

        // Reference state via setupNewPlayer() for a fresh user.
        $referenceUser = User::factory()->create();
        $referenceColony = $onboardingService->setupNewPlayer($referenceUser->user_id, 'Reference');

        $referenceBuildings = DB::table('colony_buildings')
            ->where('colony_id', $referenceColony->id)
            ->orderBy('building_id')
            ->get(['building_id', 'level', 'status_points'])
            ->toArray();

        $referenceExploredCount = DB::table('colony_tiles')
            ->where('colony_id', $referenceColony->id)
            ->where('is_explored', 1)
            ->count();

        // Actual state via newRun() for the existing test colony.
        $this->actingAs($this->user())->post(route('run.new'));

        $actualBuildings = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->orderBy('building_id')
            ->get(['building_id', 'level', 'status_points'])
            ->toArray();

        $actualExploredCount = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('is_explored', 1)
            ->count();

        $this->assertEquals($referenceBuildings, $actualBuildings, 'newRun() must seed the same buildings as setupNewPlayer()');
        $this->assertEquals($referenceExploredCount, $actualExploredCount, 'newRun() must explore the same tile count as setupNewPlayer()');
    }

    public function test_new_run_resets_user_credits_to_3000(): void
    {
        $this->actingAs($this->user())->post(route('run.new'));

        $credits = DB::table('user_resources')
            ->where('user_id', $this->userId)
            ->value('credits');

        $this->assertEquals(3000, $credits, 'Credits must be reset to 3000 Cr after newRun()');
    }
}
