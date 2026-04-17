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
        ])->assertRedirect(route('galaxy.index'));

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

        // Harvester at level 1 — immediately operational from day one
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 27)
            ->first();
        $this->assertNotNull($harvester, 'Harvester must be placed at game start');
        $this->assertEquals(1, $harvester->level);
        $this->assertEquals(20, $harvester->status_points, 'Harvester must start fully intact');
    }

    public function test_setup_new_player_uses_free_planet(): void
    {
        $service = $this->app->make(OnboardingService::class);
        $user    = User::factory()->create();

        $colony = $service->setupNewPlayer($user->user_id, 'Testkolonie');

        $this->assertNotNull($colony);
        $this->assertEquals($user->user_id, $colony->user_id);

        // Planet must not be shared with another colony from the same setup call
        $count = DB::table('glx_colonies')
            ->where('system_object_id', $colony->system_object_id)
            ->where('user_id', $user->user_id)
            ->count();
        $this->assertEquals(1, $count);
    }

    public function test_registration_fails_when_no_free_planets(): void
    {
        // Block all 14 system_objects with fake colonies.
        // system_object_id=1 is already occupied by the TestSeeder (Springfield + Shelbyville).
        // We insert one fake colony each for the remaining 13 objects (IDs 2–18).
        $fakeColonyId = 100;
        foreach ([2, 3, 4, 5, 10, 11, 12, 13, 14, 15, 16, 17, 18] as $systemObjectId) {
            DB::table('glx_colonies')->insert([
                'id'               => $fakeColonyId++,
                'name'             => 'FakeColony_' . $systemObjectId,
                'system_object_id' => $systemObjectId,
                'spot'             => 1,
                'user_id'          => null,
                'since_tick'       => 0,
                'is_primary'       => 0,
            ]);
        }

        // Set Referer so that back() in the controller resolves to the register route,
        // which is what a real browser would send after submitting the registration form.
        $response = $this->withHeaders(['Referer' => route('register')])
            ->post(route('register'), [
                'username'              => 'blockedplayer',
                'email'                 => 'blocked@example.com',
                'password'              => 'secret1234',
                'password_confirmation' => 'secret1234',
            ]);

        // Must not redirect to galaxy — expect redirect back to registration form
        $response->assertRedirectContains(route('register'));

        // An error must be present in the session
        $response->assertSessionHasErrors('username');

        // No user account must have been created (transaction rolled back)
        $this->assertDatabaseMissing('user', ['username' => 'blockedplayer']);
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
        ])->assertRedirect(route('galaxy.index'));

        $this->assertDatabaseHas('glx_colonies', ['user_id' => $user->user_id]);
    }
}
