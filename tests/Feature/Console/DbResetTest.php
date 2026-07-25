<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

/**
 * DbReset had 0% test coverage. Safe to actually run here — the test environment
 * always points at the in-memory sqlite connection (phpunit.xml), never the real
 * dev database (data/db/nouron.db).
 *
 * Deliberately NOT using RefreshDatabase: migrate:fresh's underlying wipe (VACUUM)
 * cannot run inside RefreshDatabase's wrapping transaction ("cannot VACUUM from
 * within a transaction") — this command performs its own full reset per test.
 */
class DbResetTest extends TestCase
{
    public function test_aborts_without_confirmation(): void
    {
        $this->artisan('db:reset')
            ->expectsConfirmation('Are you sure?', 'no')
            ->expectsOutputToContain('Aborted.')
            ->assertExitCode(0);
    }

    public function test_confirmed_resets_and_seeds_the_database(): void
    {
        $this->artisan('db:reset')
            ->expectsConfirmation('Are you sure?', 'yes')
            ->expectsOutputToContain('Resetting database...')
            ->expectsOutputToContain('Done. Database has been reset and seeded.')
            ->assertExitCode(0);
    }

    public function test_force_flag_skips_confirmation(): void
    {
        $this->artisan('db:reset', ['--force' => true])
            ->doesntExpectOutputToContain('This will DELETE all data')
            ->assertExitCode(0);
    }
}
