<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;

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
 * batches of --concurrency each (default 10): every PlaytestBotTest run gets
 * its own PHP process with its own `:memory:` SQLite DB (phpunit.xml), so
 * concurrent runs never share state — the only real limit is host CPU/RAM.
 * See docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md.
 */
class Playtest extends Command
{
    protected $signature = 'game:playtest
        {--profiles=default : Comma-separated BotProfile names}
        {--seeds=4242 : Comma-separated integer seeds}
        {--concurrency=10 : How many profile×seed combos to run at once}';

    protected $description = 'Run the PlaytestBot across seeds/profiles (in parallel batches) and print a comparison table';

    public function handle(): int
    {
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

            $results = Process::pool(function (Pool $pool) use ($batch) {
                foreach ($batch as [$profile, $seed]) {
                    $pool->as("{$profile}-{$seed}")
                        // Spawned from an already-booted artisan process, phpunit.xml's
                        // force="true" DB_DATABASE=:memory: override is NOT reliable —
                        // the child can inherit the parent's already-resolved real .env
                        // DB_DATABASE (data/db/nouron.db) instead (found 2026-08-16 after
                        // a parallel run corrupted the dev DB). Force it explicitly here
                        // so a child never touches the real database regardless of that
                        // inheritance quirk.
                        ->env([
                            'PLAYTEST_PROFILE' => $profile,
                            'PLAYTEST_SEED' => $seed,
                            'APP_ENV' => 'testing',
                            'DB_CONNECTION' => 'sqlite',
                            'DB_DATABASE' => ':memory:',
                        ])
                        ->timeout(120)
                        ->command([
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
            })->wait();
            $resultsByKey = $results->collect();

            foreach ($batch as [$profile, $seed]) {
                $result = $resultsByKey->get("{$profile}-{$seed}");

                if ($result === null) {
                    $this->error("profile={$profile} seed={$seed}: no process result found (pool key mismatch)");

                    continue;
                }

                if (! $result->successful()) {
                    $this->error("profile={$profile} seed={$seed} failed to run:");
                    $this->line($result->errorOutput());

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
