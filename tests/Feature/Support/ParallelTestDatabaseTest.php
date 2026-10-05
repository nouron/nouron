<?php

namespace Tests\Feature\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;
use Tests\Concerns\UsesParallelTestDatabase;
use Tests\TestCase;

/**
 * A trait-less test (no RefreshDatabase etc.) must still end up on its paratest worker
 * database, never on the shared base database, when it opts in via the helper.
 */
class ParallelTestDatabaseTest extends TestCase
{
    use UsesParallelTestDatabase;

    public function test_helper_moves_a_trait_less_test_to_the_worker_database(): void
    {
        $this->useParallelTestDatabase();

        $token = ParallelTesting::token();
        $expected = $token === false ? 'nouron_test' : "nouron_test_test_{$token}";

        $this->assertSame($expected, DB::connection()->getDatabaseName());
    }

    public function test_helper_is_idempotent(): void
    {
        $this->useParallelTestDatabase();
        $first = DB::connection()->getDatabaseName();
        $this->useParallelTestDatabase();

        $this->assertSame($first, DB::connection()->getDatabaseName());
    }
}
