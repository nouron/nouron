<?php

namespace Tests\Feature\Auth;

use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PDOException;
use Tests\TestCase;

/**
 * R5b: a failed registration must not write the SQL statement with its bindings
 * (e-mail address, bcrypt hash) to the log — only exception class and error codes.
 */
class RegistrationFailureLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_exception_is_logged_without_sql_or_bindings(): void
    {
        $this->app->make(TestSeeder::class)->run();

        $pdo = new PDOException('boom');
        $pdo->errorInfo = ['23000', 1062, 'Duplicate entry'];
        $this->mock(OnboardingService::class, function ($mock) use ($pdo) {
            $mock->shouldReceive('setupNewPlayer')->andThrow(
                new QueryException('mysql', 'insert into user (email, password) values (?, ?)', ['leak@example.com', '$2y$hash'], $pdo)
            );
        });

        Log::spy();

        $this->post(route('register'), [
            'username' => 'logtester',
            'email' => 'leak@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ])->assertSessionHasErrors('username');

        Log::shouldHaveReceived('error')->once()->withArgs(function (string $message, array $context = []) {
            $dump = $message.json_encode($context);

            return ! str_contains($dump, 'leak@example.com')
                && ! str_contains($dump, 'insert into')
                && ! str_contains($dump, '$2y$')
                && ($context['sqlstate'] ?? null) === '23000'
                && ($context['driver_code'] ?? null) === 1062
                && ($context['exception'] ?? null) === QueryException::class;
        });
    }
}
