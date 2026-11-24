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
 * the user's language for API responses. Profile, name, hints and password: UserControllerTest.
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
