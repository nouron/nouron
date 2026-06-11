<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_registration_creates_colony_with_starting_resources(): void
    {
        $this->post(route('register'), [
            'username'              => 'newplayer',
            'email'                 => 'new@example.com',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('lobby'));

        $user = User::where('username', 'newplayer')->firstOrFail();

        // Colony exists
        $colony = DB::table('glx_colonies')->where('user_id', $user->user_id)->first();
        $this->assertNotNull($colony, 'Colony must be created after registration');

        // Starting user resources
        $userRes = DB::table('user_resources')->where('user_id', $user->user_id)->first();
        $this->assertEquals(3000, $userRes->credits);
        $this->assertEquals(15, $userRes->supply);

        // Starting colony resources
        $res = DB::table('colony_resources')
            ->where('colony_id', $colony->id)
            ->pluck('amount', 'resource_id');
        $this->assertEquals(200, $res[3]);   // regolith
        $this->assertEquals(0,   $res[4]);   // werkstoffe — produced by harvester, no starting stock
        $this->assertEquals(0,   $res[5]);   // organika   — produced by bioFacility, no starting stock

        // CommandCenter at level 1
        $cc = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 25)
            ->first();
        $this->assertNotNull($cc, 'CommandCenter must be placed');
        $this->assertEquals(1, $cc->level);

        // Harvester at level 0, ap_spend=7 — "almost done", player places + finishes it Sol 1
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 27)
            ->first();
        $this->assertNotNull($harvester, 'Harvester must exist at game start');
        $this->assertEquals(0, $harvester->level, 'Harvester starts unfinished (level 0)');
        $this->assertEquals(7, $harvester->ap_spend, 'Harvester has 7/10 AP pre-invested');
        $this->assertNull($harvester->tile_x, 'Harvester starts unplaced — player positions it');

        // HousingComplex at level 0, ap_spend=7 — same as harvester
        $housing = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 28)
            ->first();
        $this->assertNotNull($housing, 'HousingComplex must exist at game start');
        $this->assertEquals(0, $housing->level, 'HousingComplex starts unfinished (level 0)');
        $this->assertEquals(7, $housing->ap_spend, 'HousingComplex has 7/10 AP pre-invested');
    }

    public function test_setup_new_player_creates_colony_without_planet(): void
    {
        $service = $this->app->make(OnboardingService::class);
        $user    = User::factory()->create();

        $colony = $service->setupNewPlayer($user->user_id, 'Testkolonie');

        $this->assertNotNull($colony);
        $this->assertEquals($user->user_id, $colony->user_id);
        $this->assertNull($colony->system_object_id, 'Colonies no longer require a planet assignment');
    }

    public function test_login_triggers_onboarding_for_user_without_colony(): void
    {
        // Create a user but no colony
        $user = User::factory()->create([
            'username' => 'nocolony',
            'password' => bcrypt('secret123'),
        ]);

        $this->assertDatabaseMissing('glx_colonies', ['user_id' => $user->user_id]);

        $this->post(route('login'), [
            'username' => 'nocolony',
            'password' => 'secret123',
        ])->assertRedirect(route('lobby'));

        $this->assertDatabaseHas('glx_colonies', ['user_id' => $user->user_id]);
    }
}
