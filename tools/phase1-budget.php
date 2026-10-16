<?php

// Phase-1 budget table from game:playtest reports (no Laravel needed).
// Usage: php tools/phase1-budget.php [--since=YYYYMMDD_HHMMSS] [--dir=storage/logs/playtest]
//        [--engine=utility] [--min=445] [--json]
// --since compares against the UTC timestamp in the report file name.
// --min is the Regolith cost of the Phase-1 minimum. It is CALCULATED (gerechnet), not
// measured: Wohnhabitat 65 + Agrardom 95 + Uplink 105 + path building 120 + CC Lv2 60.
// Everything else (AP per bot rule, Rg available/out, Phase-1-end Sol) is MEASURED from the
// reports, counted up to and including `phase2_start_sol` (= first Sol of Phase 2).

$o = getopt('', ['since::', 'dir::', 'engine::', 'min::', 'json']);
$dir = $o['dir'] ?? __DIR__.'/../storage/logs/playtest';
$engine = $o['engine'] ?? 'utility';
$min = (float) ($o['min'] ?? 445);
$since = $o['since'] ?? null;

function stats(array $v): array
{
    if ($v === []) {
        return ['min' => null, 'median' => null, 'max' => null];
    }
    sort($v);

    return ['min' => $v[0], 'median' => $v[intdiv(count($v), 2)], 'max' => $v[count($v) - 1]];
}

function mean_map(array $maps): array
{
    $sum = [];
    foreach ($maps as $m) {
        foreach ($m as $k => $v) {
            $sum[$k] = ($sum[$k] ?? 0) + $v;
        }
    }
    arsort($sum);

    return array_map(fn ($v) => round($v / max(1, count($maps)), 1), $sum);
}

$by = [];
foreach (glob(rtrim($dir, '/').'/*.json') ?: [] as $file) {
    if (! preg_match('/-(\d{8}_\d{6})\.json$/', $file, $m) || ($since !== null && $m[1] < $since)) {
        continue;
    }
    $j = json_decode((string) file_get_contents($file), true);
    if (! is_array($j) || ($j['engine'] ?? null) !== $engine) {
        continue;
    }
    $op = $j['opening'];
    $by[$op]['fails'] = $by[$op]['fails'] ?? 0;
    $p2 = (int) ($j['phase2_start_sol'] ?? 0);
    if ($p2 === 0) {
        $by[$op]['fails']++;

        continue;
    }

    $ap = [];
    foreach ($j['log'] ?? [] as $e) {
        if (($e['sol'] ?? 99) <= $p2 && ($e['ok'] ?? false)) {
            $ap[$e['rule']] = ($ap[$e['rule']] ?? 0) + max(0, ($e['ap_before'] ?? 0) - ($e['ap_after'] ?? 0));
        }
    }

    $avail = (float) ($j['regolith_ledger']['start_stock'] ?? 0);
    $out = [];
    foreach ($j['regolith_ledger']['per_sol'] ?? [] as $p) {
        if ($p['sol'] <= $p2) {
            $avail += array_sum((array) $p['in']);
            foreach ((array) $p['out'] as $sink => $v) {
                $out[$sink] = ($out[$sink] ?? 0) + $v;
            }
        }
    }

    $by[$op]['p1_end'][] = $p2;
    $by[$op]['ap'][] = $ap;
    $by[$op]['avail'][] = $avail;
    $by[$op]['out'][] = $out;
}

$res = [];
foreach ($by as $op => $d) {
    $res[$op] = [
        'runs' => count($d['p1_end'] ?? []),
        'fails' => $d['fails'],
        'p1_end_sol' => stats($d['p1_end'] ?? []),
        'ap_by_rule' => mean_map($d['ap'] ?? []),
        'ap_total_mean' => round(array_sum(array_map('array_sum', $d['ap'] ?? [])) / max(1, count($d['ap'] ?? [])), 1),
        'rg_available' => stats($d['avail'] ?? []),
        'rg_slack' => stats(array_map(fn ($a) => $a - $min, $d['avail'] ?? [])),
        'rg_out_by_sink' => mean_map($d['out'] ?? []),
    ];
}

if (isset($o['json'])) {
    echo json_encode($res);

    return;
}

echo "Phase-1-Budget (engine=$engine, since=".($since ?? '-').", Minimum $min Rg = gerechnet)\n";
foreach ($res as $op => $r) {
    $e = $r['p1_end_sol'];
    $s = $r['rg_slack'];
    printf("\n== %s: %d Laeufe, %d Frist-Fails | Phase-1-Ende Sol %s / %s / %s (min/med/max)\n", $op, $r['runs'], $r['fails'], $e['min'], $e['median'], $e['max']);
    printf("   Rg-Puffer (verfuegbar - Minimum): %s / %s / %s\n", $s['min'], $s['median'], $s['max']);
    echo '   Rg-Senken (Mittel): '.json_encode($r['rg_out_by_sink'])."\n";
    echo "   AP je Regel (Mittel, Summe {$r['ap_total_mean']}): ".json_encode($r['ap_by_rule'])."\n";
}
