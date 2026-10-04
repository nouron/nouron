<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R5b: resetColonyToSol1() skips deletes on tables where the colony has no rows
 * (an empty-range DELETE takes InnoDB gap locks that deadlock concurrent
 * onboardings). Regression guard: an existing colony must still be cleared
 * completely, and a reset must never touch another player's colony.
 */
class OnboardingResetIsolationTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_TABLES = ['colony_resources', 'colony_buildings', 'colony_tiles'];

    protected function setUp(): void
    {
        parent::setUp();
        app(ReferenceDataSeeder::class)->run();
    }

    /** @return array{0:int,1:int} user id, colony id */
    private function newPlayer(string $name): array
    {
        $user = User::create([
            'username' => $name,
            'display_name' => $name,
            'role' => 'player',
            'password' => 'secret',
            'email' => "{$name}@example.invalid",
            'activated' => 1,
            'activation_key' => '',
        ]);
        $colony = app(OnboardingService::class)->setupNewPlayer((int) $user->user_id);

        return [(int) $user->user_id, (int) $colony->id];
    }

    /** @return array<string,int> */
    private function counts(int $colonyId): array
    {
        $counts = [];
        foreach (self::COLONY_TABLES as $table) {
            $counts[$table] = DB::table($table)->where('colony_id', $colonyId)->count();
        }

        return $counts;
    }

    public function test_reset_clears_an_existing_colony_and_leaves_other_colonies_untouched(): void
    {
        [$userA, $colonyA] = $this->newPlayer('player_a');
        [, $colonyB] = $this->newPlayer('player_b');
        $freshA = $this->counts($colonyA);
        $before = $this->counts($colonyB);
        $tilesB = DB::table('colony_tiles')->where('colony_id', $colonyB)->orderBy('id')->get()->toArray();

        // Played state on A that a reset must wipe.
        DB::table('colony_resources')->where('colony_id', $colonyA)->update(['amount' => 999]);
        DB::table('colony_ships')->insert(['colony_id' => $colonyA, 'ship_id' => DB::table('ships')->value('id'), 'level' => 1, 'status_points' => 5]);
        DB::table('trust_events')->insert(['colony_id' => $colonyA, 'tick' => 3, 'event_type' => 'test']);

        app(OnboardingService::class)->resetColonyToSol1($userA, $colonyA, rngSeed: 42);

        $this->assertSame($freshA, $this->counts($colonyA), 'Reset colony must hold exactly the fresh Sol-1 state');
        $this->assertSame(0, DB::table('colony_resources')->where('colony_id', $colonyA)->where('amount', 999)->count());
        $this->assertSame(0, DB::table('colony_ships')->where('colony_id', $colonyA)->count());
        $this->assertSame(0, DB::table('trust_events')->where('colony_id', $colonyA)->count());
        $this->assertSame(1, DB::table('runs')->where('user_id', $userA)->where('status', 'active')->count());

        $this->assertSame($before, $this->counts($colonyB), 'Another player\'s colony must stay untouched');
        $this->assertEquals($tilesB, DB::table('colony_tiles')->where('colony_id', $colonyB)->orderBy('id')->get()->toArray());
    }

    public function test_reset_of_a_brand_new_colony_produces_the_sol1_state(): void
    {
        [$user, $colony] = $this->newPlayer('player_c');
        $fresh = $this->counts($colony);

        app(OnboardingService::class)->resetColonyToSol1($user, $colony, rngSeed: 7);

        $this->assertSame($fresh, $this->counts($colony));
        $this->assertSame(1, DB::table('runs')->where('user_id', $user)->where('status', 'active')->count());
    }
}
