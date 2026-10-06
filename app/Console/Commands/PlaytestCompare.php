<?php

namespace App\Console\Commands;

use App\Support\OpeningComparison;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Tests\Feature\Playtest\BotProfile;
use Throwable;

/**
 * T30 step 0: paired comparison of PlaytestBot openings by the equivalence
 * criteria K1-K7 (spec 2026-10-05, section 2.1). Read-only: reads report
 * JSONs written by game:playtest ({profile}-{opening}-{seed}-{Ymd_His}.json),
 * takes the newest report per (profile, opening, seed) and prints KPI
 * medians/ranges, the per-seed deltas to `labor` and the threshold check.
 * KPI definitions: App\Support\OpeningComparison.
 *
 * Run: php artisan game:playtest-compare --openings=labor,hangar,cantina --seeds=1,2,3 --since=2026-10-06
 */
class PlaytestCompare extends Command
{
    protected $signature = 'game:playtest-compare
        {--profile=default : BotProfile name of the reports to compare}
        {--openings=labor,hangar,cantina : Comma-separated openings (auto, labor, hangar, cantina)}
        {--seeds= : Comma-separated seeds (default: all found)}
        {--since= : Only reports written at/after this date/time (e.g. 2026-10-06 or "2026-10-06 14:00")}
        {--dir= : Report directory (default: storage/logs/playtest)}';

    protected $description = 'Compare PlaytestBot openings (K1-K7, paired by seed) from existing report JSONs';

    public function handle(): int
    {
        $openings = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('openings')))));
        $unknown = array_diff($openings, BotProfile::OPENINGS);
        if ($openings === [] || $unknown !== []) {
            $this->error('Unknown opening(s): '.implode(', ', $unknown ?: ['(none)']).'. Allowed: '.implode(', ', BotProfile::OPENINGS));

            return self::FAILURE;
        }

        $since = null;
        if ($this->option('since')) {
            try {
                $since = Carbon::parse((string) $this->option('since'))->format('Ymd_His');
            } catch (Throwable) {
                $this->error('Invalid --since date: '.$this->option('since'));

                return self::FAILURE;
            }
        }

        $seeds = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('seeds'))), fn ($s) => $s !== ''));
        $profile = (string) $this->option('profile');
        $dir = rtrim((string) ($this->option('dir') ?: storage_path('logs/playtest')), '/');

        $reports = [];
        foreach ($openings as $opening) {
            // Filenames sort chronologically by their Ymd_His suffix; later entries overwrite earlier ones.
            $files = glob("{$dir}/{$profile}-{$opening}-*-*.json") ?: [];
            sort($files);
            $newest = [];
            foreach ($files as $file) {
                if (! preg_match('/^'.preg_quote("{$profile}-{$opening}-", '/').'(\d+)-(\d{8}_\d{6})\.json$/', basename($file), $m)) {
                    continue;
                }
                if (($seeds !== [] && ! in_array($m[1], $seeds, true)) || ($since !== null && $m[2] < $since)) {
                    continue;
                }
                $newest[$m[1]] = $file;
            }
            foreach ($newest as $file) {
                $report = json_decode((string) file_get_contents($file), true);
                if (is_array($report)) {
                    $reports[] = $report;
                }
            }
        }

        $result = OpeningComparison::fromReports($reports, $openings)->compare();

        $this->info("Profile: {$profile} — openings: ".implode(', ', $openings).' — reference: '.$result['reference']);
        $tooShort = count(array_filter($reports, fn ($r) => isset($r['truncated_at_sol']) && $r['truncated_at_sol'] < OpeningComparison::MIN_MEANINGFUL_TRUNCATION));
        if ($tooShort > 0) {
            $this->warn("Warning: {$tooShort} run(s) truncated before Sol ".OpeningComparison::MIN_MEANINGFUL_TRUNCATION.' — K3/K5/K6 are not meaningful for them.');
        }
        $this->line('Paired seeds: '.($result['paired_seeds'] ? implode(', ', $result['paired_seeds']) : '(none)'));
        if ($result['skipped_seeds'] !== []) {
            $this->line('Skipped seeds: '.implode('; ', array_map(
                fn ($seed, $why) => "{$seed} ({$why})",
                array_keys($result['skipped_seeds']),
                $result['skipped_seeds'],
            )));
        }

        $rows = [];
        foreach ($result['kpis'] as $k => $byOpening) {
            foreach ($byOpening as $opening => $s) {
                $rows[] = [
                    strtoupper($k),
                    $opening,
                    OpeningComparison::fmt($s['median']),
                    OpeningComparison::fmt($s['min']).'..'.OpeningComparison::fmt($s['max']),
                    $s['missing'],
                    $s['delta'] ? OpeningComparison::fmt($s['delta']['median']) : '-',
                    $s['delta'] ? OpeningComparison::fmt($s['delta']['min']).'..'.OpeningComparison::fmt($s['delta']['max']) : '-',
                ];
            }
        }
        foreach ($result['k7'] as $opening => $s) {
            if ($s['runs'] === 0 && $s['truncated'] > 0) {
                $rows[] = ['K7', $opening, 'nicht gemessen', "nicht gemessen ({$s['truncated']} truncated)", '-', '-', '-'];

                continue;
            }
            $rows[] = ['K7', $opening, OpeningComparison::fmt($s['win_rate']).' % won', "{$s['wins']}/{$s['runs']}", '-', 'win Sol '.OpeningComparison::fmt($s['median_win_sol']), '-'];
        }
        $this->table(['KPI', 'Opening', 'Median', 'Range', 'Null', 'Δ labor median', 'Δ labor range'], $rows);

        $this->table(
            ['Criterion', 'Status', 'Detail'],
            array_map(fn ($id, $c) => [$id, $c['status'], $c['detail']], array_keys($result['checks']), $result['checks']),
        );

        return self::SUCCESS;
    }
}
