<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart, password 'test123' (see feedback_dev_db_workflow memory)

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    public function test_settings_returns_profile(): void
    {
        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $response->assertJsonPath('username', 'Bart');
        $response->assertJsonStructure(['username', 'display_name', 'email', 'role', 'registration']);
        $response->assertJsonMissingPath('password');
    }

    public function test_settings_defaults_onboarding_hints_to_true_without_prefs_row(): void
    {
        DB::table('user_preferences')->where('user_id', self::USER_ID)->delete();

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $response->assertJsonPath('onboarding_hints', true);
    }

    public function test_settings_reads_onboarding_hints_from_prefs_row(): void
    {
        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => self::USER_ID],
            ['onboarding_hints' => false]
        );

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertJsonPath('onboarding_hints', false);
    }

    public function test_update_display_name(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.displayname'), ['display_name' => 'Bartholomew']);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('Bartholomew', $this->user()->display_name);
    }

    public function test_update_display_name_requires_value(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.displayname'), ['display_name' => '']);

        $response->assertUnprocessable()->assertJsonValidationErrors('display_name');
    }

    public function test_update_onboarding_hints(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.onboarding'), ['onboarding_hints' => false]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, (int) DB::table('user_preferences')
            ->where('user_id', self::USER_ID)->value('onboarding_hints'));
    }

    public function test_update_locale_stores_preference(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.locale'), ['locale' => 'en']);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('en', DB::table('user_preferences')
            ->where('user_id', self::USER_ID)->value('locale'));
    }

    public function test_update_locale_creates_prefs_row_if_missing(): void
    {
        DB::table('user_preferences')->where('user_id', self::USER_ID)->delete();

        $this->actingAs($this->user())
            ->patch(route('user.update.locale'), ['locale' => 'de']);

        $this->assertSame('de', DB::table('user_preferences')
            ->where('user_id', self::USER_ID)->value('locale'));
    }

    public function test_update_locale_rejects_unsupported_locale(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.locale'), ['locale' => 'fr']);

        $response->assertUnprocessable()->assertJsonValidationErrors('locale');
    }

    public function test_locale_preference_is_applied_to_requests(): void
    {
        config(['app.locale' => 'de']);
        DB::table('user_preferences')->updateOrInsert(['user_id' => self::USER_ID], ['locale' => 'en']);

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $this->assertSame('en', app()->getLocale());
        $response->assertJsonPath('locale', 'en');
    }

    public function test_default_locale_is_used_without_preference(): void
    {
        config(['app.locale' => 'de']);
        DB::table('user_preferences')->where('user_id', self::USER_ID)->delete();

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $this->assertSame('de', app()->getLocale());
        $response->assertJsonPath('locale', 'de');
    }

    public function test_settings_shows_language_selection(): void
    {
        config(['app.locale' => 'de']);

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $this->assertSame(config('app.supported_locales'), $response->json('supported_locales'));
        $this->assertContains('en', $response->json('supported_locales'));
        $this->assertContains('de', $response->json('supported_locales'));
    }

    public function test_update_password_with_wrong_current_password_fails(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.password'), [
                'current_password' => 'wrong-password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('current_password');
    }

    public function test_update_password_with_correct_current_password_succeeds(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.password'), [
                'current_password' => 'test123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk()->assertJson(['ok' => true, 'message' => __('settings.password_saved')]);
        $this->assertTrue(Hash::check('newpassword123', $this->user()->password));
    }
}
