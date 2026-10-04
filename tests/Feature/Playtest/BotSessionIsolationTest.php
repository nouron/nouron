<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R5b: parallel bots share one database like real players do — so every
 * boot() must create its own user + colony + run instead of resetting a
 * fixed fixture colony (user 3 / colony 1) that another bot is playing on.
 */
class BotSessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_bot_sessions_get_separate_users_and_colonies(): void
    {
        $a = BotSession::boot($this, seed: 1);
        $b = BotSession::boot($this, seed: 2);

        $this->assertNotSame($a->userId, $b->userId);
        $this->assertNotSame($a->colonyId, $b->colonyId);
        $this->assertNotSame($a->runId, $b->runId);
    }

    /**
     * Shared mode commits its data — so it must refuse to run on any connection
     * other than the playtest database (here: the test database nouron_test).
     */
    public function test_shared_mode_refuses_a_connection_that_is_not_the_playtest_database(): void
    {
        putenv('PLAYTEST_SHARED_DB=1');
        $error = null;

        try {
            BotSession::boot($this, seed: 1);
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        } finally {
            putenv('PLAYTEST_SHARED_DB');
        }

        $this->assertNotNull($error, 'boot() must refuse a non-playtest database in shared mode');
        $this->assertStringContainsString('playtest database', $error);
        $this->assertSame(0, DB::table('user')->where('username', 'like', 'bot\_%')->count());
    }
}
