<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Token auth for the SvelteKit frontend (ADR 0006, spike stage 0):
 * POST /api/v1/auth/login issues a Sanctum bearer token, GET /api/v1/ping requires it.
 */
class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::factory()->create([
            'username' => 'spike',
            'email' => 'spike@example.com',
            'password' => bcrypt('secret123'),
        ]);
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $this->makeUser();

        $this->postJson('/api/v1/auth/login', [
            'login' => 'spike@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_accepts_username_like_the_web_login(): void
    {
        $this->makeUser();

        $this->postJson('/api/v1/auth/login', [
            'login' => 'spike',
            'password' => 'secret123',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_with_wrong_password_returns_422_without_token(): void
    {
        $this->makeUser();

        $this->postJson('/api/v1/auth/login', [
            'login' => 'spike@example.com',
            'password' => 'wrong',
        ])->assertStatus(422)->assertJsonMissingPath('token');
    }

    public function test_login_requires_login_and_password(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login', 'password']);
    }

    public function test_ping_without_token_returns_401(): void
    {
        $this->getJson('/api/v1/ping')->assertUnauthorized();
    }

    public function test_ping_with_issued_token_returns_user(): void
    {
        $user = $this->makeUser();

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => 'spike@example.com',
            'password' => 'secret123',
        ])->json('token');

        $this->withToken($token)->getJson('/api/v1/ping')
            ->assertOk()
            ->assertJson(['pong' => true, 'user_id' => $user->user_id]);
    }

    public function test_cors_allows_dev_frontend_origin(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:5173'])
            ->getJson('/api/v1/ping')
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
