<?php

namespace App\Services;

use App\Models\Run;
use Illuminate\Support\Facades\DB;

/**
 * SolReportService — assembles the end-of-Sol report shown on the transition
 * screen after the player ends a Sol.
 *
 * Flow:
 *   1. Controller calls snapshot() BEFORE advancing the tick (captures the
 *      "before" state of the colony: resources, credits, supply, trust,
 *      building levels/status, advisor ranks).
 *   2. The tick runs (game:tick), mutating the DB.
 *   3. Controller calls buildReport() with the run and the "before" snapshot.
 *      The report diffs the live "after" state against the snapshot and reads
 *      the colony_log events written at the processed tick to produce a list
 *      of player-facing groups.
 *
 * Player-facing groups (design: game-designer, 2026-06-16):
 *   1. decay       — "Die Kolonie altert"   (level-downs + general wear)
 *   2. events      — "Ereignisse"            (merchant, encounters, deliveries)
 *   3. production  — "Produktion & Vorräte"  (resource yield + supply cap)
 *   4. colony      — "Kolonie & Personal"    (trust, credits, advisor promotions)
 *   5. run         — "Der Run"               (sol counter, phase, objectives)
 *
 * Empty *vorkommnis* groups (decay/events) are omitted entirely; *zustand*
 * groups (production/colony/run) are always shown so the report stays honest.
 *
 * Line tones: neutral | good | warning | danger. A line flagged `beat => true`
 * is an emotional beat (level-down, promotion, phase change) and forces the
 * report to stay visible even when sol_report_skip is set.
 */
class SolReportService
{
    /** Colony resource IDs (see docs/game-reference.md). */
    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

    private const RES_ORGANICS = 5;

    private const RES_TRUST = 12;

    /** Tradeable colony resources shown in the production group, id => lang key. */
    private const PRODUCTION_RESOURCES = [
        self::RES_REGOLITH => 'res_regolith',
        self::RES_COMPOUNDS => 'res_werkstoffe',
        self::RES_ORGANICS => 'res_organika',
    ];

    /**
     * Capture the "before" state of a colony for later diffing.
     *
     * @return array{resources:array<int,int>,credits:int,supply:int,trust:int,buildings:array<int,array{level:int,status:float}>,advisors:array<int,int>,phase:int}
     */
    public function snapshot(int $colonyId, int $userId, int $phase): array
    {
        $resources = DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->pluck('amount', 'resource_id')
            ->map(fn ($a) => (int) $a)
            ->all();

        $ur = DB::table('user_resources')->where('user_id', $userId)->first();

        // Key by building_id:instance_id so multi-instance buildings (e.g. two
        // hangars share building_id) do not collide in the wear comparison.
        $buildings = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->get(['building_id', 'instance_id', 'level', 'status_points'])
            ->mapWithKeys(fn ($b) => [$b->building_id.':'.$b->instance_id => [
                'level' => (int) $b->level,
                'status' => (float) $b->status_points,
            ]])
            ->all();

        $advisors = DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->pluck('rank', 'id')
            ->map(fn ($r) => (int) $r)
            ->all();

        return [
            'resources' => $resources,
            'credits' => (int) ($ur->credits ?? 0),
            'supply' => (int) ($ur->supply ?? 0),
            'trust' => (int) ($resources[self::RES_TRUST] ?? 0),
            'buildings' => $buildings,
            'advisors' => $advisors,
            'phase' => $phase,
        ];
    }

