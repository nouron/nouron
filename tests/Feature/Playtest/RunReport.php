<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
use App\Services\TrustService;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates one bot run into the JSON artifact + STDERR summary table
 * described in the plan. Read-only: everything here is observation, not
 * game state — same as BotStrategy's DB reads.
 */
class RunReport
{
    /** @var array<int, array> */
    private array $sols = [];

    private ?int $phase2StartSol = null;

    /**
     * A30/B1a: maps a BotSession::act() rule name to its AP-spend category.
     * Unmapped rules (hire_advisor, accept_bar_offer, buy_corporate_harvester_offer,
     * sol_next) cost Credits or nothing, not AP — deliberately excluded rather
     * than lumped into a catch-all "other" bucket the plan doesn't ask for.
     */
    private const AP_CATEGORY_MAP = [
        'repair_critical' => 'repair',
        'invest_cc' => 'project',
        'invest_production' => 'project',
        'place_building' => 'project',
        'place_harvester_instance2' => 'project',
        'relocate_harvester' => 'project',
        'research_knowledge' => 'project',
        'explore_tile' => 'action',
        'deep_scan_signal_tile' => 'action',
        'dispatch_mission' => 'action',
        'dispatch_salvage_mission' => 'action',
        'request_ship' => 'action',
    ];

    public function __construct(private readonly int $seed, private readonly string $profile = 'default') {}

    /**
     * Sums ap_before-ap_after for this Sol's log entries, grouped by
     * AP_CATEGORY_MAP. A negative or zero delta (rejected action, DB
     * untouched) never counts — only real spend does.
     *
     * @return array{repair: int, project: int, action: int}
     */
    private function apSpendByCategory(BotSession $bot): array
    {
        $spent = ['repair' => 0, 'project' => 0, 'action' => 0];

        foreach ($bot->log as $entry) {
            if ($entry['sol'] !== $bot->sol) {
                continue;
            }

            $category = self::AP_CATEGORY_MAP[$entry['rule']] ?? null;
            if ($category === null) {
                continue;
            }

            $delta = ($entry['ap_before'] ?? 0) - ($entry['ap_after'] ?? 0);
            if ($delta > 0) {
                $spent[$category] += $delta;
            }
        }

        return $spent;
    }

