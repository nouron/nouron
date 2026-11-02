<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Registration and login onboarding for the SvelteKit frontend (ADR 0006 stage 5a):
 * same rules as the Blade forms, but answers with a bearer token instead of a session.
 */
class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'username' => 'newbie',
            'email' => 'newbie@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ], $overrides);
    }

    public function test_register_form_says_whether_an_invite_code_is_needed(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);
        $this->getJson('/api/v1/auth/register')->assertOk()->assertExactJson(['invite_required' => true]);

        config(['auth.invite_code' => null]);
        $this->getJson('/api/v1/auth/register')->assertOk()->assertExactJson(['invite_required' => false]);
    }

    public function test_register_creates_player_with_colony_and_returns_a_working_token(): void
    {
        config(['auth.invite_code' => null]);

        $token = $this->postJson('/api/v1/auth/register', $this->payload())
            ->assertCreated()
            ->assertJsonStructure(['token'])
            ->json('token');

        $user = User::where('username', 'newbie')->firstOrFail();
        $this->assertTrue(DB::table('glx_colonies')->where('user_id', $user->user_id)->exists());
        $this->withToken($token)->getJson('/api/v1/ping')->assertOk()->assertJson(['user_id' => $user->user_id]);
    }

    public function test_register_rejects_wrong_invite_code(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->postJson('/api/v1/auth/register', $this->payload(['invite_code' => 'guess']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('invite_code')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseMissing('user', ['username' => 'newbie']);
    }

    public function test_register_rejects_taken_username_and_short_password(): void
    {
        config(['auth.invite_code' => null]);
        User::factory()->create(['username' => 'newbie']);

        $this->postJson('/api/v1/auth/register', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_register_is_throttled_like_the_web_form(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', $this->payload(['invite_code' => 'guess'.$i]))->assertStatus(422);
        }
        $this->postJson('/api/v1/auth/register', $this->payload(['invite_code' => 'NOURON-BETA']))->assertStatus(429);
    }

    public function test_login_sets_up_a_colony_for_accounts_without_one(): void
    {
        $user = User::factory()->create(['username' => 'legacy', 'password' => bcrypt('secret123')]);
        DB::table('glx_colonies')->where('user_id', $user->user_id)->delete();

        $this->postJson('/api/v1/auth/login', ['login' => 'legacy', 'password' => 'secret123'])->assertOk();

        $this->assertTrue(DB::table('glx_colonies')->where('user_id', $user->user_id)->exists());
    }
}