    /**
     * Build the report for a run after its tick has been processed.
     *
     * @param  array  $before  Snapshot taken before the tick (see snapshot()).
     */
    public function buildReport(Run $run, array $before, bool $skipPref): array
    {
        $colonyId = (int) $run->colony_id;
        $userId = (int) $run->user_id;
        $tick = (int) $run->current_tick; // the tick that was just processed
        $solLimit = (int) config('game.run.tick_limit', 100);

        $completedSol = max(1, $tick);
        $nextSol = min($solLimit, $tick + 1);

        $events = $this->eventsAtTick($userId, $tick);

        $groups = [];
        $forceShow = false;

        $decay = $this->decayGroup($colonyId, $before, $events, $forceShow);
        if ($decay !== null) {
            $groups[] = $decay;
        }

        $eventsGroup = $this->eventsGroup($events);
        if ($eventsGroup !== null) {
            $groups[] = $eventsGroup;
        }

        $groups[] = $this->productionGroup($colonyId, $userId, $before);
        $groups[] = $this->colonyGroup($colonyId, $userId, $before, $forceShow);

        $finale = $this->finale($run);
        if ($finale === null) {
            $groups[] = $this->runGroup($run, $before, $completedSol, $solLimit, $forceShow);
        } else {
            $forceShow = true;
        }

        return [
            'completed_sol' => $completedSol,
            'next_sol' => $nextSol,
            'run_status' => $run->status,
            'result_url' => $finale !== null ? route('run.result', $run->id) : null,
            'skip_pref' => $skipPref,
            'force_show' => $forceShow,
            'finale' => $finale,
            'groups' => $groups,
        ];
    }

    // ── Group 1: decay ────────────────────────────────────────────────────────

    private function decayGroup(int $colonyId, array $before, array $events, bool &$forceShow): ?array
    {
        $lines = [];

        // Level-downs (buildings, ships, research) — emotional beats.
        foreach ($events['techtree.level_down'] ?? [] as $p) {
            $type = $p['entity_type'] ?? 'building';
            $name = $this->entityName($p['entity_name'] ?? null, $type);
            $newLevel = isset($p['new_level']) ? (int) $p['new_level'] : null;

            if ($type === 'ship') {
                $detail = __('colony.sol_report_ship_destroyed');
            } elseif ($newLevel !== null) {
                $detail = __('colony.sol_report_level_to', ['level' => $newLevel]);
            } else {
                $detail = __('colony.sol_report_level_lost');
            }

            $lines[] = [
                'label' => $name,
                'detail' => $detail,
                'tone' => 'danger',
                'beat' => true,
            ];
            $forceShow = true;
        }

        // General wear: buildings that lost status this Sol without levelling down.
        if (empty($lines)) {
            $worn = false;
            $after = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->get(['building_id', 'instance_id', 'status_points']);
            foreach ($after as $b) {
                $prev = $before['buildings'][$b->building_id.':'.$b->instance_id]['status'] ?? null;
                if ($prev !== null && (float) $b->status_points < $prev) {
                    $worn = true;
                    break;
                }
            }
            if (! $worn) {
                return null; // nothing decayed (e.g. all freshly repaired) → skip group
            }
            $lines[] = [
                'label' => __('colony.sol_report_wear_label'),
                'detail' => __('colony.sol_report_wear_detail'),
                'tone' => 'neutral',
                'beat' => false,
            ];
        }

        return [
            'key' => 'decay',
            'title' => __('colony.sol_report_group_decay'),
            'icon' => 'arrow-down-circle',
            'lines' => $lines,
        ];
    }

    // ── Group 2: events ─────────────────────────────────────────────────────────

    private function eventsGroup(array $events): ?array
    {
        $lines = [];

        if (! empty($events['merchant.visit'])) {
            $lines[] = $this->staticLine(__('colony.sol_report_event_merchant'), 'good');
        }

        if (empty($lines)) {
            return null;
        }

        return [
            'key' => 'events',
            'title' => __('colony.sol_report_group_events'),
            'icon' => 'broadcast',
            'lines' => $lines,
        ];
    }

    // ── Group 3: production ───────────────────────────────────────────────────

