<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
        if ($firstRefresh && TestSchemaCache::forCurrentConnection()->isCurrent()) {
            TestSchemaCache::emptyTables();
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
