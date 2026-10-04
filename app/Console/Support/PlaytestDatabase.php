<?php

namespace App\Console\Support;

use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The shared MySQL database of the playtest bot (R5b, ADR 0005).
 *
 * game:playtest resets it once per invocation (reset()), every bot child
 * process switches its default connection onto it (connect()) — both only
 * after name() has verified it is exactly the dedicated playtest database
 * and never the dev or test database.
 */
class PlaytestDatabase
{
    /** Never reset or written by the bot, whatever the configuration says. */
    private const FORBIDDEN = ['nouron', 'nouron_test'];

    /**
     * The validated playtest database name (config game.playtest.database).
     *
     * @throws RuntimeException when it is empty, a forbidden name, or equals the
     *                          database the default or mysql connection points at
     */
    public function name(): string
    {
        $name = (string) config('game.playtest.database');
        $default = (string) config('database.connections.'.config('database.default').'.database');
        $mysql = (string) config('database.connections.mysql.database');

        if ($name === '' || in_array($name, self::FORBIDDEN, true) || $name === $default || $name === $mysql) {
            throw new RuntimeException(
                "Playtest database '{$name}' must be a dedicated database, different from the default "
                ."('{$default}'), the mysql connection ('{$mysql}') and from ".implode('/', self::FORBIDDEN)
                .' — refuse to use it.'
            );
        }

        return $name;
    }

    /** Points the default (mysql) connection of this process at the playtest database. */
    public function connect(): string
    {
        $name = $this->name();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => $name,
        ]);
        DB::purge('mysql');

        $actual = self::serverDatabase('mysql');
        if ($actual !== $name) {
            throw new RuntimeException("Expected playtest database '{$name}', connected to '{$actual}' — refuse to use it.");
        }

        return $name;
    }

    /** Fails unless this process' default connection already is the playtest database (after connect()). */
    public function assertConnected(): void
    {
        $name = (string) config('game.playtest.database');
        $actual = self::serverDatabase(null);

        if ($name === '' || in_array($actual, self::FORBIDDEN, true) || $actual !== $name) {
            throw new RuntimeException("Shared playtest mode is connected to '{$actual}', not the playtest database '{$name}' — refuse to use it.");
        }
    }

    /** The database the server session actually uses — not just what the config claims. */
    private static function serverDatabase(?string $connection): string
    {
        return (string) DB::connection($connection)->selectOne('select database() as db')->db;
    }

    /** Fresh schema + reference data, once per game:playtest invocation (parent process). */
    public function reset(): string
    {
        $name = $this->connect();

        Artisan::call('migrate:fresh', ['--database' => 'mysql', '--force' => true]);
        app(ReferenceDataSeeder::class)->run();

        return $name;
    }
}
