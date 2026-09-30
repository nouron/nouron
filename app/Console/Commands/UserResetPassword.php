<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * UserResetPassword — admin fallback for forgotten passwords (closed beta, R10).
 *
 * Sets a fresh random password and prints it once; the admin hands it to the
 * tester personally. There is no mail-based reset yet (needs a mail service).
 *
 * Usage:
 *   php artisan user:reset-password Bart
 *   php artisan user:reset-password bart@nouron.de
 */
class UserResetPassword extends Command
{
    protected $signature = 'user:reset-password {user : Username or e-mail address}';

    protected $description = 'Set a new random password for a user and print it';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $identifier)->first();
        if ($user === null) {
            $this->error("No user with {$field} '{$identifier}'.");

            return self::FAILURE;
        }

        $password = Str::password(16, symbols: false);
        $user->password = $password; // hashed via the model cast
        $user->save();

        $this->info("Password reset for {$user->username} (#{$user->user_id}).");
        $this->line("New password: {$password}");

        return self::SUCCESS;
    }
}