    private function productionGroup(int $colonyId, int $userId, array $before): array
    {
        $afterResources = DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->pluck('amount', 'resource_id')
            ->map(fn ($a) => (int) $a)
            ->all();

        $lines = [];
        $anyYield = false;

        foreach (self::PRODUCTION_RESOURCES as $resId => $langKey) {
            $from = (int) ($before['resources'][$resId] ?? 0);
            $to = (int) ($afterResources[$resId] ?? 0);
            if ($to <= $from) {
                continue; // only show resources that grew (trade losses handled elsewhere)
            }
            $anyYield = true;
            $lines[] = [
                'label' => __('resources.'.$langKey),
                'detail' => '+'.($to - $from),
                'tone' => 'good',
                'from' => $from,
                'to' => $to,
            ];
        }

        if (! $anyYield) {
            $lines[] = $this->staticLine(__('colony.sol_report_no_production'), 'warning');
        }

        // Supply cap (set, not incremented — show as current cap).
        $afterSupply = (int) (DB::table('user_resources')->where('user_id', $userId)->value('supply') ?? 0);
        $lines[] = [
            'label' => __('resources.res_supply'),
            'detail' => (string) $afterSupply,
            'tone' => $afterSupply < ($before['supply'] ?? 0) ? 'warning' : 'neutral',
            'from' => (int) ($before['supply'] ?? 0),
            'to' => $afterSupply,
        ];

        // Provisioning (Organika): make hunger visible — otherwise the escalating trust
        // hit from food_shortage has no on-screen cause.
        $usedSupply = (int) DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $colonyId)->where('cb.level', '>', 0)
            ->sum(DB::raw('cb.level * COALESCE(b.supply_cost, 0)'));
        $foodNeed = intdiv($usedSupply, max(1, (int) config('game.food.supply_per_eater', 4)));
        if ($foodNeed >= 1) {
            $hungry = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('hunger_streak') > 0;
            $lines[] = [
                'label' => __('colony.sol_report_food'),
                'detail' => $hungry ? __('colony.sol_report_food_shortage') : __('colony.sol_report_food_ok', ['amount' => $foodNeed]),
                'tone' => $hungry ? 'warning' : 'neutral',
            ];
        }

