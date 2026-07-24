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

    public function test_show_renders_profile(): void
    {
        $response = $this->actingAs($this->user())->get(route('user.show'));

        $response->assertOk();
    }

    public function test_settings_defaults_onboarding_hints_to_true_without_prefs_row(): void
    {
        DB::table('user_preferences')->where('user_id', self::USER_ID)->delete();

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertOk();
        $response->assertViewHas('onboarding_hints', true);
    }

    public function test_settings_reads_onboarding_hints_from_prefs_row(): void
    {
        DB::table('user_preferences')->updateOrInsert(
            ['user_id' => self::USER_ID],
            ['onboarding_hints' => false]
        );

        $response = $this->actingAs($this->user())->get(route('user.settings'));

        $response->assertViewHas('onboarding_hints', false);
    }

    public function test_update_display_name(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.displayname'), ['display_name' => 'Bartholomew']);

        $response->assertRedirect(route('user.settings'));
        $this->assertSame('Bartholomew', $this->user()->display_name);
    }

    public function test_update_display_name_requires_value(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.displayname'), ['display_name' => '']);

        $response->assertSessionHasErrors('display_name');
    }

    public function test_update_onboarding_hints(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.onboarding'), ['onboarding_hints' => false]);

        $response->assertRedirect(route('user.settings'));
        $this->assertSame(0, (int) DB::table('user_preferences')
            ->where('user_id', self::USER_ID)->value('onboarding_hints'));
    }

    public function test_update_password_with_wrong_current_password_fails(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.password'), [
                'current_password' => 'wrong-password',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_update_password_with_correct_current_password_succeeds(): void
    {
        $response = $this->actingAs($this->user())
            ->patch(route('user.update.password'), [
                'current_password' => 'test123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect(route('user.settings'));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $this->user()->password));
    }
}
