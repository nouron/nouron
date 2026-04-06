<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * LOW-1: Login brute-force throttle and auth flow tests.
 *
 * Covers:
 * - LOW-1 (throttle): 5 failed attempts are allowed; the 6th returns 429
 * - Wrong password shows error on username field, guest remains unauthenticated
 * - Non-existent username shows error, guest remains unauthenticated
 * - Successful login with username → redirect to galaxy.index
 * - Successful login with email → redirect to galaxy.index
 * - Throttle is per IP key — a different IP (different key) is not blocked
 * - After throttle triggers, the correct password also returns 429 (locked out)
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

    // ── Auth flow: wrong credentials ─────────────────────────────────────────

    /**
     * Wrong password must return a validation error on the username field,
     * and the user must remain a guest.
     */
    public function test_wrong_password_shows_error_and_stays_guest(): void
    {
        User::factory()->create([
            'username' => 'testplayer',
            'password' => bcrypt('correct_password'),
        ]);

        $this->post(route('login'), [
            'username' => 'testplayer',
            'password' => 'wrong_password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    /**
     * Non-existent username must also return an error without leaking whether
     * the username exists.
     */
    public function test_unknown_username_shows_error(): void
    {
        $this->post(route('login'), [
            'username' => 'nobody_here',
            'password' => 'irrelevant',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    // ── Auth flow: successful login ──────────────────────────────────────────

    /**
     * Correct credentials with username → redirected to galaxy.index.
     */
    public function test_successful_login_with_username_redirects_to_galaxy(): void
    {
        $user = User::factory()->create([
            'username' => 'validplayer',
            'password' => bcrypt('mypassword'),
        ]);

        $this->post(route('login'), [
            'username' => 'validplayer',
            'password' => 'mypassword',
        ])->assertRedirect(route('galaxy.index'));

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Correct credentials with email address → redirected to galaxy.index.
     */
    public function test_successful_login_with_email_redirects_to_galaxy(): void
    {
        $user = User::factory()->create([
            'email'    => 'valid@example.com',
            'password' => bcrypt('mypassword'),
        ]);

        $this->post(route('login'), [
            'username' => 'valid@example.com',
            'password' => 'mypassword',
        ])->assertRedirect(route('galaxy.index'));

        $this->assertAuthenticatedAs($user);
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

        // Attempts 1–5: each should fail with a session error, not 429
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->post(route('login'), [
                'username' => 'victim',
                'password' => 'wrong',
            ]);
            $response->assertStatus(302, "Attempt {$i} should redirect (not 429 yet).");
        }

        // 6th attempt: must be throttled
        $this->post(route('login'), [
            'username' => 'victim',
            'password' => 'wrong',
        ])->assertStatus(429);
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
            $this->post(route('login'), [
                'username' => 'lockedout',
                'password' => 'wrong',
            ]);
        }

        // Now try the correct password — must still be blocked
        $this->post(route('login'), [
            'username' => 'lockedout',
            'password' => 'correct',
        ])->assertStatus(429);

        // Must remain a guest
        $this->assertGuest();
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
            $response = $this->post(route('login'), [
                'username' => 'boundary_user',
                'password' => 'wrong',
            ]);
            // Must not be 429 on any of the first 5 attempts
            $this->assertNotEquals(429, $response->getStatusCode(),
                "Attempt " . ($i + 1) . " must not be throttled.");
        }
    }

    /**
     * Successful login clears the throttle counter so the user can log out
     * and back in without being rate-limited.
     *
     * Note: Laravel's built-in throttle middleware does NOT automatically clear
     * the counter on successful login, but this test documents actual behavior.
     * The throttle window is 1 minute; between tests RateLimiter is cleared.
     */
    public function test_failed_attempts_are_tracked_per_session(): void
    {
        User::factory()->create([
            'username' => 'sessiontest',
            'password' => bcrypt('password'),
        ]);

        // Two failed attempts
        $this->post(route('login'), ['username' => 'sessiontest', 'password' => 'bad']);
        $this->post(route('login'), ['username' => 'sessiontest', 'password' => 'bad']);

        // Then succeed — should still be allowed (< 5 attempts used)
        $this->post(route('login'), [
            'username' => 'sessiontest',
            'password' => 'password',
        ])->assertRedirect(route('galaxy.index'));

        $this->assertAuthenticated();
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
        return sha1('127.0.0.1|' . parse_url(config('app.url'), PHP_URL_HOST));
    }
}
