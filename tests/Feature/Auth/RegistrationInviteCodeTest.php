<?php

namespace Tests\Feature\Auth;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R9 — closed beta: registration requires the shared invite code
 * (config auth.invite_code, env BETA_INVITE_CODE) whenever one is configured.
 * Without a configured code (local dev, tests) registration stays open.
 */
class RegistrationInviteCodeTest extends TestCase
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
            'username' => 'betatester',
            'email' => 'beta@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ], $overrides);
    }

    public function test_registration_succeeds_with_correct_invite_code(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->post(route('register'), $this->payload(['invite_code' => 'NOURON-BETA']))
            ->assertRedirect(route('lobby'));

        $this->assertDatabaseHas('user', ['username' => 'betatester']);
    }

    public function test_invite_code_ignores_surrounding_whitespace(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->post(route('register'), $this->payload(['invite_code' => '  NOURON-BETA ']))
            ->assertRedirect(route('lobby'));
    }

    public function test_registration_fails_with_wrong_invite_code(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->from(route('register'))
            ->post(route('register'), $this->payload(['invite_code' => 'guess']))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('invite_code');

        $this->assertDatabaseMissing('user', ['username' => 'betatester']);
        $this->assertGuest();
    }

    public function test_registration_fails_without_invite_code_when_one_is_configured(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->from(route('register'))
            ->post(route('register'), $this->payload())
            ->assertSessionHasErrors('invite_code');

        $this->assertDatabaseMissing('user', ['username' => 'betatester']);
    }

    public function test_registration_is_open_when_no_invite_code_is_configured(): void
    {
        config(['auth.invite_code' => null]);

        $this->post(route('register'), $this->payload())
            ->assertRedirect(route('lobby'));
    }

    public function test_register_form_shows_invite_field_only_when_required(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);
        $this->get(route('register'))->assertOk()->assertSee('name="invite_code"', false);

        config(['auth.invite_code' => null]);
        $this->get(route('register'))->assertOk()->assertDontSee('name="invite_code"', false);
    }

    public function test_registration_is_rate_limited(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('register'), $this->payload(['invite_code' => "wrong{$i}"]));
        }

        $this->post(route('register'), $this->payload(['invite_code' => 'wrong-again']))
            ->assertStatus(429);
    }
}
