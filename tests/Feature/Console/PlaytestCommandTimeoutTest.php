<?php

namespace Tests\Feature\Console;

use App\Console\Support\PlaytestDatabase;
use Tests\TestCase;

/**
 * One child exceeding the process timeout used to abort the whole command with
 * a ProcessTimedOutException — no table, no result for the other seeds (R5b
 * batch 2026-10-04). A timed-out child must be reported per profile/seed and
 * the command must carry on.
 *
 * Real child processes run here: the mysql host is a blackhole address, so a
 * child can neither finish before the 1-second timeout nor reach any database.
 */
class PlaytestCommandTimeoutTest extends TestCase
{
    public function test_a_timed_out_child_is_reported_and_the_others_still_complete(): void
    {
        config([
            'database.connections.mysql.host' => '10.255.255.1',
            'game.playtest.process_timeout' => 1,
        ]);
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1,2', '--concurrency' => 2])
            ->expectsOutputToContain('profile=default opening=auto seed=1 timed out')
            ->expectsOutputToContain('profile=default opening=auto seed=2 timed out')
            ->assertSuccessful();
    }
}
