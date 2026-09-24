<?php

namespace App\Console\Commands;

use App\Services\OvercapService;
use App\Services\ResourcesService;
use App\Services\TrustService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Simulates one game tick and prints a before/after diff without writing to the DB.
 *
 * Usage:
 *   php artisan game:tick-dry-run            — all colonies
 *   php artisan game:tick-dry-run --colony=1 — single colony
 */
class GameTickDryRun extends Command
{
    protected $signature = 'game:tick-dry-run {--colony= : Limit output to a single colony ID}';

    protected $description = 'Simulate one game tick and show a resource/decay diff (no DB writes)';

    public function __construct(
        private readonly TrustService $trustService,
        private readonly OvercapService $overcapService,
        private readonly ResourcesService $resourcesService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $filterColony = $this->option('colony') ? (int) $this->option('colony') : null;

        $tick = (int) DB::table('user_resources')->max('supply');  // rough proxy; not used in output
        $colonies = $this->loadColonies($filterColony);

        if ($colonies->isEmpty()) {
            $this->warn('No colonies found.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('<fg=cyan>═══ Tick Dry-Run (simulation — no DB writes) ═══</>');

        foreach ($colonies as $colony) {
            $this->renderColony($colony);
        }

        return self::SUCCESS;
    }

    private function buildingLabel(string $name): string
    {
        $short = preg_replace('/^building_/', '', $name);
        $spaced = (string) preg_replace('/(?<=[a-z])([A-Z])/', ' $1', $short);

        return ucfirst($spaced);
    }

    private function loadColonies(?int $filterColony)
    {
        $q = DB::table('glx_colonies as c')
            ->leftJoin('user as u', 'u.user_id', '=', 'c.user_id')
            ->leftJoin('user_resources as ur', 'ur.user_id', '=', 'c.user_id')
            ->select('c.id as colony_id', 'c.name as colony_name', 'c.user_id',
                'u.username', 'ur.credits', 'ur.supply');

        if ($filterColony !== null) {
            $q->where('c.id', $filterColony);
        }

        return $q->orderBy('c.name')->get();
    }

    private function renderColony(object $colony): void
    {
        $cid = $colony->colony_id;

        $this->line('');
        $this->line(sprintf(
            '<fg=yellow>Colony: %s (ID:%d)</> | User: %s (ID:%d)',
            $colony->colony_name,
            $cid,
            $colony->username ?? 'NPC',
            $colony->user_id ?? 0
        ));
        $this->line(str_repeat('─', 60));

        // ── Buildings (levels + decay rates) ─────────────────────────────
        $buildings = DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $cid)
            ->where('cb.level', '>', 0)
            ->select('cb.building_id', 'b.name', 'cb.level', 'cb.status_points',
                'b.decay_rate', 'b.max_status_points', 'b.supply_cost')
            ->get()
            ->keyBy('building_id');

        $buildingLevel = static fn (int $buildingId): int => $buildings->has($buildingId) ? (int) $buildings->get($buildingId)->level : 0;

        $ccLevel = $buildingLevel(25);
        $housingLevel = $buildingLevel(28);
        $uplinkLevel = $buildingLevel(54);

        // ── Supply cap ────────────────────────────────────────────────────
        $capCC = (int) config('buildings.commandCenter.supply_cap', 10);
        $capHousing = (int) config('buildings.housingComplex.supply_cap', 8);
        $capMax = (int) config('game.supply.cap_max', 200);
        $newCap = $ccLevel > 0
            ? min($capCC + ($housingLevel * $capHousing), $capMax)
            : 0;
        $oldCap = (int) ($colony->supply ?? 0);
        $capChange = $newCap - $oldCap;
        $capStr = $capChange === 0
            ? "<fg=white>{$newCap}</>"
            : sprintf('<fg=%s>%d → %d (%+d)</>', $capChange > 0 ? 'green' : 'red', $oldCap, $newCap, $capChange);
        $this->line("  Supply cap:  {$capStr}");

        // ── Credits ───────────────────────────────────────────────────────
        $credits = (int) ($colony->credits ?? 0);
        $nexus = $ccLevel > 0 ? (int) config('game.credits.nexus_subsidy', 30) : 0;
        $relayBonus = $uplinkLevel * (int) config('game.credits.relay_bonus_per_uplink_level', 20);

        $advisors = DB::table('advisors')
            ->where('colony_id', $cid)
            ->whereNotNull('colony_id')
            ->select('rank', 'personell_id')
            ->get();
        $upkeepMap = config('game.advisor.upkeep', [1 => 10, 2 => 30, 3 => 80]);
        $upkeep = $advisors->sum(fn ($a) => $upkeepMap[$a->rank] ?? 10);

        $creditsDelta = $nexus + $relayBonus - $upkeep;
        $creditsNew = $credits + $creditsDelta;

        $incomeStr = "+{$nexus} nexus";
        if ($relayBonus > 0) {
            $incomeStr .= " +{$relayBonus} relay";
        }
        if ($upkeep > 0) {
            $incomeStr .= " -{$upkeep} upkeep";
        }
        $color = $creditsDelta >= 0 ? 'green' : 'red';
        $this->line(sprintf(
            '  Credits:     %d → <fg=%s>%d</> (%+d: %s)',
            $credits, $color, $creditsNew, $creditsDelta, $incomeStr
        ));

        // ── Resources ─────────────────────────────────────────────────────
        $colRes = DB::table('colony_resources')
            ->where('colony_id', $cid)
            ->whereIn('resource_id', [3, 4, 5])
            ->pluck('amount', 'resource_id');

        $production = config('game.production_curve', []);
        $trust = $this->trustService->getTrust($cid);
        $trustMultiplier = $this->trustService->getProductionMultiplier($trust);
        // Staffing share (GDD §5/§6, A14): unfilled workplaces after an over-capacity
        // departure scale raw-material production, like GameTick::generateResources().
        $staffing = $this->resourcesService->staffingShare((int) $cid);
        $multiplier = $trustMultiplier * $staffing;

        $resNames = [3 => 'Regolith', 4 => 'Werkstoffe', 5 => 'Organika'];
        $this->line('  Resources:');
        foreach ($resNames as $rid => $rname) {
            $cur = (int) ($colRes[$rid] ?? 0);
            $yield = 0;
            foreach ($production as $buildingId => $outputs) {
                if (isset($outputs[$rid])) {
                    $bLevel = $buildingLevel($buildingId);
                    $base = GameTick::cumulativeCurveYield($outputs[$rid], $bLevel);
                    $yield += (int) round($base * $multiplier);
                }
            }
            $new = $cur + $yield;
            $yieldStr = $yield > 0
                ? sprintf('<fg=green>+%d</>', $yield)
                : ($yield < 0 ? sprintf('<fg=red>%d</>', $yield) : '<fg=gray>±0</>');
            $this->line(sprintf('    %-14s %6d → %6d  (%s)', $rname.':', $cur, $new, $yieldStr));
        }

        $trustColor = $trust >= 0 ? 'green' : 'red';
        $this->line(sprintf(
            '    Trust:        <fg=%s>%d</> (production ×%.2f)',
            $trustColor, $trust, $trustMultiplier
        ));

        // ── Building decay ──────────────────────────────────────────────
        // Over-capacity no longer accelerates decay (GDD §7, A14).
        $this->line('  Building decay:');
        foreach ($buildings->sortBy('name') as $b) {
            $rate = (float) $b->decay_rate;
            $maxSP = (int) $b->max_status_points;
            $curSP = (float) $b->status_points;
            $newSP = $curSP - $rate;
            $pct = $maxSP > 0 ? ($newSP / $maxSP) * 100 : 100;

            if ($newSP <= 0) {
                $color = 'red';
                $flag = ' ⚠ LEVEL DOWN';
            } elseif ($pct < 20) {
                $color = 'red';
                $flag = ' ⚠ critical';
            } elseif ($pct < 40) {
                $color = 'yellow';
                $flag = ' ! attention';
            } else {
                $color = 'gray';
                $flag = '';
            }

            $this->line(sprintf(
                '    %-22s Lv%d  %5.1f → <fg=%s>%5.1f</> SP/%d  (%d%%)%s',
                $this->buildingLabel($b->name).':', $b->level, $curSP, $color, max(0, $newSP), $maxSP, max(0, (int) $pct), $flag
            ));
        }

        $this->renderOvercapPreview((int) $cid);
    }

    /**
     * A14 (GDD §6 "Überkapazität"): homeless colonists — next streak, its trust
     * penalty and the Sols until departure (or the departure itself) — and
     * understaffing after a departure.
     */
    private function renderOvercapPreview(int $colonyId): void
    {
        $status = $this->overcapService->status($colonyId);
        $deadline = max(1, (int) config('game.overcap.departure_after_sols', 3));

        if ($status['over'] && $status['streak'] >= $deadline) {
            $this->line(sprintf('  <fg=red>Departure next Sol: %d colonists leave</>', $status['homeless']));
        } elseif ($status['over']) {
            $this->line(sprintf(
                '  <fg=red>Over-capacity: %d homeless, streak %d → %d, trust penalty next Sol: %d, departure in %d Sol</>',
                $status['homeless'],
                $status['streak'],
                $status['streak'] + 1,
                $this->trustService->overcapPenaltyForStreak($status['streak'] + 1),
                $status['sols_until_departure']
            ));
        }

        if ($status['departed'] > 0) {
            $this->line(sprintf(
                '  <fg=yellow>Understaffed: %d workplaces unfilled, raw-material production at %d%%</>',
                $status['departed'],
                $status['staffing_pct']
            ));
        }
    }
}
