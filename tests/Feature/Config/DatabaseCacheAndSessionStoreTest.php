<?php

namespace Tests\Feature\Config;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * R3: production uses the database cache and session drivers (several app
 * instances, no shared filesystem). The /sol/next lock (R11) needs atomic locks
 * from that store, and the session driver needs its table.
 */
class DatabaseCacheAndSessionStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_cache_store_supports_the_sol_advance_lock(): void
    {
        $store = Cache::store('database');

        $first = $store->lock('run:1:sol-advance', 120);
        $this->assertTrue($first->get());
        $this->assertFalse($store->lock('run:1:sol-advance', 120)->get(), 'a second advance must be refused');

        $first->release();
        $this->assertTrue($store->lock('run:1:sol-advance', 120)->get(), 'lock is free again after release');
    }

    public function test_session_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
    }
}
