<?php

namespace App\Support;

/**
 * T30 step 0: paired comparison of PlaytestBot openings (labor / hangar /
 * cantina) by the equivalence criteria K1-K7 of
 * docs/superpowers/specs/2026-10-05-t30-gleichwertige-eroeffnungen.md (2.1).
 *
 * Pure functions on decoded RunReport::build() arrays — no DB, no files.
 *
 * Report fields used: `opening`, `seed`, `outcome.{status,sols}`,
 * `phase2_start_sol`, `log[]` ({sol, rule, ok, regolith_after}) and per-Sol
 * snapshots in `sols[]` ({sol, trust, ap.inflow, ap_unspent, regolith_sources,
 * buildings["<building_id>:<instance>"].level, researches[id].level}).
 * A snapshot for Sol N is taken after the bot acted in Sol N, before the tick.
 * BotSession::nextSol() increments the Sol first and then logs `sol_next`, so
 * the ok `sol_next` entry with sol N carries the start-of-Sol-N balances.
 *
 * Path building per opening: labor = Sciencelab (31), hangar = Hangar (44),
 * cantina = Bar (52); `auto` uses whichever of the three reached level 1 first
 * (if two reach Lv1 in the same Sol, the constant order labor > hangar >
 * cantina decides).
 * "Lv1 Sol" = first snapshot Sol in which that building has level >= 1.
 *
 * Path actions (successful log entries, rule names from BotStrategy):
 *  - labor:   research_knowledge
 *  - hangar:  request_ship, dispatch_mission, dispatch_salvage_mission,
 *             dispatch_regolith_mission, dispatch_credit_mission,
 *             dispatch_compounds_mission, focus_expedition_mission
 *  - cantina: accept_bar_offer, accept_bar_encounter, resolve_bar_concern,
 *             resolve_information_encounter
 *
 * KPI definitions (per run):
 *  - K1 (?int): first Sol >= Lv1 Sol with a successful path action of the
 *    opening, minus the Lv1 Sol. null if the building never reaches Lv1 or no
 *    path action follows.
 *  - K2 (?int): first snapshot Sol >= Lv1 Sol with a Phase-1-relevant yield
 *    from the opening's own path, minus the Lv1 Sol. Yield = labor: any
 *    knowledge at level >= 1 (each knowledge level grants Supply/Trust/
 *    production, e.g. geology +Rg); hangar: regolith_sources.mission > 0;
 *    cantina: regolith_sources.trade > 0, OR a Sol with a successful cantina
 *    path action in which the snapshot's trust rose versus the previous
 *    snapshot. Credits and AP are not counted (credits are not
 *    Phase-1-relevant per spec 1.7; AP gains are not attributable per source).
 *    null if never.
 *  - K3 (?int): phase2_start_sol; null if Phase 2 was never reached.
 *  - K4 (float): sum of ap_unspent over the snapshots of Sols 4-12 that exist.
 *    The spec's second part ("pure exploration AP after the map is fully
 *    revealed") is not derivable from the report (no map-end marker), so only
 *    the unspent AP are counted.
 *  - K5 (int): number of Sols 4-15 with ap_unspent >= 50 % of ap.inflow AND no
 *    successful build/research/path action in that Sol's log (build = place_*,
 *    invest_*, relocate_harvester, focus_engineering_levelup,
 *    focus_trust_building; path = any opening's path actions). ap.inflow is the
 *    denominator because the report's ap.total equals ap_unspent (kept for
 *    backward compatibility, see RunReport::snapshot()).
 *  - K6 (?int): regolith at the START of Sol 10 = regolith_after of the ok
 *    `sol_next` log entry with sol 10 (before any Sol-10 action; the Sol-10
 *    snapshot would already include Sol-10 builds). null if there is none
 *    (run ended earlier).
 *  - K7: won = outcome.status === 'completed', sols = outcome.sols.
 *
 * Comparison: only finished runs count (status completed|failed, or a run
 * stopped by --until-sol, marked `truncated_at_sol`: K1-K6 as usual, K7 = null
 * and reported as 'nicht gemessen', never as 0 or 'verfehlt'); a seed is
 * paired only if every compared opening has a finished run for it, otherwise
 * it is listed in skipped_seeds and ignored entirely (never counted as 0).
 * Per KPI and opening: median/min/max over non-null values (n, missing) and,
 * vs. the reference `labor`, the per-seed deltas (both values non-null).
 */
