<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Profile and settings for the SvelteKit frontend (ADR 0006 stage 5b):
 * same actions as the Blade settings page, plus the user's language for API responses.
 */
class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart, password 'test123'

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['app.locale' => 'de']);
        Sanctum::actingAs(User::findOrFail(self::USER_ID));
    }

    private function prefs(): ?object
    {
        return DB::table('user_preferences')->where('user_id', self::USER_ID)->first();
    }

    public function test_settings_require_a_token(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/settings')->assertUnauthorized();
    }

    public function test_settings_return_profile_and_preferences(): void
    {
        DB::table('user_preferences')->where('user_id', self::USER_ID)->delete();

        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJson([
                'username' => 'Bart',
                'onboarding_hints' => true,
                'locale' => 'de',
                'supported_locales' => config('app.supported_locales'),
            ])
            ->assertJsonStructure(['display_name', 'email', 'role', 'registration'])
            ->assertJsonMissingPath('password');
    }

    public function test_update_display_name(): void
    {
        $this->patchJson('/api/v1/settings/name', ['display_name' => 'Bartholomew'])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame('Bartholomew', User::findOrFail(self::USER_ID)->display_name);
        $this->patchJson('/api/v1/settings/name', ['display_name' => ''])->assertJsonValidationErrors('display_name');
    }

    public function test_update_onboarding_hints(): void
    {
        $this->patchJson('/api/v1/settings/onboarding', ['onboarding_hints' => false])->assertOk();

        $this->assertSame(0, (int) $this->prefs()->onboarding_hints);
    }

    public function test_update_locale_and_answer_in_the_new_language(): void
    {
        $this->patchJson('/api/v1/settings/locale', ['locale' => 'en'])
            ->assertOk()->assertJson(['ok' => true, 'message' => __('settings.saved', [], 'en')]);

        $this->assertSame('en', $this->prefs()->locale);
        $this->patchJson('/api/v1/settings/locale', ['locale' => 'xx'])->assertJsonValidationErrors('locale');
    }

    public function test_api_responses_follow_the_users_language(): void
    {
        DB::table('user_preferences')->updateOrInsert(['user_id' => self::USER_ID], ['locale' => 'en']);

        $this->getJson('/api/v1/shell')->assertOk()->assertJson(['locale' => 'en']);
        $this->patchJson('/api/v1/settings/password', [
            'current_password' => 'wrong',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(422)->assertJsonPath('errors.current_password.0', __('settings.password_wrong', [], 'en'));
    }

    public function test_update_password(): void
    {
        $this->patchJson('/api/v1/settings/password', [
            'current_password' => 'test123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertTrue(Hash::check('newpassword123', User::findOrFail(self::USER_ID)->password));
    }
}
