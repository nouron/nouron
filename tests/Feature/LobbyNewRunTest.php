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
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LobbyNewRunTest extends TestCase
{
    use RefreshDatabase;

    protected int $userId   = 3;
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
            'user_id'      => $this->userId,
            'colony_id'    => $this->colonyId,
            'current_tick' => 5,
            'status'       => 'active',
            'phase'        => 1,
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

    public function test_new_run_resets_tiles_to_unexplored(): void
    {
        // Pre-mark some tiles as explored.
        DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->limit(5)
            ->update(['is_explored' => true]);

        $this->actingAs($this->user())->post(route('run.new'));

        $exploredCount = DB::table('colony_tiles')
            ->where('colony_id', $this->colonyId)
            ->where('is_explored', 1)
            ->count();

        $this->assertEquals(0, $exploredCount, 'All tiles must be unexplored after newRun()');
    }

    public function test_new_run_releases_advisors_from_colony(): void
    {
        // Bind an advisor to colony 1.
        DB::table('advisors')->insert([
            'user_id'                => $this->userId,
            'personell_id'           => 35,
            'colony_id'              => $this->colonyId,
            'rank'                   => 1,
            'active_ticks'           => 0,
            'unavailable_until_tick' => null,
        ]);

        $this->actingAs($this->user())->post(route('run.new'));

        $count = DB::table('advisors')
            ->where('colony_id', $this->colonyId)
            ->count();

        $this->assertEquals(0, $count, 'All advisors must be released from colony after newRun()');
    }

    public function test_new_run_seeds_starting_buildings(): void
    {
        $this->actingAs($this->user())->post(route('run.new'));

        $ccId        = config('buildings.commandCenter.id', 25);
        $harvesterId = config('buildings.harvester.id', 27);

        $cc = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $ccId)
            ->first();

        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $this->colonyId)
            ->where('building_id', $harvesterId)
            ->first();

        $this->assertNotNull($cc, 'CommandCenter must be seeded after newRun()');
        $this->assertEquals(1, $cc->level, 'CC must start at level 1');

        $this->assertNotNull($harvester, 'Harvester must be seeded after newRun()');
        $this->assertEquals(1, $harvester->level, 'Harvester must start at level 1');
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
