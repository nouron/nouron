<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\ParallelTesting;

/**
 * Laravel switches a paratest worker to its own database (nouron_test_test_N) only for test
 * cases that use one of the database traits. Tests that deliberately avoid them (they run
 * migrate:fresh themselves) would otherwise all share the base nouron_test database and
 * collide with each other under `php artisan test --parallel`.
 *
 * Call useParallelTestDatabase() right after parent::setUp().
 */
trait UsesParallelTestDatabase
{
    protected function useParallelTestDatabase(): void
    {
        $token = ParallelTesting::token();
        if ($token === false) {
            return;
        }

        $default = config('database.default');
        $database = config("database.connections.{$default}.database");
        $suffix = "_test_{$token}";
        if (str_ends_with((string) $database, $suffix)) {
            return;
        }

        DB::purge();
        config(["database.connections.{$default}.database" => $database.$suffix]);
    }
}
