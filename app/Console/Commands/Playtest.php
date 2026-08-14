<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Playtest — orchestrates the PlaytestBot (tests/Feature/Playtest/) across
 * multiple seeds and/or playstyle profiles, and prints a comparison table.
 *
 * Run:   php artisan game:playtest --profiles=default,thrifty --seeds=4242,1337,9001
 * Single combo (equivalent to running the PHPUnit test directly):
 *        php artisan game:playtest --seeds=4242
 *
 * Shells out to PHPUnit per (profile × seed) combination — the bot itself
 * still drives real HTTP requests through PlaytestBotTest/BotSession exactly
 * as it does today; this command only automates what was previously a
 * manual sed-loop plus manual JSON inspection. See
 * docs/superpowers/specs/2026-08-14-bot-playstyle-profiles-design.md.
 */
class Playtest extends Command
{
    protected $signature = 'game:playtest
        {--profiles=default : Comma-separated BotProfile names}
        {--seeds=4242 : Comma-separated integer seeds}';

    protected $description = 'Run the PlaytestBot across seeds/profiles and print a comparison table';

    public function handle(): int
    {
        $profiles = array_filter(array_map('trim', explode(',', (string) $this->option('profiles'))));
        $seeds = array_filter(array_map('trim', explode(',', (string) $this->option('seeds'))));

        $rows = [];

        foreach ($profiles as $profile) {
            foreach ($seeds as $seed) {
                $this->line("Running profile={$profile} seed={$seed}...");

                $result = Process::env([
                    'PLAYTEST_PROFILE' => $profile,
                    'PLAYTEST_SEED' => $seed,
                ])->timeout(120)->run([
                    'php', 'bin/phpunit',
                    '--filter', 'test_bot_plays_a_full_run_and_produces_a_report',
                    'tests/Feature/Playtest/PlaytestBotTest.php',
                ]);

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
