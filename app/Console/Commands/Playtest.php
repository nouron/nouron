<?php

namespace App\Console\Commands;

use App\Console\Concerns\RefusesInProduction;
use App\Console\Support\PlaytestDatabase;
use Illuminate\Console\Command;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Playtest — orchestrates the PlaytestBot (tests/Feature/Playtest/) across
 * multiple seeds and/or playstyle profiles, and prints a comparison table.
 *
 * Run:   php artisan game:playtest --profiles=default,thrifty --seeds=4242,1337,9001
 * Single combo (equivalent to running the PHPUnit test directly):
 *        php artisan game:playtest --seeds=4242
 * Parallel (10 at a time, e.g. for a 100+-seed sweep):
 *        php artisan game:playtest --seeds=1,2,...,100 --concurrency=10
 *
 * Shells out to PHPUnit per (profile × seed) combination — the bot itself
 * still drives real HTTP requests through PlaytestBotTest/BotSession exactly
 * as it does today; this command only automates what was previously a
 * manual sed-loop plus manual JSON inspection. Combos run in parallel
 * batches of --concurrency each (default 10).
 *
 * Shared database (R5b, ADR 0005): all runs of one invocation play in ONE
 * MySQL database (config game.playtest.database, default nouron_playtest),
 * each run with its own user + colony — the same multi-player model as
 * production. The parent resets it once (migrate:fresh + ReferenceDataSeeder)
 * via PlaytestDatabase, which refuses any name that is empty, nouron,
 * nouron_test, or the database the default/mysql connection points at. The
 * children get PLAYTEST_SHARED_DB=1 + PLAYTEST_DATABASE: because phpunit.xml
 * forces DB_DATABASE=nouron_test, PlaytestBotTest switches its connection at
 * runtime (PlaytestDatabase::connect(), same guard) instead of relying on the
 * env, and skips the RefreshDatabase rollback so data is committed.
 * The parent itself needs MySQL credentials (DB_HOST/PORT/USERNAME/PASSWORD
 * from .env or the environment); they are passed on to the children.
 * See docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md.
 */
class Playtest extends Command
{
    use RefusesInProduction;

    protected $signature = 'game:playtest
        {--profiles=default : Comma-separated BotProfile names}
        {--seeds=4242 : Comma-separated integer seeds}
        {--concurrency=10 : How many profile×seed combos to run at once}';

    private const OUTPUT_TAIL_CHARS = 4000;

    protected $description = 'Run the PlaytestBot across seeds/profiles (in parallel batches) and print a comparison table';

