<?php

use Illuminate\Contracts\Console\Kernel;

// Nouron Regolith economy model (expected values, deterministic, Sol 1..N).
// Usage: php tools/economy-model.php [--opening=labor|hangar|cantina|all] [--sols=60]
//        [--yield=26] [--start=300] [--geo=3,3,2,2,2] [--eff=1.0] [--new=0|1]
//        [--set=key=val,key=val] [--json]
// Costs/yields come from config(); everything else is a labelled ASSUMPTION in $A
// (docs/superpowers/specs/2026-10-14-regolith-wirtschaft-budget-modell.md, E.3).
// Greedy planner: one ordered buy list per opening, AP pool per Sol, Rg paid when an
// item is started. A Sol where the next item is blocked by Rg counts as "Rg-bound".

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$o = getopt('', ['opening::', 'sols::', 'yield::', 'start::', 'geo::', 'eff::', 'new::', 'set::', 'json']);
$sols = (int) ($o['sols'] ?? 60);
$yield = (float) ($o['yield'] ?? config('game.harvester.fresh_yield.regolith_y2_d2'));
$tileMax = (float) config('game.harvester.resource_max.regolith_y2_d2');
$start = (float) ($o['start'] ?? config('game.onboarding.start_regolith'));
$geo = array_map('floatval', explode(',', $o['geo'] ?? implode(',', config('game.geology_harvester_bonus_per_level'))));
$eff = (float) ($o['eff'] ?? 1.0);   // share of AP actually spent (bot ~0.6, reference 1.0)
$new = (int) ($o['new'] ?? 0);       // 1 = planned sources (Schürfflug, Erzgast, Kenntnis-Rg)

// ASSUMPTIONS (override via --set=key=val). Rg/Sol are expected values incl. utilisation.
$A = [
    'build_ap' => 10, 'place_ap' => 11, 'explore_ap_p1' => 6, 'research_share_labor' => 0.33,
    'research_share_p2' => 0.40, 'repair_ap_share_p2' => 0.16,
    'pool_rg' => 40, 'finds_rg_per_sol' => 0.0,         // current game: pool only
    'cantina_guest_rg_per_sol' => 0.8,                   // measured ~0.8 (cantina_guest 68/40 Sols)
    'drone_flight_rg_per_sol' => 2.8, 'freighter_flight_rg_per_sol' => 5.6,   // new: Schürfflug
    'cantina_ore_rg_per_sol' => 3.5, 'cartography_per_lv' => 0.52, 'trade_per_lv' => 0.72,  // new
    'repair_rg_factor' => 0.35, 'relocate_ap' => 6, 'map_budget' => 2300, 'trust_mult_p2' => 1.04, 'harvester_downtime' => 0.04,
];
foreach (explode(',', $o['set'] ?? '') as $kv) {
    if (str_contains($kv, '=')) {
        [$k, $v] = explode('=', $kv);
        $A[$k] = (float) $v;
    }
}

$bcfg = config('buildings');
$flat = (float) config('game.build.levelup_regolith_flat');
$ccPer = (float) $bcfg['commandCenter']['cc_upgrade_regolith_per_level'];
$decay = fn ($b) => $bcfg[$b]['decay_rate'] ?? 0;
$place = fn ($b) => ($bcfg[$b]['build_cost'][3] ?? 0) + $flat;

// Buy list: [key, label, rg, ap, effect]. effect: [type, value]
$P = fn ($b, $l = null) => ['key' => "$b+", 'b' => $b, 'rg' => $place($b), 'ap' => $A['place_ap'], 'lbl' => ($l ?? $b).' neu'];
$L = fn ($b, $lv) => ['key' => "$b$lv", 'b' => $b, 'rg' => $flat, 'ap' => $A['build_ap'], 'lbl' => "$b Lv$lv"];
$CC = fn ($lv) => ['key' => "cc$lv", 'b' => 'commandCenter', 'rg' => $ccPer * $lv, 'ap' => $A['build_ap'], 'lbl' => "CC Lv$lv"];
$H = fn ($n) => ['key' => "housing$n", 'b' => 'housingComplex', 'rg' => $flat, 'ap' => $A['build_ap'], 'lbl' => "Wohnhabitat Lv$n"];
$FREIGHTER = ['key' => 'freighter', 'b' => null, 'rg' => 0, 'ap' => 0, 'lbl' => 'Frachter (nur Cr)'];

