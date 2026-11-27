<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Under MySQL's default REPEATABLE READ, deletes and inserts of different
 * colonies deadlock on gap locks (bot batches, and any two players ticking at
 * the same time). The game connection therefore runs READ COMMITTED.
 */
class DatabaseIsolationLevelTest extends TestCase
{
    public function test_the_game_connection_runs_read_committed(): void
    {
        $this->assertSame('READ-COMMITTED', DB::selectOne('select @@transaction_isolation as level')->level);
    }
}
