<?php

namespace Tests;

use Database\Seeders\TestSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\TestSchemaCache;

abstract class TestCase extends BaseTestCase
{
    /**
     * T58d: the first RefreshDatabase test of a process skips migrate:fresh when the test
     * database was built from the same migrations (TestSchemaCache) and only empties it.
     */
    protected function setUpTraits()
    {
        $firstRefresh = ! RefreshDatabaseState::$migrated
            && isset(array_flip(class_uses_recursive(static::class))[RefreshDatabase::class]);
        // T63 experiment: seed the test fixtures once per process, committed, before the first
        // test transaction; every test then rolls back to this state.
        if ($firstRefresh) {
            $cache = TestSchemaCache::forCurrentConnection();
            if ($cache->isCurrent()) {
                TestSchemaCache::emptyTables();
            } else {
                Artisan::call('migrate:fresh', ['--force' => true]);
                $cache->remember();
            }
            TestSeeder::$baseline = false;
            $this->app->make(TestSeeder::class)->run();
            TestSeeder::$baseline = true;
            RefreshDatabaseState::$migrated = true;
            $firstRefresh = false;
        }

        $uses = parent::setUpTraits();

        if ($firstRefresh && RefreshDatabaseState::$migrated) {
            TestSchemaCache::forCurrentConnection()->remember();
        }

        return $uses;
    }

    /**
     * For tests that commit their own data outside RefreshDatabase's transaction: bring the
     * database back to the shared seeded state (DML only, no migrate:fresh), so the next test
     * of this process can start from it.
     */
    protected static function restoreTestBaseline(): void
    {
        TestSchemaCache::emptyTables();
        TestSeeder::$baseline = false;
        app(TestSeeder::class)->run();
        TestSeeder::$baseline = true;
        RefreshDatabaseState::$migrated = true;
    }

    /**
     * Log in like the SvelteKit frontend: the API only accepts Sanctum tokens
     * (ADR 0006), so a session login would be "Unauthenticated".
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);

        return $this;
    }
}