$plans = [
    'labor' => [$P('bioFacility'), $CC(2), $P('sciencelab'), $L('sciencelab', 2), $H(2), $CC(3), $P('hangar'), $L('hangar', 2),
        $L('bioFacility', 2), $L('sciencelab', 3), $P('uplinkStation'), $CC(4), $P('bar'), $L('housingComplex', 3), $L('sciencelab', 4), $L('bar', 2),
        $P('infirmary'), $L('bioFacility', 3), $CC(5), $L('sciencelab', 5), $L('hangar', 3), $L('bar', 3)],
    'hangar' => [$P('bioFacility'), $CC(2), $P('hangar'), $L('hangar', 2), $H(2), $CC(3), $P('sciencelab'), $L('sciencelab', 2),
        $L('bioFacility', 2), $L('sciencelab', 3), $P('uplinkStation'), $CC(4), $P('bar'), $L('housingComplex', 3), $L('sciencelab', 4), $L('bar', 2),
        $P('infirmary'), $L('bioFacility', 3), $CC(5), $L('sciencelab', 5), $L('bar', 3), $L('hangar', 3)],
    'cantina' => [$P('bioFacility'), $CC(2), $P('bar'), $L('bar', 2), $H(2), $CC(3), $P('sciencelab'), $L('sciencelab', 2),
        $L('bioFacility', 2), $L('sciencelab', 3), $P('uplinkStation'), $CC(4), $P('hangar'), $L('housingComplex', 3), $L('sciencelab', 4), $L('hangar', 2),
        $P('infirmary'), $L('bioFacility', 3), $CC(5), $L('sciencelab', 5), $L('hangar', 3), $L('bar', 3)],
];
// Reuse of the same 'housingComplex' label on Lv3: fix key clash by index below.

function advisorAp(int $sol, array $startSols): float
{
    // base + per advisor: rank 1 at hire, rank 2 after 15 active Sols, rank 3 after 45 (config).
    $ap = config('game.ap.base');
    $per = config('game.advisor.ap_per_rank');
    $th = config('game.advisor.rank_thresholds');
    foreach ($startSols as $s) {
        if ($sol < $s) {
            continue;
        }
        $age = $sol - $s;
        $ap += $per[$age >= $th[2] ? 3 : ($age >= $th[1] ? 2 : 1)];
    }

    return $ap;
}

