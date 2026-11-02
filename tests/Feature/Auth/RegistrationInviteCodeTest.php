<?php

namespace Tests\Feature\Auth;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * R9 — closed beta: registration requires the shared invite code
 * (config auth.invite_code, env BETA_INVITE_CODE) whenever one is configured.
 * Without a configured code (local dev, tests) registration stays open.
 *
 * Wrong code, the form's invite_required flag and the throttle are covered by
 * tests/Feature/Api/RegisterApiTest.php.
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

        $this->postJson(route('register'), $this->payload(['invite_code' => 'NOURON-BETA']))
            ->assertCreated()
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('user', ['username' => 'betatester']);
    }

    public function test_invite_code_ignores_surrounding_whitespace(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->postJson(route('register'), $this->payload(['invite_code' => '  NOURON-BETA ']))
            ->assertCreated();

        $this->assertDatabaseHas('user', ['username' => 'betatester']);
    }

    public function test_registration_fails_without_invite_code_when_one_is_configured(): void
    {
        config(['auth.invite_code' => 'NOURON-BETA']);

        $this->postJson(route('register'), $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('invite_code')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseMissing('user', ['username' => 'betatester']);
    }

    public function test_registration_is_open_when_no_invite_code_is_configured(): void
    {
        config(['auth.invite_code' => null]);

        $this->postJson(route('register'), $this->payload())
            ->assertCreated();

        $this->assertDatabaseHas('user', ['username' => 'betatester']);
    }
}