    /**
     * Placed Harvester instances (tile_x/tile_y NOT NULL), keyed by
     * instance_id — lets the dashboard correlate relocate_harvester actions
     * with the actual DB position per Sol (B1e).
     *
     * @return array<int, array{0:int, 1:int}>
     */
    private function harvesterPositions(int $colonyId): array
    {
        return DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Harvester->value)
            ->whereNotNull('tile_x')
            ->whereNotNull('tile_y')
            ->get(['instance_id', 'tile_x', 'tile_y'])
            ->mapWithKeys(fn ($row) => [
                (int) $row->instance_id => [(int) $row->tile_x, (int) $row->tile_y],
            ])
            ->all();
    }

    /**
     * A30/B1c: this Sol's Regolith gains by source. 'accept_bar_offer' deltas
     * are 'trade'; the 'sol_next' delta is split into 'mission' (read from the
     * real 'hangar.mission_completed' colony_log event for this tick — no
     * re-derivation, the game already computed and logged the exact reward)
     * and 'harvester' (the remainder — Regolith has no other tick-driven
     * source). 'event' stays 0: encounters affect production/AP, not a direct
     * Regolith credit, per GDD §9 — kept as a key for forward compatibility.
     * Only ok=true entries and positive deltas count; a rejected action or a
     * net-negative delta (repair spend, etc.) is not a "source".
     */
    private function regolithSources(BotSession $bot): array
    {
        $sources = ['harvester' => 0, 'mission' => 0, 'trade' => 0, 'event' => 0];

        foreach ($bot->log as $entry) {
            if ($entry['sol'] !== $bot->sol || ! $entry['ok']) {
                continue;
            }

            $delta = $entry['regolith_after'] - $entry['regolith_before'];
            if ($delta <= 0) {
                continue;
            }

            if ($entry['rule'] === 'accept_bar_offer') {
                $sources['trade'] += $delta;
            } elseif ($entry['rule'] === 'sol_next') {
                $missionAmount = $this->missionRewardAmount($bot, 'regolith');
                $sources['mission'] += $missionAmount;
                $sources['harvester'] += max(0, $delta - $missionAmount);
            }
        }

        return $sources;
    }

    /**
     * A30/B1c: same shape as regolithSources(), but 'harvester' becomes
     * 'agrardom' (the Agrardom is what actually produces Organika — reusing
     * "harvester" here would be a misnomer, even though the plan's own wording
     * says "wie oben"). Also returns the consumption split the Owner decided
     * (F8/3): 'hunger_consumed' (GameTick::processFoodConsumption(), read via
     * the same ResourcesService::foodNeed() the game itself uses — no
     * re-derivation of the formula) and 'mission_dispatch_consumed'
     * (HangarService::dispatch()'s immediate Organika cost, observed directly
     * from the dispatch actions' own before/after delta).
     *
     * @return array{sources: array{agrardom:int, mission:int, trade:int, event:int}, consumption: array{hunger_consumed:int, mission_dispatch_consumed:int}}
     */
    private function organicsSourcesAndConsumption(BotSession $bot): array
    {
        $sources = ['agrardom' => 0, 'mission' => 0, 'trade' => 0, 'event' => 0];
        $consumption = ['hunger_consumed' => 0, 'mission_dispatch_consumed' => 0];

        $dispatchRules = ['dispatch_mission', 'dispatch_salvage_mission', 'request_ship'];

        foreach ($bot->log as $entry) {
            if ($entry['sol'] !== $bot->sol || ! $entry['ok']) {
                continue;
            }

            $delta = $entry['organics_after'] - $entry['organics_before'];

            if ($entry['rule'] === 'accept_bar_offer' && $delta > 0) {
                $sources['trade'] += $delta;
            } elseif (in_array($entry['rule'], $dispatchRules, true) && $delta < 0) {
                $consumption['mission_dispatch_consumed'] += -$delta;
            } elseif ($entry['rule'] === 'sol_next') {
                $missionAmount = $this->missionRewardAmount($bot, 'organics');
                $sources['mission'] += $missionAmount;

                $hungerConsumed = min($entry['organics_before'], app(ResourcesService::class)->foodNeed($bot->colonyId));
                $consumption['hunger_consumed'] += $hungerConsumed;

                // delta = agrardom_production + mission_reward - hunger_consumed
                $agrardomProduction = $delta - $missionAmount + $hungerConsumed;
                $sources['agrardom'] += max(0, $agrardomProduction);
            }
        }

        return ['sources' => $sources, 'consumption' => $consumption];
    }

    /**
     * Sums the given resource type's reward across every real
     * 'hangar.mission_completed' event for this Sol's tick (tick == Sol
     * number — the codebase's established 1:1 mapping, GDD "Sol" is the
     * player-facing name for "tick"). Reads the game's own logged reward
     * breakdown (GameTick::payMissionRewards()' $details, stored verbatim as
     * the event's 'rewards' parameter) rather than guessing from the delta.
     */
    private function missionRewardAmount(BotSession $bot, string $resourceType): int
    {
        return DB::table('colony_log')
            ->where('user', $bot->userId)
            ->where('tick', $bot->sol)
            ->where('event', 'hangar.mission_completed')
            ->get(['parameters'])
            ->sum(function ($row) use ($resourceType) {
                $params = json_decode($row->parameters, true) ?? [];

                return (int) ($params['rewards'][$resourceType] ?? 0);
            });
    }

    /**
     * Capture one Sol's state — call once per Sol, after the strategy has
     * acted but BEFORE nextSol() (locked_actionpoints still reflect that Sol).
     */
    public function snapshot(BotSession $bot): void
    {
        $colonyId = $bot->colonyId;
        $ccLevel = BotStrategy::ccLevel($bot);

        // Note: AP pool is now unified (GDD §13.1) — no longer separate by type
        $apAvailable = app(AdvisorService::class)->getAvailableActionPoints($colonyId);
        // "inflow" = the colony's total AP pool this tick (base + advisors,
        // trust/plague multipliers applied) — what generatePassiveCredits()'s
        // AP equivalent produced, before this Sol's spend. Distinct from
        // $apAvailable, which is what's LEFT after spending (kept below as
        // 'total'/'ap_unspent' for backward compat with existing consumers).
        $apInflow = app(AdvisorService::class)->getApBreakdown($colonyId)['total'];
        $apSpent = $this->apSpendByCategory($bot);
        $regolithSources = $this->regolithSources($bot);
        $organics = $this->organicsSourcesAndConsumption($bot);

        // B1d: Reuses ResourcesService::getSupplyBreakdown() — the same canonical
        // cap/used numbers behind the resource-bar SUP popup and GameTick's
        // "supply_cap_full" onboarding trigger — instead of re-deriving a
        // second supply formula here.
        $supplyBreakdown = app(ResourcesService::class)->getSupplyBreakdown($colonyId);
        $supplyUsed = $supplyBreakdown['used']['buildings'] + $supplyBreakdown['used']['researches'] + $supplyBreakdown['used']['advisors'];
        $supplyCap = $supplyBreakdown['cap'];
        // Supply has no hard upper bound (GDD §13.1, only a decay penalty past
        // cap), so an over-cap colony is a real, expected state — clamp the
        // reported ratio to [0, 1] rather than exposing e.g. 1.4, which
        // wouldn't be meaningful on the dashboard's utilization chart.
        $supplyUtilization = $supplyCap > 0 ? min(1.0, max(0.0, $supplyUsed / $supplyCap)) : 0.0;

        // B1b: per-instance level + ap_spend — Metriken 2-4 (Projektdauer/Fertigstellung).
        $buildings = [];
        foreach (DB::table('colony_buildings')->where('colony_id', $colonyId)->get() as $row) {
            $buildings["{$row->building_id}:{$row->instance_id}"] = [
                'level' => (int) $row->level,
                'ap_spend' => (int) $row->ap_spend,
            ];
        }

        $phase = (int) (DB::table('runs')->where('id', $bot->runId)->value('phase') ?? 1);
        if ($phase >= 2 && $this->phase2StartSol === null) {
            $this->phase2StartSol = $bot->sol;
        }

        $this->sols[] = [
            'sol' => $bot->sol,
            'trust' => app(TrustService::class)->getTrust($colonyId),
            'credits' => BotStrategy::credits($bot),
            'regolith' => BotStrategy::regolith($bot),
            'organics' => BotStrategy::organics($bot),
            'ap' => [
                'total' => $apAvailable,
                'inflow' => $apInflow,
                'repair_spent' => $apSpent['repair'],
                'project_spent' => $apSpent['project'],
                'action_spent' => $apSpent['action'],
                'unspent' => $apAvailable,
            ],
            'ap_unspent' => $apAvailable,
            'cc_level' => $ccLevel,
            'advisors' => DB::table('advisors')->where('colony_id', $colonyId)->count(),
            'regolith_sources' => $regolithSources,
            'organics_sources' => $organics['sources'],
            'organics_consumption' => $organics['consumption'],
            'buildings' => $buildings,
            'supply' => [
                'used' => $supplyUsed,
                'cap' => $supplyCap,
                'utilization' => $supplyUtilization,
            ],
            'harvester_positions' => $this->harvesterPositions($colonyId),
        ];
    }

    /**
     * A31/B2a: derives project-completion metrics (2-4 of the plan) purely
     * from the B1b `buildings` snapshots already captured per Sol — no new
     * DB reads. Each building instance is tracked independently across the
     * whole run since one instance can complete several level-up cycles.
     *
     *   project_durations          — Sole von erstem ap_spend>0 bis Level-Anstieg,
     *                                 je abgeschlossenem Zyklus (nicht je Instanz)
     *   last_completion_sol        — höchster Sol mit irgendeinem Level-Anstieg
     *   median_concurrent_projects — Median über alle Sole der Instanzenzahl
     *                                 mit ap_spend>0 an diesem Sol ("Baustelle")
     */
    private function projectMetrics(): array
    {
        $state = []; // building key => ['prevLevel' => int, 'startSol' => ?int]
        $durations = [];
        $lastCompletionSol = null;
        $concurrentPerSol = [];

        foreach ($this->sols as $snapshot) {
            $sol = $snapshot['sol'];
            $activeCount = 0;

            foreach ($snapshot['buildings'] ?? [] as $key => $info) {
                $level = $info['level'];
                $apSpend = $info['ap_spend'];

                if ($apSpend > 0) {
                    $activeCount++;
                }

                if (! array_key_exists($key, $state)) {
                    $state[$key] = ['prevLevel' => $level, 'startSol' => $apSpend > 0 ? $sol : null];

                    continue;
                }

                if ($apSpend > 0 && $state[$key]['startSol'] === null) {
                    $state[$key]['startSol'] = $sol;
                }

                if ($level > $state[$key]['prevLevel']) {
                    if ($state[$key]['startSol'] !== null) {
                        $durations[] = $sol - $state[$key]['startSol'];
                        $lastCompletionSol = $lastCompletionSol === null ? $sol : max($lastCompletionSol, $sol);
                    }
                    $state[$key]['startSol'] = null;
                }

                $state[$key]['prevLevel'] = $level;
            }

            $concurrentPerSol[] = $activeCount;
        }

        return [
            'project_durations' => $durations,
            'last_completion_sol' => $lastCompletionSol,
            'median_concurrent_projects' => self::median($concurrentPerSol),
        ];
    }

    private static function median(array $values): float
    {
        if ($values === []) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);

        return $count % 2 === 0
            ? ($values[$mid - 1] + $values[$mid]) / 2
            : (float) $values[$mid];
    }

    /**
     * @return array{seed:int, profile:string, outcome:array, phase2_start_sol:?int, objectives:array,
     *               actions:array, rejections:array, burnout:array, sols:array, log:array, project_metrics:array}
     */
    public function build(BotSession $bot): array
    {
        $run = Run::findOrFail($bot->runId);

        $ok = 0;
        $rejections = [];
        foreach ($bot->log as $entry) {
            if ($entry['ok']) {
                $ok++;
            } else {
                $rejections[$entry['error']] = ($rejections[$entry['error']] ?? 0) + 1;
            }
        }
        $rejected = count($bot->log) - $ok;

        $objectives = $run->objectives->map(fn ($o) => [
            'task_key' => $o->task_key,
            'current' => (int) $o->current_value,
            'target' => (int) $o->target_value,
            'completed_at' => $o->completed_at,
        ])->values()->all();

        $advisorActiveTicks = DB::table('advisors')
            ->where('colony_id', $bot->colonyId)
            ->pluck('active_ticks')
            ->map(fn ($v) => (int) $v)
            ->all();

        $observedLockouts = DB::table('advisors')
            ->where('colony_id', $bot->colonyId)
            ->whereNotNull('unavailable_until_tick')
            ->count();

        return [
            'seed' => $this->seed,
            'profile' => $this->profile,
            'outcome' => [
                'status' => $run->status,
                'fail_reason' => $run->fail_reason,
                'sols' => $bot->sol,
                'score' => (int) ($run->score ?? 0),
            ],
            'phase2_start_sol' => $this->phase2StartSol,
            'objectives' => $objectives,
            'actions' => [
                'attempted' => count($bot->log),
                'ok' => $ok,
                'rejected' => $rejected,
            ],
            'rejections' => $rejections,
            // config('game.advisors.burnout') doesn't exist yet (GDD: formula follows
            // the first playtest) — this reports the raw material for that formula,
            // not a guess at it.
            'burnout' => [
                'implemented' => false,
                'observed_lockouts' => $observedLockouts,
                'advisor_active_ticks' => $advisorActiveTicks,
            ],
            'sols' => $this->sols,
            // Raw per-action log (sol/rule/ok/error) — dashboard event markers
            // read this directly, kept unaggregated unlike 'rejections' above.
            'log' => $bot->log,
            'project_metrics' => $this->projectMetrics(),
        ];
    }

    public function write(array $report): string
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$this->profile}-{$this->seed}-".now()->format('Ymd_His').'.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        return $path;
    }

    public function printTable(array $report): void
    {
        fwrite(STDERR, sprintf(
            "\n[playtest] seed=%d profile=%s status=%s fail_reason=%s sols=%d phase2_start_sol=%s score=%d actions=%d/%d rejected=%d\n",
            $report['seed'],
            $report['profile'],
            $report['outcome']['status'],
            $report['outcome']['fail_reason'] ?? '-',
            $report['outcome']['sols'],
            $report['phase2_start_sol'] ?? '-',
            $report['outcome']['score'],
            $report['actions']['ok'],
            $report['actions']['attempted'],
            $report['actions']['rejected'],
        ));

        if (! empty($report['rejections'])) {
            fwrite(STDERR, '[playtest] rejections: '.json_encode($report['rejections'])."\n");
        }
    }
}
