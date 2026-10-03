<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

/**
 * DbReset had 0% test coverage. Safe to actually run here — phpunit.xml forces the
 * test database (nouron_test, or nouron_test_test_N per parallel worker), never
 * the dev database.
 *
 * Deliberately NOT using RefreshDatabase: migrate:fresh is DDL, which cannot run
 * inside RefreshDatabase's wrapping transaction (MySQL commits it implicitly) —
 * this command performs its own full reset per test.
 *
 * A reset leaves the test database migrated AND seeded, while RefreshDatabase
 * expects an empty schema once it has migrated. tearDown therefore marks the
 * database as not migrated, so the next RefreshDatabase test runs migrate:fresh.
 */
class DbResetTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

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