class OpeningComparison
{
    public const REFERENCE = 'labor';

    public const FINISHED_STATUSES = ['completed', 'failed'];

    private const PATH_BUILDINGS = ['labor' => 31, 'hangar' => 44, 'cantina' => 52];

    private const PATH_RULES = [
        'labor' => ['research_knowledge'],
        'hangar' => [
            'request_ship', 'dispatch_mission', 'dispatch_salvage_mission', 'dispatch_regolith_mission',
            'dispatch_credit_mission', 'dispatch_compounds_mission', 'focus_expedition_mission',
        ],
        'cantina' => ['accept_bar_offer', 'accept_bar_encounter', 'resolve_bar_concern', 'resolve_information_encounter'],
    ];

    /** Human-readable K2 yield definition per path (shown in the K2 check detail). */
    private const YIELD_LABELS = [
        'labor' => 'first knowledge level>=1',
        'hangar' => 'first mission Rg',
        'cantina' => 'trade Rg or trust rise',
    ];

    private const BUILD_RULES = [
        'place_building', 'place_agrardom', 'place_harvester_instance2', 'invest_production', 'invest_cc',
        'relocate_harvester', 'focus_engineering_levelup', 'focus_trust_building',
    ];

    /** Thresholds from spec 2.1. */
    public const K1_MAX = 1;

    public const K2_MAX = 3;

    public const K3_CORRIDOR = [15, 20];

    public const K3_MAX_SPREAD = 2;

    /** Spec: 15 % of the Sol 4-12 AP inflow, ~20 AP; could be derived from the measured inflow instead. */
    public const K4_MAX_DELTA = 20;

    public const K5_MAX = 2;

    public const K6_MAX_DELTA = 25;

    public const K7_MAX_WIN_RATE_PP = 10;

    public const K7_MAX_WIN_SOL_DELTA = 3;

    /** @var array<string, array<int, array>> opening => seed => report */
    private array $reports = [];

    /** @param list<string> $openings */
    private function __construct(private array $openings) {}

    /**
     * @param  list<array>  $reports  decoded report JSONs (a later report for the same opening+seed wins)
     * @param  list<string>  $openings  openings to compare; empty = all present (reference first)
     */
    public static function fromReports(array $reports, array $openings = []): self
    {
        $grouped = [];
        foreach ($reports as $report) {
            $grouped[$report['opening'] ?? 'auto'][(int) $report['seed']] = $report;
        }

        if ($openings === []) {
            $openings = array_keys($grouped);
            usort($openings, fn ($a, $b) => [$a !== self::REFERENCE] <=> [$b !== self::REFERENCE]);
        }

        $self = new self($openings);
        $self->reports = $grouped;

        return $self;
    }

