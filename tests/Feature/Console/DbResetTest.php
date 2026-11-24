<?php

namespace Tests\Feature\Console;

use Tests\Concerns\UsesParallelTestDatabase;
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
 * A reset leaves the test database migrated and seeded with the dev seeders;
 * tearDown restores the shared test fixtures, so the next test of this process
 * needs no migrate:fresh.
 */
class DbResetTest extends TestCase
{
    use UsesParallelTestDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useParallelTestDatabase();
    }

    protected function tearDown(): void
    {
        self::restoreTestBaseline();

        parent::tearDown();
    }

    public function test_aborts_without_confirmation(): void
    {
        $this->artisan('db:reset')
            ->expectsConfirmation('Are you sure?', 'no')
            ->expectsOutputToContain('Aborted.')
            ->assertExitCode(0);
    }

    // One real reset is enough (migrate:fresh --seed takes ~15 s): --force skips the
    // confirmation that the abort test above covers, and the reset itself runs here.
    public function test_force_flag_skips_confirmation_and_resets_and_seeds(): void
    {
        $this->artisan('db:reset', ['--force' => true])
            ->doesntExpectOutputToContain('This will DELETE all data')
            ->expectsOutputToContain('Resetting database...')
            ->expectsOutputToContain('Done. Database has been reset and seeded.')
            ->assertExitCode(0);
    }
}