    public function handle(): int
    {
        if ($this->refusesInProduction()) {
            return self::FAILURE;
        }

        // Fresh shared DB for the whole invocation; every bot run creates its own
        // user + colony in it. Host/credentials are captured before the reset so the
        // children connect exactly where the parent seeded.
        $mysql = config('database.connections.mysql');
        try {
            $playtestDb = app(PlaytestDatabase::class)->reset();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $profiles = array_filter(array_map('trim', explode(',', (string) $this->option('profiles'))));
        $seeds = array_filter(array_map('trim', explode(',', (string) $this->option('seeds'))));
        $concurrency = max(1, (int) $this->option('concurrency'));

        $combos = [];
        foreach ($profiles as $profile) {
            foreach ($seeds as $seed) {
                $combos[] = [$profile, $seed];
            }
        }

        $rows = [];

        foreach (array_chunk($combos, $concurrency) as $batch) {
            $labels = collect($batch)->map(fn ($c) => "{$c[0]}={$c[1]}")->implode(', ');
            $this->line('Running batch: '.$labels);

            // Started one by one (not via Process::pool) so a child that exceeds the
            // timeout can be caught per run below — a pool's wait() aborts on the first
            // ProcessTimedOutException and loses every other result (R5b, 2026-10-04).
            $running = [];
            foreach ($batch as [$profile, $seed]) {
                // Every child plays in the shared playtest DB the parent just reset.
                // phpunit.xml forces DB_DATABASE=nouron_test, so DB_DATABASE would
                // not reach the child (and is deliberately not passed: the guard
                // requires the playtest DB to differ from the configured one).
                // PLAYTEST_SHARED_DB makes PlaytestBotTest switch its connection
                // to PLAYTEST_DATABASE at runtime (PlaytestDatabase::connect(),
                // same guard as here) and skip the rollback, so the run's data
                // is committed like a real player's.
                $running["{$profile}-{$seed}"] = Process::env([
                    'PLAYTEST_PROFILE' => $profile,
                    'PLAYTEST_SEED' => $seed,
                    'APP_ENV' => 'testing',
                    'PLAYTEST_SHARED_DB' => '1',
                    'PLAYTEST_DATABASE' => $playtestDb,
                    'DB_CONNECTION' => 'mysql',
                    'DB_HOST' => (string) $mysql['host'],
                    'DB_PORT' => (string) $mysql['port'],
                    'DB_USERNAME' => (string) $mysql['username'],
                    'DB_PASSWORD' => (string) $mysql['password'],
                ])
                    // History and reasoning of the value: config/game.php playtest.process_timeout.
                    ->timeout((int) config('game.playtest.process_timeout'))
                    ->start([
                        // opcache.enable_cli defaults to Off system-wide, so every
                        // spawned child cold-compiles the whole vendor tree from
                        // scratch — measured ~47s for a single run just from that.
                        // Forcing it on here (scoped to this process only, no global
                        // php.ini change) lets concurrency scale past ~2 without
                        // hitting the timeout below (found 2026-08-17).
                        'php', '-d', 'opcache.enable_cli=1',
                        'bin/phpunit',
                        '--filter', 'test_bot_plays_a_full_run_and_produces_a_report',
                        'tests/Feature/Playtest/PlaytestBotTest.php',
                    ]);
            }

            foreach ($batch as [$profile, $seed]) {
                try {
                    $result = $running["{$profile}-{$seed}"]->wait();
                } catch (ProcessTimedOutException) {
                    $this->error("profile={$profile} seed={$seed} timed out after ".config('game.playtest.process_timeout').'s');
                    $rows[] = [$profile, $seed, 'timed out', '-', '-', '-', '-'];

                    continue;
                }

                if (! $result->successful()) {
                    $this->error("profile={$profile} seed={$seed} failed to run:");
                    // PHPUnit reports test failures on stdout, not stderr — printing
                    // only errorOutput() left this message empty (baseline 2026-09-26).
                    $this->line(self::tail($result->output()));
                    $this->line(self::tail($result->errorOutput()));

                    continue;
                }

                $report = $this->latestReportFor($profile, $seed);
                if ($report === null) {
                    $this->error("profile={$profile} seed={$seed}: no report file found after run");

                    continue;
                }

                $rows[] = $this->summarize($profile, $seed, $report);
            }
        }

        $this->table(
            ['Profile', 'Seed', 'Status', 'Fail Reason', 'Phase2 Sol', 'Objectives Done', 'Score'],
            $rows
        );

        return self::SUCCESS;
    }

    /** Last OUTPUT_TAIL_CHARS of a child's output — the failure summary sits at the end. */
    private static function tail(string $output): string
    {
        $output = trim($output);

        return strlen($output) > self::OUTPUT_TAIL_CHARS
            ? '...'.substr($output, -self::OUTPUT_TAIL_CHARS)
            : $output;
    }

    private function latestReportFor(string $profile, string $seed): ?array
    {
        $pattern = storage_path("logs/playtest/{$profile}-{$seed}-*.json");
        $matches = glob($pattern) ?: [];
        if ($matches === []) {
            return null;
        }

        sort($matches);
        $latest = end($matches);

        return json_decode(file_get_contents($latest), true);
    }

    private function summarize(string $profile, string $seed, array $report): array
    {
        $completed = collect($report['objectives'] ?? [])
            ->filter(fn ($o) => $o['completed_at'] !== null)
            ->count();
        $total = count($report['objectives'] ?? []);

        return [
            $profile,
            $seed,
            $report['outcome']['status'] ?? '?',
            $report['outcome']['fail_reason'] ?? '-',
            $report['phase2_start_sol'] ?? '-',
            "{$completed}/{$total}",
            $report['outcome']['score'] ?? 0,
        ];
    }
}