    /**
     * @return array{k1: ?int, k2: ?int, k3: ?int, k4: float, k5: int, k6: ?int, k7: ?array{won: bool, sols: int}}
     */
    public static function metrics(array $report): array
    {
        $opening = $report['opening'] ?? 'auto';
        $sols = $report['sols'] ?? [];
        $log = array_values(array_filter($report['log'] ?? [], fn ($e) => ! empty($e['ok'])));

        [$path, $lv1Sol] = self::pathAndLevel1Sol($opening, $sols);

        $k1 = null;
        $k2 = null;
        if ($path !== null && $lv1Sol !== null) {
            foreach ($log as $entry) {
                if ($entry['sol'] >= $lv1Sol && in_array($entry['rule'], self::PATH_RULES[$path], true)) {
                    $k1 = $entry['sol'] - $lv1Sol;
                    break;
                }
            }
            $pathActionSols = [];
            foreach ($log as $entry) {
                if (in_array($entry['rule'], self::PATH_RULES[$path], true)) {
                    $pathActionSols[$entry['sol']] = true;
                }
            }
            $previousTrust = null;
            foreach ($sols as $snapshot) {
                $trust = $snapshot['trust'] ?? null;
                $trustRose = $trust !== null && $previousTrust !== null && $trust > $previousTrust;
                $previousTrust = $trust;
                if ($snapshot['sol'] < $lv1Sol) {
                    continue;
                }
                if (self::hasPathYield($path, $snapshot)
                    || ($path === 'cantina' && $trustRose && isset($pathActionSols[$snapshot['sol']]))) {
                    $k2 = $snapshot['sol'] - $lv1Sol;
                    break;
                }
            }
        }

        $activeRules = array_merge(self::BUILD_RULES, ...array_values(self::PATH_RULES));
        $activeSols = [];
        foreach ($log as $entry) {
            if (in_array($entry['rule'], $activeRules, true)) {
                $activeSols[$entry['sol']] = true;
            }
        }

        $k4 = 0.0;
        $k5 = 0;
        $k6 = null;
        foreach ($sols as $snapshot) {
            $sol = $snapshot['sol'];
            $unspent = (int) $snapshot['ap_unspent'];
            if ($sol >= 4 && $sol <= 12) {
                $k4 += $unspent;
            }
            $inflow = (int) ($snapshot['ap']['inflow'] ?? 0);
            if ($sol >= 4 && $sol <= 15 && $inflow > 0 && $unspent * 2 >= $inflow && ! isset($activeSols[$sol])) {
                $k5++;
            }
        }
        foreach ($log as $entry) {
            if ($entry['rule'] === 'sol_next' && $entry['sol'] === 10 && isset($entry['regolith_after'])) {
                $k6 = (int) $entry['regolith_after'];
                break;
            }
        }

        $p2 = $report['phase2_start_sol'] ?? null;

        return [
            'k1' => $k1,
            'k2' => $k2,
            'k3' => $p2 === null ? null : (int) $p2,
            'k4' => $k4,
            'k5' => $k5,
            'k6' => $k6,
            'k7' => isset($report['truncated_at_sol']) && ($report['outcome']['status'] ?? null) === 'active' ? null : [
                'won' => ($report['outcome']['status'] ?? null) === 'completed',
                'sols' => (int) ($report['outcome']['sols'] ?? 0),
            ],
        ];
    }

    /** Finished = ended regularly (completed|failed) or deliberately stopped at --until-sol. */
    private static function isFinished(array $report): bool
    {
        return in_array($report['outcome']['status'] ?? null, self::FINISHED_STATUSES, true)
            || isset($report['truncated_at_sol']);
    }

    public function compare(): array
    {
        $allSeeds = [];
        foreach ($this->openings as $opening) {
            $allSeeds += array_fill_keys(array_keys($this->reports[$opening] ?? []), true);
        }
        $allSeeds = array_keys($allSeeds);
        sort($allSeeds);

        $paired = [];
        $skipped = [];
        foreach ($allSeeds as $seed) {
            $problems = [];
            foreach ($this->openings as $opening) {
                $report = $this->reports[$opening][$seed] ?? null;
                if ($report === null) {
                    $problems[] = "{$opening}: missing";
                } elseif (! self::isFinished($report)) {
                    $problems[] = "{$opening}: unfinished";
                }
            }
            if ($problems === []) {
                $paired[] = $seed;
            } else {
                $skipped[$seed] = implode(', ', $problems);
            }
        }

        $metrics = [];
        foreach ($this->openings as $opening) {
            foreach ($paired as $seed) {
                $metrics[$opening][$seed] = self::metrics($this->reports[$opening][$seed]);
            }
        }

        $hasReference = in_array(self::REFERENCE, $this->openings, true);
        $kpis = [];
        foreach (['k1', 'k2', 'k3', 'k4', 'k5', 'k6'] as $k) {
            foreach ($this->openings as $opening) {
                $values = array_map(fn ($m) => $m[$k], $metrics[$opening] ?? []);
                $stats = self::stats(array_values(array_filter($values, fn ($v) => $v !== null)));
                $stats['missing'] = count(array_filter($values, fn ($v) => $v === null));

                $stats['delta'] = null;
                if ($hasReference && $opening !== self::REFERENCE) {
                    $deltas = [];
                    foreach ($paired as $seed) {
                        $own = $metrics[$opening][$seed][$k];
                        $ref = $metrics[self::REFERENCE][$seed][$k];
                        if ($own !== null && $ref !== null) {
                            $deltas[] = $own - $ref;
                        }
                    }
                    $stats['delta'] = self::stats($deltas);
                }
                $kpis[$k][$opening] = $stats;
            }
        }

        $k7 = [];
        foreach ($this->openings as $opening) {
            $all = $metrics[$opening] ?? [];
            // Truncated runs (--until-sol) carry k7 = null: not measured, neither won nor lost.
            $runs = array_filter($all, fn ($m) => $m['k7'] !== null);
            $winSols = array_values(array_map(fn ($m) => $m['k7']['sols'], array_filter($runs, fn ($m) => $m['k7']['won'])));
            $k7[$opening] = [
                'runs' => count($runs),
                'truncated' => count($all) - count($runs),
                'wins' => count($winSols),
                'win_rate' => $runs === [] ? null : count($winSols) / count($runs) * 100,
                'median_win_sol' => self::median($winSols),
            ];
        }

        return [
            'reference' => self::REFERENCE,
            'openings' => $this->openings,
            'paired_seeds' => $paired,
            'skipped_seeds' => $skipped,
            'metrics' => $metrics,
            'kpis' => $kpis,
            'k7' => $k7,
            'checks' => $this->checks($paired, $kpis, $k7, $hasReference),
        ];
    }