function run(string $opening, array $plan, array $A, array $ctx): array
{
    ['sols' => $sols, 'yield' => $yield, 'tileMax' => $tileMax, 'start' => $start, 'geo' => $geo, 'eff' => $eff, 'new' => $new] = $ctx;
    $bcfg = config('buildings');
    $rg = $start;
    $tile = $tileMax;
    $budget = $A['map_budget'] - $tileMax;
    $skip = false;
    $level = ['commandCenter' => 1, 'harvester' => 1, 'housingComplex' => 1];
    $geoLv = 0;
    $geoAp = 0;
    $geoCost = [20, 28, 36, 44, 52];
    $done = [];
    $idx = 0;
    $progress = 0.0;
    $paid = false;
    $advisors = [1];   // Baumeister Sol 1
    $rows = [];
    $tot = ['in' => [], 'out' => [], 'rgBound' => 0, 'unusedAp' => 0, 'apTotal' => 0];
    $repairDebt = 0.0;
    $poolGiven = false;
    $milestone = [];
    foreach (range(1, $sols) as $sol) {
        $inc = [];
        // --- advisors: path advisor hired with the first path building
        $pathBuilt = array_intersect(['sciencelab', 'hangar', 'bar'], array_keys($level));
        $advisors = array_slice([1, ...array_fill(0, 5, 0)], 0, 1);
        $n = 0;
        foreach ($pathBuilt as $b) {
            $advisors[] = $level[$b.'_since'] ?? $sol;
        }
        $ap = advisorAp($sol, $advisors) * ($sol >= 26 ? 1.05 : 1.0);
        $apUse = $ap * $eff;
        // --- fixed AP drains
        $explore = $sol <= 12 ? $A['explore_ap_p1'] : 0;
        $p2 = $sol > 15;
        $decaySum = 0;
        foreach ($level as $b => $lv) {
            if (isset($bcfg[$b]) && ! in_array($b, ['commandCenter', 'harvester'])) {
                $decaySum += ($bcfg[$b]['decay_rate'] ?? 0) * (is_int($lv) ? 1 : 0);
            }
        }
        $repairAp = $p2 ? $A['repair_ap_share_p2'] * $apUse : ($sol > 6 ? 1.5 : 0);
        $repairRg = min($repairAp, 1.0 * $decaySum * 1.0);          // 1 Rg per SP repaired
        $repairRg = $p2 ? $A['repair_rg_factor'] * min($repairAp, $decaySum + 1.2) : 0;   // bot repairs late/partial (ledger: ~0.6 Rg/Sol)
        $labor = isset($level['sciencelab']);
        $researchShare = $labor ? ($p2 ? $A['research_share_p2'] : ($opening === 'labor' ? $A['research_share_labor'] : 0.02)) : 0;
        $researchAp = $researchShare * $apUse;
        // --- research: geology first (needs lab Lv2)
        if (($level['sciencelab'] ?? 0) >= 2 && $geoLv < 5) {
            $geoAp += $researchAp;
            while ($geoLv < 5 && $geoAp >= $geoCost[$geoLv]) {
                $geoAp -= $geoCost[$geoLv];
                $geoLv++;
                $milestone["geo$geoLv"] = $sol;
            }
        }
        $reloc = 0;
        if ($tile <= 0.5 && $budget > 0) {   // tile empty: move the harvester (AP, 1 Sol without yield)
            $tile = min($tileMax, $budget);
            $budget -= $tile;
            $reloc = $A['relocate_ap'];
            $skip = true;
            $milestone['reloc'.($milestone['relocs'] = ($milestone['relocs'] ?? 0) + 1)] = $sol;
        }
        $buildAp = max(0, $apUse - $explore - $repairAp - $researchAp - $reloc);
        $tot['apTotal'] += $ap;
        $tot['out']['repair'] = ($tot['out']['repair'] ?? 0) + $repairRg;
        $rg -= $repairRg;
        // --- spend build AP on the plan (greedy, strict order)
        $blocked = false;
        while ($buildAp > 0 && $idx < count($plan)) {
            $it = $plan[$idx];
            if (! $paid) {
                if ($rg < $it['rg']) {
                    $blocked = true;
                    break;
                }
                $rg -= $it['rg'];
                $sink = str_starts_with($it['key'], 'cc') ? 'cc_upgrade' : (str_ends_with($it['key'], '+') ? 'build_place' : 'build_levelup');
                $tot['out'][$sink] = ($tot['out'][$sink] ?? 0) + $it['rg'];
                $paid = true;
            }
            $take = min($buildAp, $it['ap'] - $progress);
            $progress += $take;
            $buildAp -= $take;
            if ($progress >= $it['ap'] - 1e-9) {
                $b = $it['b'];
                if ($b) {
                    $level[$b] = (is_int($level[$b] ?? null) ? $level[$b] : 0) + 1;
                    if (in_array($b, ['sciencelab', 'hangar', 'bar']) && str_ends_with($it['key'], '+')) {
                        $level[$b.'_since'] = $sol + 1;
                    }
                }
                $milestone[$it['key']] = $sol;
                $idx++;
                $progress = 0;
                $paid = false;
            }
        }
        if ($blocked) {
            $tot['rgBound']++;
        }
        $tot['unusedAp'] += $buildAp + ($ap - $apUse);
        // --- income
        $trust = $p2 ? $A['trust_mult_p2'] : 1.0;
        $down = $p2 ? 1 - $A['harvester_downtime'] : 1.0;
        $gb = $geoLv ? array_sum(array_slice($geo, 0, $geoLv)) : 0;
        $h = $sol >= 2 && ! $skip ? min($tile, ($yield + $gb) * $trust * $down) : 0;   // moved Sol 1, yield from Sol 2
        $skip = false;
        $tile -= $h;
        $inc['harvester_base'] = $h > 0 ? min($h, $yield * $trust * $down) : 0;
        $inc['geology'] = $h - $inc['harvester_base'];
        $hangarLv = $level['hangar'] ?? 0;
        $barLv = $level['bar'] ?? 0;
        if (! $poolGiven && $sol >= 4) {
            $inc['finds'] = $A['pool_rg'];
            $poolGiven = true;
        }
        if ($barLv >= 1 && $level['bar_since'] <= $sol) {
            $inc['cantina_guest'] = $A['cantina_guest_rg_per_sol'];
            if ($new) {
                $inc['cantina_ore'] = $A['cantina_ore_rg_per_sol'] * (0.5 + 0.25 * $barLv);
            }
        }
        if ($new && $hangarLv >= 2) {
            $inc['schuerfflug'] = $A['drone_flight_rg_per_sol'] + (($milestone['freighter'] ?? null) ? $A['freighter_flight_rg_per_sol'] : 0);
            if ($sol > 15 && ! isset($milestone['freighter'])) {
                $milestone['freighter'] = $sol;
            }
        }
        foreach ($inc as $k => $v) {
            $rg += $v;
            $tot['in'][$k] = ($tot['in'][$k] ?? 0) + $v;
        }
        $rows[] = ['sol' => $sol, 'ap' => round($ap, 1), 'in' => round(array_sum($inc), 1), 'rg' => round($rg), 'tile' => round($tile), 'plan' => $plan[min($idx, count($plan) - 1)]['lbl'], 'bound' => $blocked];
    }

    return compact('rows', 'tot', 'milestone', 'rg');
}

