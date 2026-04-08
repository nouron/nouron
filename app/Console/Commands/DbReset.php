<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * DbReset — drops and recreates the development database, then seeds it.
 *
 * Run:           php artisan db:reset
 * Skip confirm:  php artisan db:reset --force
 *
 * Equivalent to: php artisan migrate:fresh --seed
 * but with an explicit warning and confirmation prompt so it
 * cannot be triggered accidentally.
 *
 * Only intended for the development database (nouron.db).
 * Never run in a production environment.
 */
class DbReset extends Command
{
    protected $signature   = 'db:reset {--force : Skip the confirmation prompt}';
    protected $description = 'Drop all tables, run migrations, and seed the development database';

    public function handle(): int
    {
        $db = config('database.connections.' . config('database.default') . '.database');

        if (!$this->option('force')) {
            $this->warn('This will DELETE all data in: ' . $db);
            if (!$this->confirm('Are you sure?', false)) {
                $this->line('Aborted.');
                return self::SUCCESS;
            }
        }

        $this->info('Resetting database...');

        $this->call('migrate:fresh', ['--seed' => true]);

        $this->info('Done. Database has been reset and seeded.');

        return self::SUCCESS;
    }
}