    /**
     * One status per criterion: 'ok', 'verfehlt', or 'n/a' (not evaluable, 'nicht gemessen' (K7 only: all runs truncated),
     * e.g. no paired seeds or no reference opening). Rules (spec 2.1):
     *  - K1 / K2 / K5: per opening, median <= 1 / <= 3 / <= 2 Sols; for K1/K2
     *    a run that never reached the event (missing > 0) fails — "never" is
     *    the worst value, not a gap to ignore.
     *  - K3: all medians present (no missing), spread of the per-opening
     *    medians <= 2 Sols and every median within the corridor 15-20.
     *  - K4 / K6: compared against the reference `labor` only: |median of the
     *    per-seed deltas| <= 20 AP / <= 25 Rg.
     *  - K7: against `labor` only: |win-rate difference| <= 10 pp and, if both
     *    sides have wins, |median win-Sol difference| <= 3.
     */
    private function checks(array $paired, array $kpis, array $k7, bool $hasReference): array
    {
        $na = fn (string $why) => ['status' => 'n/a', 'detail' => $why];
        if ($paired === []) {
            return array_fill_keys(['K1', 'K2', 'K3', 'K4', 'K5', 'K6', 'K7'], $na('no paired seeds'));
        }

        $perOpeningMax = function (string $k, int $max) use ($kpis): array {
            $ok = true;
            $parts = [];
            foreach ($kpis[$k] as $opening => $s) {
                $pass = $s['missing'] === 0 && $s['median'] !== null && $s['median'] <= $max;
                $ok = $ok && $pass;
                $parts[] = "{$opening} median ".self::fmt($s['median']).($s['missing'] ? " ({$s['missing']}x never)" : '');
            }

            $detail = implode('; ', $parts)." (target <= {$max})";
            if ($k === 'k2') {
                $detail .= ' — counted yields: '.implode('; ', array_map(
                    fn ($path) => "{$path}: ".self::YIELD_LABELS[$path],
                    array_keys(self::YIELD_LABELS),
                ));
            }

            return ['status' => $ok ? 'ok' : 'verfehlt', 'detail' => $detail];
        };

        $deltaMax = function (string $k, int $max) use ($kpis, $hasReference, $na): array {
            if (! $hasReference) {
                return $na('no reference opening');
            }
            $ok = true;
            $parts = [];
            foreach ($kpis[$k] as $opening => $s) {
                if ($s['delta'] === null) {
                    continue;
                }
                $median = $s['delta']['median'];
                $ok = $ok && $median !== null && abs($median) <= $max;
                $parts[] = "{$opening} delta median ".self::fmt($median);
            }

            return $parts === [] ? $na('only the reference opening')
                : ['status' => $ok ? 'ok' : 'verfehlt', 'detail' => implode('; ', $parts)." (target |delta| <= {$max})"];
        };

        $medians = array_map(fn ($s) => $s['median'], $kpis['k3']);
        $k3ok = ! in_array(null, $medians, true)
            && array_sum(array_map(fn ($s) => $s['missing'], $kpis['k3'])) === 0
            && max($medians) - min($medians) <= self::K3_MAX_SPREAD
            && min($medians) >= self::K3_CORRIDOR[0] && max($medians) <= self::K3_CORRIDOR[1];
        $k3parts = [];
        foreach ($kpis['k3'] as $opening => $s) {
            $k3parts[] = "{$opening} median ".self::fmt($s['median']).($s['missing'] ? " ({$s['missing']}x never)" : '');
        }

        $k7check = $na('no reference opening');
        if (array_sum(array_column($k7, 'runs')) === 0) {
            $k7check = ['status' => 'nicht gemessen', 'detail' => 'all runs truncated (--until-sol); K7 needs full runs'];
        } elseif ($hasReference) {
            $ref = $k7[self::REFERENCE];
            $ok = true;
            $parts = [];
            foreach ($k7 as $opening => $s) {
                if ($opening === self::REFERENCE) {
                    continue;
                }
                $ok = $ok && $s['win_rate'] !== null && $ref['win_rate'] !== null && abs($s['win_rate'] - $ref['win_rate']) <= self::K7_MAX_WIN_RATE_PP;
                if ($s['median_win_sol'] !== null && $ref['median_win_sol'] !== null) {
                    $ok = $ok && abs($s['median_win_sol'] - $ref['median_win_sol']) <= self::K7_MAX_WIN_SOL_DELTA;
                }
                $parts[] = "{$opening} ".self::fmt($s['win_rate']).' % / win Sol '.self::fmt($s['median_win_sol']);
            }
            $k7check = $parts === [] ? $na('only the reference opening') : [
                'status' => $ok ? 'ok' : 'verfehlt',
                'detail' => self::REFERENCE.' '.self::fmt($ref['win_rate']).' % / win Sol '.self::fmt($ref['median_win_sol']).'; '
                    .implode('; ', $parts).' (target <= '.self::K7_MAX_WIN_RATE_PP.' pp, win Sol <= '.self::K7_MAX_WIN_SOL_DELTA.')',
            ];
        }

        return [
            'K1' => $perOpeningMax('k1', self::K1_MAX),
            'K2' => $perOpeningMax('k2', self::K2_MAX),
            'K3' => [
                'status' => $k3ok ? 'ok' : 'verfehlt',
                'detail' => implode('; ', $k3parts).' (target spread <= '.self::K3_MAX_SPREAD.', all in '.implode('-', self::K3_CORRIDOR).')',
            ],
            'K4' => $deltaMax('k4', self::K4_MAX_DELTA),
            'K5' => $perOpeningMax('k5', self::K5_MAX),
            'K6' => $deltaMax('k6', self::K6_MAX_DELTA),
            'K7' => $k7check,
        ];
    }

