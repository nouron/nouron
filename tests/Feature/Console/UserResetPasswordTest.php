<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * R10 — `php artisan user:reset-password <username|email>` sets a fresh random
 * password and prints it once, so the admin can hand it to the tester
 * personally (closed beta, no mail service yet).
 *
 * Fixture: Bart (user_id 3) from TestSeeder.
 */
class UserResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function newPasswordFromOutput(string $output): string
    {
        $this->assertMatchesRegularExpression('/New password: (\S+)/', $output);
        preg_match('/New password: (\S+)/', $output, $m);

        return $m[1];
    }

    public function test_resets_password_by_username_and_prints_it(): void
    {
        $bart = User::findOrFail(3);
        $oldHash = $bart->password;

        $exit = Artisan::call('user:reset-password', ['user' => $bart->username]);

        $this->assertSame(0, $exit);
        $password = $this->newPasswordFromOutput(Artisan::output());
        $this->assertGreaterThanOrEqual(12, strlen($password));

        $bart->refresh();
        $this->assertNotSame($oldHash, $bart->password);
        $this->assertTrue(Hash::check($password, $bart->password));
    }

    public function test_resets_password_by_email(): void
    {
        $bart = User::findOrFail(3);

        $exit = Artisan::call('user:reset-password', ['user' => $bart->email]);

        $this->assertSame(0, $exit);
        $password = $this->newPasswordFromOutput(Artisan::output());
        $this->assertTrue(Hash::check($password, $bart->refresh()->password));
    }

    public function test_new_password_allows_login(): void
    {
        $bart = User::findOrFail(3);
        Artisan::call('user:reset-password', ['user' => $bart->username]);
        $password = $this->newPasswordFromOutput(Artisan::output());

        $this->post(route('login'), ['username' => $bart->username, 'password' => $password]);

        $this->assertAuthenticatedAs($bart);
    }

    public function test_unknown_user_fails_without_changing_anything(): void
    {
        $hashesBefore = User::pluck('password', 'user_id')->all();

        $exit = Artisan::call('user:reset-password', ['user' => 'nobody-here']);

        $this->assertSame(1, $exit);
        $this->assertStringNotContainsString('New password:', Artisan::output());
        $this->assertSame($hashesBefore, User::pluck('password', 'user_id')->all());
    }

    public function test_each_reset_generates_a_different_password(): void
    {
        Artisan::call('user:reset-password', ['user' => 'Bart']);
        $first = $this->newPasswordFromOutput(Artisan::output());
        Artisan::call('user:reset-password', ['user' => 'Bart']);
        $second = $this->newPasswordFromOutput(Artisan::output());

        $this->assertNotSame($first, $second);
    }
}
