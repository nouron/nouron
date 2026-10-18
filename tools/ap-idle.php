<?php

// Unspent action points (idle AP) and milestone Sols per opening from game:playtest reports (no Laravel needed).
// Usage: php tools/ap-idle.php --dir=<report dir> [--profile=default] [--engine=utility]
//        [--from=12] [--to=45] [--threshold=5] [--json]
// Idle AP of a Sol = sols[].ap.unspent, counted only when >= --threshold (small leftovers are normal).
// idle_share = idle AP / sols[].ap.inflow over the Sols [from, to]. Milestones (first Sol with a
// Sciencelab Lv1+, CC Lv3/4/5) look at the whole run; `never` counts runs that did not reach them.
// Everything is MEASURED from the reports.

$o = getopt('', ['dir:', 'profile::', 'engine::', 'from::', 'to::', 'threshold::', 'json']);
$dir = $o['dir'] ?? __DIR__.'/../storage/logs/playtest';
$engine = $o['engine'] ?? 'utility';
$profile = $o['profile'] ?? null;
$from = (int) ($o['from'] ?? 12);
$to = (int) ($o['to'] ?? 45);
$threshold = (int) ($o['threshold'] ?? 5);

function median(array $v): ?float
{
    if ($v === []) {
        return null;
    }
    sort($v);
    $n = count($v);

    return $n % 2 === 1 ? (float) $v[intdiv($n, 2)] : ($v[$n / 2 - 1] + $v[$n / 2]) / 2;
}

function first_sol(array $sols, callable $reached): ?int
{
    foreach ($sols as $s) {
        if ($reached($s)) {
            return (int) $s['sol'];
        }
    }

    return null;
}

$by = [];
foreach (glob(rtrim($dir, '/').'/*.json') ?: [] as $file) {
    $j = json_decode((string) file_get_contents($file), true);
    if (! is_array($j) || ($j['engine'] ?? null) !== $engine || ($profile !== null && ($j['profile'] ?? null) !== $profile)) {
        continue;
    }
    $sols = $j['sols'] ?? [];
    $idle = 0;
    $idleSols = 0;
    $inflow = 0;
    foreach ($sols as $s) {
        if ($s['sol'] < $from || $s['sol'] > $to) {
            continue;
        }
        $inflow += (int) ($s['ap']['inflow'] ?? 0);
        $un = (int) ($s['ap']['unspent'] ?? 0);
        if ($un >= $threshold) {
            $idle += $un;
            $idleSols++;
        }
    }
    $by[$j['opening']][] = [
        'idle' => $idle, 'idle_sols' => $idleSols, 'inflow' => $inflow,
        'sciencelab' => first_sol($sols, function (array $s): bool {
            foreach ($s['buildings'] ?? [] as $key => $b) {
                if (str_starts_with((string) $key, '31:') && (int) ($b['level'] ?? 0) >= 1) {
                    return true;
                }
            }

            return false;
        }),
        'cc3' => first_sol($sols, fn (array $s): bool => (int) ($s['cc_level'] ?? 0) >= 3),
        'cc4' => first_sol($sols, fn (array $s): bool => (int) ($s['cc_level'] ?? 0) >= 4),
        'cc5' => first_sol($sols, fn (array $s): bool => (int) ($s['cc_level'] ?? 0) >= 5),
    ];
}

$milestone = fn (array $runs, string $k): array => [
    'median' => median(array_values(array_filter(array_column($runs, $k), fn ($v) => $v !== null))),
    'never' => count(array_filter(array_column($runs, $k), fn ($v) => $v === null)),
];

ksort($by);
$res = [];
foreach ($by as $opening => $runs) {
    $res[$opening] = [
        'runs' => count($runs),
        'idle_share' => array_sum(array_column($runs, 'inflow')) > 0 ? array_sum(array_column($runs, 'idle')) / array_sum(array_column($runs, 'inflow')) : 0.0,
        'idle_ap_median' => median(array_column($runs, 'idle')),
        'idle_sols_median' => median(array_column($runs, 'idle_sols')),
        'sciencelab_first_sol' => $milestone($runs, 'sciencelab'),
        'cc3_first_sol' => $milestone($runs, 'cc3'),
        'cc4_first_sol' => $milestone($runs, 'cc4'),
        'cc5_first_sol' => $milestone($runs, 'cc5'),
    ];
}

if (isset($o['json'])) {
    echo json_encode($res, JSON_PRETTY_PRINT), "\n";

    return;
}
printf("Idle AP, Sol %d-%d, threshold %d (engine %s%s)\n", $from, $to, $threshold, $engine, $profile !== null ? ", profile $profile" : '');
printf("%-8s %4s %8s %9s %9s  %-12s %-12s %-12s %-12s\n", 'opening', 'runs', 'idle %', 'idleAP/r', 'idleSol/r', 'Lab Lv1', 'CC3', 'CC4', 'CC5');
$fmt = fn (array $m): string => ($m['median'] === null ? '-' : rtrim(rtrim(number_format($m['median'], 1), '0'), '.')).($m['never'] > 0 ? " (n/a {$m['never']})" : '');
foreach ($res as $op => $r) {
    printf(
        "%-8s %4d %7.1f%% %9s %9s  %-12s %-12s %-12s %-12s\n",
        $op, $r['runs'], $r['idle_share'] * 100, $r['idle_ap_median'], $r['idle_sols_median'],
        $fmt($r['sciencelab_first_sol']), $fmt($r['cc3_first_sol']), $fmt($r['cc4_first_sol']), $fmt($r['cc5_first_sol'])
    );
}