$names = ['labor', 'hangar', 'cantina'];
$sel = $o['opening'] ?? 'all';
$res = [];
foreach ($names as $n) {
    if ($sel === 'all' || $sel === $n) {
        // de-duplicate repeated keys (housing Lv3 appears once; keys are unique per level otherwise)
        $res[$n] = run($n, $plans[$n], $A, compact('sols', 'yield', 'tileMax', 'start', 'geo', 'eff', 'new'));
    }
}

if (isset($o['json'])) {
    echo json_encode($res, JSON_PRETTY_PRINT), "\n";
    exit;
}
printf("Modell: yield=%s start=%s geo=%s eff=%s new=%d sols=%d\n", $yield, $start, implode('/', $geo), $eff, $new, $sols);
foreach ($res as $n => $r) {
    echo "\n== $n ==\n";
    echo "Sol  AP  Zufl.  Rg  Kachel  naechster Kauf\n";
    foreach ($r['rows'] as $row) {
        if ($row['sol'] % 5 == 0 || $row['sol'] <= 3) {
            printf("%3d %4s %5s %4s %5s  %s%s\n", $row['sol'], $row['ap'], $row['in'], $row['rg'], $row['tile'], $row['plan'], $row['bound'] ? '  [Rg-bound]' : '');
        }
    }
    $in = array_sum($r['tot']['in']);
    $out = array_sum($r['tot']['out']);
    echo 'Zufluss: ';
    foreach ($r['tot']['in'] as $k => $v) {
        printf('%s=%d (%d%%) ', $k, $v, $in ? 100 * $v / $in : 0);
    }
    echo "\nAbfluss: ";
    foreach ($r['tot']['out'] as $k => $v) {
        printf('%s=%d ', $k, $v);
    }
    printf("\nSumme in=%d out=%d Ausschoepfung=%d%% Rg-bound-Sole=%d ungenutzte AP=%d (%d%%) Endbestand=%d\n", $in, $out, $in ? 100 * $out / $in : 0,
        $r['tot']['rgBound'], $r['tot']['unusedAp'], 100 * $r['tot']['unusedAp'] / max(1, $r['tot']['apTotal']), $r['rg']);
    $m = $r['milestone'];
    printf("Meilensteine: Phase-1-Ende(2.Pfad Lv1)~Sol %s, CC3 Sol %s, CC4 Sol %s, CC5 Sol %s, Geologie Lv1..5 Sol %s\n",
        $m[$n === 'labor' ? 'hangar+' : ($n === 'hangar' ? 'sciencelab+' : 'sciencelab+')] ?? '-', $m['cc3'] ?? '-', $m['cc4'] ?? '-', $m['cc5'] ?? '-',
        implode('/', array_map(fn ($i) => $m["geo$i"] ?? '-', range(1, 5))));
}
