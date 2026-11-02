<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * LOW-1: Login brute-force throttle and auth flow tests against POST /api/v1/auth/login.
 *
 * Covers:
 * - LOW-1 (throttle): 5 failed attempts are allowed; the 6th returns 429
 * - After throttle triggers, the correct password also returns 429 (locked out)
 * - Non-existent username shows a validation error and issues no token
 * - Failed attempts below the limit do not block a following correct login
 *
 * Login by username/e-mail, wrong password and logout are covered by
 * tests/Feature/Api/AuthApiTest.php.
 *
 * Note: the throttle middleware is `throttle:5,1` (5 attempts per 1 minute).
 * RefreshDatabase + RateLimiter::clear() ensure isolation between tests.
 */
class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Clear rate limiter state so tests start with a clean slate
        RateLimiter::clear($this->throttleKey());
    }

    protected function tearDown(): void
    {
        RateLimiter::clear($this->throttleKey());
        parent::tearDown();
    }

    private function login(string $login, string $password): TestResponse
    {
        return $this->postJson(route('login'), ['login' => $login, 'password' => $password]);
    }

    // ── Auth flow: wrong credentials ─────────────────────────────────────────

    /**
     * Non-existent username must return the same error as a wrong password,
     * without leaking whether the username exists, and no token.
     */
    public function test_unknown_username_shows_error(): void
    {
        $this->login('nobody_here', 'irrelevant')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['login' => __('auth.failed')])
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── LOW-1: brute-force throttle ──────────────────────────────────────────

    /**
     * Up to 5 failed login attempts must return the normal error (not 429).
     * The 6th attempt must return HTTP 429 Too Many Requests.
     */
    public function test_sixth_failed_attempt_returns_429(): void
    {
        User::factory()->create([
            'username' => 'victim',
            'password' => bcrypt('secret'),
        ]);

        // Attempts 1–5: each should fail with a validation error, not 429
        for ($i = 1; $i <= 5; $i++) {
            $this->login('victim', 'wrong')
                ->assertStatus(422, "Attempt {$i} should fail validation (not 429 yet).");
        }

        // 6th attempt: must be throttled
        $this->login('victim', 'wrong')->assertStatus(429);
    }

    /**
     * After being throttled, even a correct password must return 429
     * (the lockout applies to the endpoint, not just wrong credentials).
     */
    public function test_throttle_blocks_correct_password_too(): void
    {
        User::factory()->create([
            'username' => 'lockedout',
            'password' => bcrypt('correct'),
        ]);

        // Exhaust the 5 allowed attempts with wrong passwords
        for ($i = 0; $i < 5; $i++) {
            $this->login('lockedout', 'wrong');
        }

        // Now try the correct password — must still be blocked, no token issued
        $this->login('lockedout', 'correct')
            ->assertStatus(429)
            ->assertJsonMissingPath('token');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Exactly 5 attempts (the limit) must not yet trigger the 429.
     * This confirms the boundary: 5 allowed, 6 blocked.
     */
    public function test_exactly_five_attempts_are_allowed(): void
    {
        User::factory()->create([
            'username' => 'boundary_user',
            'password' => bcrypt('correct'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->login('boundary_user', 'wrong');
            // Must not be 429 on any of the first 5 attempts
            $this->assertNotEquals(429, $response->getStatusCode(),
                'Attempt '.($i + 1).' must not be throttled.');
        }
    }

    /**
     * Failed attempts below the limit do not lock out a following correct login.
     *
     * Note: Laravel's built-in throttle middleware does NOT clear the counter
     * on successful login; the throttle window is 1 minute and the RateLimiter
     * is cleared between tests.
     */
    public function test_failed_attempts_below_the_limit_still_allow_login(): void
    {
        User::factory()->create([
            'username' => 'sessiontest',
            'password' => bcrypt('password'),
        ]);

        // Two failed attempts
        $this->login('sessiontest', 'bad')->assertStatus(422);
        $this->login('sessiontest', 'bad')->assertStatus(422);

        // Then succeed — should still be allowed (< 5 attempts used)
        $this->login('sessiontest', 'password')
            ->assertOk()
            ->assertJsonStructure(['token']);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    /**
     * Returns the rate-limiter key that Laravel uses for the login throttle.
     * Laravel's ThrottleRequests middleware generates the key from the request
     * fingerprint, which for the test client resolves to 127.0.0.1.
     */
    private function throttleKey(): string
    {
        // The key is "sha1(ip|domain)" as used by ThrottleRequests middleware.
        // For feature tests the IP is 127.0.0.1 and domain is the app URL host.
        return sha1('127.0.0.1|'.parse_url(config('app.url'), PHP_URL_HOST));
    }
}