        return [
            'key' => 'production',
            'title' => __('colony.sol_report_group_production'),
            'icon' => 'box-seam',
            'lines' => $lines,
        ];
    }

    // ── Group 4: colony & personnel ───────────────────────────────────────────

    private function colonyGroup(int $colonyId, int $userId, array $before, bool &$forceShow): array
    {
        $lines = [];

        // Trust.
        $trustAfter = (int) (DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', self::RES_TRUST)
            ->value('amount') ?? 0);
        $trustFrom = (int) ($before['trust'] ?? 0);
        $trustDelta = $trustAfter - $trustFrom;
        $lines[] = [
            'label' => __('resources.res_trust'),
            'detail' => $this->signed($trustDelta),
            'tone' => $trustDelta > 0 ? 'good' : ($trustDelta < 0 ? 'danger' : 'neutral'),
            'from' => $trustFrom,
            'to' => $trustAfter,
        ];

        // Credits (net change over the Sol).
        $creditsAfter = (int) (DB::table('user_resources')->where('user_id', $userId)->value('credits') ?? 0);
        $creditsFrom = (int) ($before['credits'] ?? 0);
        $creditsDelta = $creditsAfter - $creditsFrom;
        $lines[] = [
            'label' => __('resources.res_credits'),
            'detail' => $this->signed($creditsDelta).' Cr',
            'tone' => $creditsDelta > 0 ? 'good' : ($creditsDelta < 0 ? 'danger' : 'neutral'),
            'from' => $creditsFrom,
            'to' => $creditsAfter,
        ];

        // Advisor promotions (rank increased over the Sol) — emotional beat.
        $afterRanks = DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->pluck('rank', 'id')
            ->map(fn ($r) => (int) $r)
            ->all();
        foreach ($afterRanks as $advisorId => $rank) {
            $prevRank = $before['advisors'][$advisorId] ?? null;
            if ($prevRank !== null && $rank > $prevRank) {
                $name = (string) (DB::table('advisors')->where('id', $advisorId)->value('name') ?? __('colony.sol_report_advisor'));
                $lines[] = [
                    'label' => $name,
                    'detail' => __('colony.sol_report_advisor_promoted', ['rank' => $rank]),
                    'tone' => 'good',
                    'beat' => true,
                ];
                $forceShow = true;
            }
        }

        return [
            'key' => 'colony',
            'title' => __('colony.sol_report_group_colony'),
            'icon' => 'people',
            'lines' => $lines,
        ];
    }

    // ── Group 5: run progress ───────────────────────────────────────────────────

    private function runGroup(Run $run, array $before, int $completedSol, int $solLimit, bool &$forceShow): array
    {
        $lines = [];

        // Phase change — emotional beat (full-width banner on the front-end).
        $phaseBefore = (int) ($before['phase'] ?? $run->phase);
        if ($run->phase > $phaseBefore) {
            $lines[] = [
                'label' => __('colony.sol_report_phase_reached', ['phase' => $run->phase]),
                'detail' => '',
                'tone' => 'good',
                'beat' => true,
            ];
            $forceShow = true;
        }

        // Objective progress (Phase 2 only).
        if ($run->phase >= 2) {
            $total = $run->objectives()->count();
            if ($total > 0) {
                $done = $run->objectives()->whereNotNull('completed_at')->count();
                $lines[] = $this->staticLine(
                    __('colony.sol_report_objectives', ['done' => $done, 'total' => $total]),
                    $done > 0 ? 'good' : 'neutral',
                );
            }
        }

        // Sol counter — orientation anchor, always last.
        $lines[] = $this->staticLine(
            __('colony.sol_report_sol_counter', ['sol' => $completedSol, 'limit' => $solLimit]),
            'neutral',
        );

        return [
            'key' => 'run',
            'title' => __('colony.sol_report_group_run'),
            'icon' => 'flag',
            'lines' => $lines,
        ];
    }

    // ── Run finale (win / fail) ─────────────────────────────────────────────────

    private function finale(Run $run): ?array
    {
        if ($run->status === 'completed') {
            return [
                'outcome' => 'win',
                'title' => __('colony.sol_report_finale_win_title'),
                'body' => __('colony.sol_report_finale_win_body'),
            ];
        }

        if ($run->status === 'failed') {
            $bodyKey = match ($run->fail_reason) {
                'trust_collapse' => 'run.run_failed_trust',
                'time_limit' => 'run.run_failed_time',
                'nexus_debt' => 'run.run_failed_nexus_debt',
                default => 'colony.sol_report_finale_lose_body',
            };

            return [
                'outcome' => 'lose',
                'title' => __('colony.sol_report_finale_lose_title'),
                'body' => __($bodyKey),
            ];
        }

        return null;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * @return array<string,array<int,array<string,mixed>>> event => list of decoded parameter arrays
     */
    private function eventsAtTick(int $userId, int $tick): array
    {
        $rows = DB::table('colony_log')
            ->where('user', $userId)
            ->where('tick', $tick)
            ->get(['event', 'parameters']);

        $grouped = [];
        foreach ($rows as $row) {
            $params = json_decode($row->parameters ?? '[]', true);
            $grouped[$row->event][] = is_array($params) ? $params : [];
        }

        return $grouped;
    }

    private function entityName(?string $key, string $type): string
    {
        if ($key === null || $key === '') {
            return '?';
        }

        foreach (['techtree.', 'buildings.', 'ships.', 'knowledge.'] as $ns) {
            $translated = __($ns.$key);
            if ($translated !== $ns.$key) {
                return $translated;
            }
        }

        return $key;
    }

    private function staticLine(string $label, string $tone): array
    {
        return ['label' => $label, 'detail' => '', 'tone' => $tone];
    }

    private function signed(int $n): string
    {
        return ($n > 0 ? '+' : '').$n;
    }
}
