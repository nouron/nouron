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
        $this->assertEquals(500, $res[3]);   // water
        $this->assertEquals(500, $res[4]);   // ferum
        $this->assertEquals(500, $res[5]);   // silicates
        $this->assertEquals(100, $res[6]);   // ena
        $this->assertEquals(100, $res[8]);   // lho
        $this->assertEquals(100, $res[10]);  // aku

        // CommandCenter at level 1
        $cc = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 25)
            ->first();
        $this->assertNotNull($cc, 'CommandCenter must be placed');
        $this->assertEquals(1, $cc->level);
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
