<?php

namespace Tests\Feature\Console;

use App\Console\Support\PlaytestDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * game:playtest resets its database with migrate:fresh — it must refuse any
 * database that is not exactly the configured, dedicated playtest database.
 *
 * Safety net for these tests themselves: the mysql connection points at a
 * closed port and child processes are faked, so a broken guard fails with a
 * connection error instead of wiping a real database.
 */
class PlaytestCommandGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.mysql.port' => 1]);
        Process::fake();
    }

    public function test_refuses_when_playtest_database_equals_default_database(): void
    {
        config(['database.connections.mysql.database' => 'nouron_guard_x']);
        config(['game.playtest.database' => 'nouron_guard_x']);

        $this->artisan('game:playtest', ['--seeds' => '1'])
            ->expectsOutputToContain('refuse')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_refuses_the_dev_database_even_if_it_is_not_the_default(): void
    {
        config(['database.connections.mysql.database' => 'something_else']);
        config(['game.playtest.database' => 'nouron']);

        $this->artisan('game:playtest', ['--seeds' => '1'])
            ->expectsOutputToContain('refuse')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_refuses_the_test_database_even_if_it_is_not_the_default(): void
    {
        config(['database.connections.mysql.database' => 'something_else']);
        config(['game.playtest.database' => 'nouron_test']);

        $this->artisan('game:playtest', ['--seeds' => '1'])
            ->expectsOutputToContain('refuse')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_refuses_an_empty_playtest_database_name(): void
    {
        config(['game.playtest.database' => '']);

        $this->artisan('game:playtest', ['--seeds' => '1'])
            ->expectsOutputToContain('refuse')
            ->assertFailed();
    }

    public function test_guard_accepts_the_dedicated_playtest_database(): void
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'nouron']);
        config(['game.playtest.database' => 'nouron_playtest']);

        $this->assertSame('nouron_playtest', app(PlaytestDatabase::class)->name());
    }

    public function test_children_get_the_shared_playtest_database(): void
    {
        config(['game.playtest.database' => 'nouron_playtest']);
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1', '--concurrency' => 1]);

        Process::assertRan(fn (PendingProcess $process) => $process->environment['PLAYTEST_SHARED_DB'] === '1'
            && $process->environment['PLAYTEST_DATABASE'] === 'nouron_playtest'
            && $process->environment['DB_CONNECTION'] === 'mysql');
    }
}
