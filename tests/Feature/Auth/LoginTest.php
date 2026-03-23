<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for login, logout and registration.
 * Uses the test.db (configured via .env.testing).
 *
 * Covers:
 * - Login with username
 * - Login with email
 * - Wrong credentials rejected
 * - Logout works
 * - Registration creates user and logs in
 * - Guests are redirected to login
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get(route('login'))->assertStatus(200)->assertSee('Anmelden');
    }

    public function test_register_page_loads(): void
    {
        $this->get(route('register'))->assertStatus(200)->assertSee('Registrieren');
    }

    public function test_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('secret123'),
        ]);

        $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'secret123',
        ])->assertRedirect(route('techtree.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_email(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $this->post(route('login'), [
            'username' => 'test@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('techtree.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_rejected(): void
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => bcrypt('correct'),
        ]);

        $this->post(route('login'), [
            'username' => 'testuser',
            'password' => 'wrong',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_registration_creates_user_and_logs_in(): void
    {
        $this->post(route('register'), [
            'username'              => 'newplayer',
            'email'                 => 'new@example.com',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertRedirect(route('techtree.index'));

        $this->assertDatabaseHas('user', ['username' => 'newplayer']);
        $this->assertAuthenticated();
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get(route('user.show'))->assertRedirect(route('login'));
    }
}