    /**
     * @param  list<array>  $sols
     * @return array{0: ?string, 1: ?int} [path, Lv1 Sol]
     */
    private static function pathAndLevel1Sol(string $opening, array $sols): array
    {
        $candidates = isset(self::PATH_BUILDINGS[$opening]) ? [$opening => self::PATH_BUILDINGS[$opening]] : self::PATH_BUILDINGS;

        foreach ($sols as $snapshot) {
            foreach ($candidates as $path => $buildingId) {
                foreach ($snapshot['buildings'] ?? [] as $key => $building) {
                    if ((int) explode(':', (string) $key)[0] === $buildingId && $building['level'] >= 1) {
                        return [$path, (int) $snapshot['sol']];
                    }
                }
            }
        }

        return [isset(self::PATH_BUILDINGS[$opening]) ? $opening : null, null];
    }

    private static function hasPathYield(string $path, array $snapshot): bool
    {
        return match ($path) {
            'labor' => array_filter($snapshot['researches'] ?? [], fn ($r) => $r['level'] >= 1) !== [],
            'hangar' => ($snapshot['regolith_sources']['mission'] ?? 0) > 0,
            'cantina' => ($snapshot['regolith_sources']['trade'] ?? 0) > 0,
            default => false,
        };
    }

    /**
     * @param  list<int|float>  $values
     * @return array{median: int|float|null, min: int|float|null, max: int|float|null, n: int}
     */
    private static function stats(array $values): array
    {
        return [
            'median' => self::median($values),
            'min' => $values === [] ? null : min($values),
            'max' => $values === [] ? null : max($values),
            'n' => count($values),
        ];
    }

    /** @param list<int|float> $values */
    private static function median(array $values): int|float|null
    {
        if ($values === []) {
            return null;
        }
        sort($values);
        $mid = intdiv(count($values), 2);

        return count($values) % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    public static function fmt(int|float|null $value): string
    {
        if ($value === null) {
            return '-';
        }

        return is_float($value) && floor($value) != $value ? number_format($value, 1, '.', '') : (string) (int) round($value);
    }
}
