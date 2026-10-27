<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\ColonyTileService;
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

    /** @var list<array{sol: int, in: array<string, int>, out: array<string, int>, stock: int, unattributed: int}> */
    private array $ledgerRows = [];

    private ?int $ledgerStartStock = null;

    /**
     * A30/B1a: maps a BotSession::act() rule name to its AP-spend category.
     * Unmapped rules (hire_advisor, accept_bar_offer,
     * sol_next) cost Credits or nothing, not AP — deliberately excluded rather
     * than lumped into a catch-all "other" bucket the plan doesn't ask for.
     */
    private const AP_CATEGORY_MAP = [
        'repair_critical' => 'repair',
        'repair_maintenance' => 'repair',
        'invest_cc' => 'project',
        'invest_production' => 'project',
        'place_building' => 'project',
                'relocate_harvester' => 'project',
        'research_knowledge' => 'project',
        'explore_tile' => 'action',
        'deep_scan_signal_tile' => 'action',
        'dispatch_mission' => 'action',
                'dispatch_compounds_mission' => 'action',
        'dispatch_find_flight' => 'action',
        'repair_ship_for_flight' => 'repair',
        'request_ship' => 'action',
    ];

    public function __construct(private readonly int $seed, private readonly string $profile = 'default', private readonly string $opening = 'auto', private readonly string $engine = 'legacy') {}

    /** Report file prefix: legacy keeps the historic `{profile}` name, other engines `{profile}.{engine}`. */
    public static function filePrefix(string $profile, string $engine): string
    {
        return $engine === 'legacy' ? $profile : "{$profile}.{$engine}";
    }

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
     * Ledger source tag => regolith_sources bucket. harvester is the pure
     * Harvester yield since the ledger (no longer the remainder of the sol_next
     * delta); geology, import, recycling and concern are their own buckets now.
     * Tags not listed land in 'other'. 'event' stays 0 (GDD §9 encounters do not
     * credit Regolith), kept for backward compatibility.
     */
    private const SOURCE_BUCKETS = [
        'harvester_base' => 'harvester',
        'geology' => 'geology',
        'mission_ore' => 'mission',
        'mission_loot' => 'mission',
        'cantina_guest' => 'trade',
        'merchant_buy' => 'trade',
        'find_self' => 'find',
        'find_flight' => 'find_flight',
        'import' => 'import',
        'recycle' => 'recycling',
        'cantina_concern' => 'concern',
    ];

    /**
     * This Sol's Regolith inflows by bucket, from the tagged ledger bookings
     * (BotSession::$regolithLedger) — the buckets add up to the ledger inflow.
     *
     * @param  array<string, int>  $inflows  ledger tag => amount
     * @return array<string, int>
     */
    private static function regolithSources(array $inflows): array
    {
        $sources = ['harvester' => 0, 'mission' => 0, 'trade' => 0, 'find' => 0, 'find_flight' => 0, 'event' => 0,
            'geology' => 0, 'import' => 0, 'recycling' => 0, 'concern' => 0, 'other' => 0];
        foreach ($inflows as $tag => $amount) {
            $sources[self::SOURCE_BUCKETS[$tag] ?? 'other'] += $amount;
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

        $dispatchRules = ['dispatch_mission', 'dispatch_compounds_mission', 'dispatch_find_flight', 'request_ship'];

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

    /** AP actually spent this Sol by one rule (positive ap_before − ap_after of ok entries). */
    private function ruleApSpent(BotSession $bot, string $rule): int
    {
        $sum = 0;
        foreach ($bot->log as $entry) {
            if ($entry['sol'] === $bot->sol && $entry['rule'] === $rule && $entry['ok']) {
                $sum += max(0, ($entry['ap_before'] ?? 0) - ($entry['ap_after'] ?? 0));
            }
        }

        return $sum;
    }

    /**
     * T30 K16/K17: scanned real finds waiting while a hangar stands, by findAction() reason
     * (§13.8 grouping); zone_blocked = locked finds on a colony-zone tile.
     *
     * @return array{needs_level:int, needs_freighter:int, ship_busy:int, no_ship_ready:int, zone_blocked:int}
     */
    private function findWaits(int $colonyId): array
    {
        $map = ['needs_level' => 'needs_level', 'needs_freighter' => 'needs_freighter', 'ship_inactive' => 'needs_level',
            'ship_busy' => 'ship_busy', 'ship_worn' => 'no_ship_ready', 'no_ship' => 'no_ship_ready'];
        $waits = ['needs_level' => 0, 'needs_freighter' => 0, 'ship_busy' => 0, 'no_ship_ready' => 0, 'zone_blocked' => 0];
        $service = app(ColonyTileService::class);
        foreach (DB::table('colony_tiles')->where('colony_id', $colonyId)->where('is_deep_scanned', 1)
            ->whereIn('event_type', ColonyTileService::FIND_TYPES)->get() as $tile) {
            $key = $map[$service->findAction($colonyId, $tile)['reason']] ?? null;
            if ($key === null) {
                continue;
            }
            $waits[$key]++;
            if (in_array($key, ['needs_level', 'needs_freighter'], true) && (int) $tile->is_colony_zone === 1) {
                $waits['zone_blocked']++;
            }
        }

        return $waits;
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
     * Records and returns this Sol's ledger row (budget spec E.1 M1). unattributed =
     * stock change since the previous snapshot minus (in − out): 0 when every
     * Regolith booking was tagged, anything else names a booking site the
     * recorder misses (RegolithLedgerInvariantTest).
     *
     * @return array{sol: int, in: array<string, int>, out: array<string, int>, stock: int, unattributed: int}
     */
    private function ledgerRow(BotSession $bot): array
    {
        $this->ledgerStartStock ??= $bot->regolithStartStock;
        $previous = $this->ledgerRows === [] ? $this->ledgerStartStock : end($this->ledgerRows)['stock'];
        $in = $bot->regolithLedger[$bot->sol]['in'] ?? [];
        $out = $bot->regolithLedger[$bot->sol]['out'] ?? [];
        ksort($in);
        ksort($out);
        $stock = BotStrategy::regolith($bot);

        return $this->ledgerRows[] = [
            'sol' => $bot->sol,
            'in' => $in,
            'out' => $out,
            'stock' => $stock,
            'unattributed' => ($stock - $previous) - (array_sum($in) - array_sum($out)),
        ];
    }

    /** @return array{start_stock: int, sources: array<string, int>, sinks: array<string, int>, per_sol: list<array>} */
    private function ledgerSummary(): array
    {
        $sources = [];
        $sinks = [];
        foreach ($this->ledgerRows as $row) {
            foreach ($row['in'] as $tag => $amount) {
                $sources[$tag] = ($sources[$tag] ?? 0) + $amount;
            }
            foreach ($row['out'] as $tag => $amount) {
                $sinks[$tag] = ($sinks[$tag] ?? 0) + $amount;
            }
        }
        arsort($sources);
        arsort($sinks);

        return [
            'start_stock' => (int) $this->ledgerStartStock,
            'sources' => $sources,
            'sinks' => $sinks,
            'per_sol' => $this->ledgerRows,
        ];
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
        $regolithSources = self::regolithSources($this->ledgerRow($bot)['in']);
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

        // A37-Rest investigation prep: ship ownership was never tracked, only
        // harvester positions (B1e) — needed to check whether a mission-dispatch
        // candidate never fires because the colony never actually owns the
        // required ship type.
        $ships = [];
        $shipStates = [];
        foreach (DB::table('colony_ships')->where('colony_id', $colonyId)->get() as $row) {
            $shipId = (int) $row->ship_id;
            $ships[$shipId] = ($ships[$shipId] ?? 0) + 1;
            $shipStates[$shipId][$row->ship_state] = ($shipStates[$shipId][$row->ship_state] ?? 0) + 1;
        }

        // A37 investigation prep: research/Kenntnis levels were never tracked
        // (only building levels, B1b) — needed to check whether knowledge
        // progression is a factor in "no run ever wins".
        $researches = [];
        foreach (DB::table('colony_researches')->where('colony_id', $colonyId)->get() as $row) {
            $researches[(int) $row->research_id] = [
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
            // T9: consecutive hungry Sols as of the last tick (GameTick::processFoodConsumption()).
            'hunger_streak' => (int) (DB::table('glx_colonies')->where('id', $colonyId)->value('hunger_streak') ?? 0),
            'compounds' => BotStrategy::compounds($bot),
            'ap' => [
                'total' => $apAvailable,
                'inflow' => $apInflow,
                'repair_spent' => $apSpent['repair'],
                'project_spent' => $apSpent['project'],
                'action_spent' => $apSpent['action'],
                'ship_repair_spent' => $this->ruleApSpent($bot, 'repair_ship_for_flight'),
                'unspent' => $apAvailable,
            ],
            'ap_unspent' => $apAvailable,
            'cc_level' => $ccLevel,
            'advisors' => DB::table('advisors')->where('colony_id', $colonyId)->count(),
            'regolith_sources' => $regolithSources,
            'find_waits' => $this->findWaits($colonyId),
            'organics_sources' => $organics['sources'],
            'organics_consumption' => $organics['consumption'],
            'buildings' => $buildings,
            'researches' => $researches,
            'ships' => $ships,
            'ship_states' => $shipStates,
            'supply' => [
                'used' => $supplyUsed,
                'cap' => $supplyCap,
                'utilization' => $supplyUtilization,
            ],
            'harvester_positions' => $this->harvesterPositions($colonyId),
            // A34 prep: nexus_debt_fail_threshold recalibration needs the
            // per-Sol curve, not just the pass/fail outcome — see
            // config/game.php → run.nexus_debt_fail_threshold.
            'nexus_debt' => (int) (DB::table('runs')->where('id', $bot->runId)->value('nexus_debt') ?? 0),
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
     * Spec 2.11: K18 (share of legacy decisions, %), K20 (median successful
     * decisions per Sol over Sols 0..sol-1) and K22 (band-faithful shadow
     * divergence, plus the cross-band one as info). null for a legacy run.
     */
    private static function utilitySummary(BotSession $bot): ?array
    {
        if ($bot->decisions === []) {
            return null;
        }

        $n = count($bot->decisions);
        $bySource = array_count_values(array_column($bot->decisions, 'source'));
        $perSol = array_fill(0, max(1, $bot->sol), 0);
        foreach ($bot->decisions as $d) {
            if ($d['ok']) {
                $perSol[$d['sol']] = ($perSol[$d['sol']] ?? 0) + 1;
            }
        }

        $shadowed = array_values(array_filter($bot->decisions, fn (array $d): bool => ($d['shadow'] ?? null) !== null));
        $diverging = array_filter($shadowed, fn (array $d): bool => $d['shadow']['name'] !== $d['name'] || $d['shadow']['key'] !== $d['key']);
        $freed = array_values(array_filter($shadowed, fn (array $d): bool => ($d['shadow']['free'] ?? null) !== null));
        $freeDiverging = array_filter($freed, fn (array $d): bool => $d['shadow']['free']['name'] !== $d['name'] || $d['shadow']['free']['key'] !== $d['key']);

        return [
            'decisions' => $n,
            'legacy_share' => round(($bySource['legacy'] ?? 0) / $n * 100, 1),
            'mandatory_share' => round(($bySource['mandatory'] ?? 0) / $n * 100, 1),
            'utility_share' => round(($bySource['utility'] ?? 0) / $n * 100, 1),
            'median_decisions_per_sol' => self::median(array_values($perSol)),
            // K22: band-faithful (what the S3 engine would change); free = cross-band, informational only
            'shadow_divergence' => $shadowed === [] ? null : round(count($diverging) / count($shadowed) * 100, 1),
            'shadow_free_divergence' => $freed === [] ? null : round(count($freeDiverging) / count($freed) * 100, 1),
            'lambda_ap' => $bot->lambdaBySol,
        ];
    }

    /**
     * @return array{seed:int, profile:string, outcome:array, phase2_start_sol:?int, objectives:array,
     *               actions:array, rejections:array, burnout:array, sols:array, log:array,
     *               project_metrics:array, regolith_path_attribution:array, zero_ap_sols:array}
     */
    public function build(BotSession $bot, ?int $truncatedAtSol = null): array
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

        // A31/B2b: Metrik 9, per-run sum of regolithSources()'s per-Sol
        // buckets. Mapping follows the real GDD path lettering, not the
        // swapped one in the old plan text: Pfad A = Analytik-Labor/Geologie
        // (the 'harvester' + 'geology' buckets — what the old remainder bucket
        // meant, minus import/recycling it wrongly contained), Pfad B = Hangar
        // (Frachter-Mission plus Bergungsflug: the 'mission' + 'find_flight'
        // buckets), Pfad C = Cantina/Corvan (the 'trade' bucket). The switch to the
        // new ledger keys (spec E.1 M5) is a later step.
        $regolithPathAttribution = ['pfad_a_geologie' => 0, 'pfad_b_frachter' => 0, 'pfad_c_cantina' => 0];
        foreach ($this->sols as $sol) {
            $regolithPathAttribution['pfad_a_geologie'] += $sol['regolith_sources']['harvester'] + ($sol['regolith_sources']['geology'] ?? 0);
            $regolithPathAttribution['pfad_b_frachter'] += $sol['regolith_sources']['mission'] + ($sol['regolith_sources']['find_flight'] ?? 0);
            $regolithPathAttribution['pfad_c_cantina'] += $sol['regolith_sources']['trade'];
        }

        // B2c/Metrik 8 (Owner-Entscheidung F8/2): no per-domain breakdown —
        // AP domains no longer exist post pool consolidation (GDD §13.1).
        $zeroApSols = array_values(array_map(
            fn ($s) => $s['sol'],
            array_filter($this->sols, fn ($s) => $s['ap_unspent'] <= 0)
        ));

        return [
            'seed' => $this->seed,
            'profile' => $this->profile,
            'opening' => $this->opening,
            'engine' => $this->engine,
            // Only set when the bot stopped at --until-sol N with the run still active.
            ...($truncatedAtSol !== null ? ['truncated_at_sol' => $truncatedAtSol] : []),
            'outcome' => [
                'status' => $run->status,
                'fail_reason' => $run->fail_reason,
                'sols' => $bot->sol,
                'score' => (int) ($run->score ?? 0),
                'nexus_debt_final' => (int) ($run->nexus_debt ?? 0),
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
            'decisions' => $bot->decisions,
            'utility_summary' => self::utilitySummary($bot),
            // Review S3 I4: why place_building found nothing, per Sol (diagnostic).
            'place_null_reasons' => $bot->placeNullReasons,
            'project_metrics' => $this->projectMetrics(),
            'regolith_path_attribution' => $regolithPathAttribution,
            // Budget spec E.1 M1: tagged Regolith in-/outflows per Sol and per run.
            'regolith_ledger' => $this->ledgerSummary(),
            'zero_ap_sols' => [
                'count' => count($zeroApSols),
                'sols' => $zeroApSols,
            ],
        ];
    }

    public function write(array $report): string
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/".self::filePrefix($this->profile, $this->engine)."-{$this->opening}-{$this->seed}-".now()->format('Ymd_His').'.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        return $path;
    }

    public function printTable(array $report): void
    {
        fwrite(STDERR, sprintf(
            "\n[playtest] seed=%d profile=%s opening=%s engine=%s status=%s fail_reason=%s sols=%d phase2_start_sol=%s score=%d actions=%d/%d rejected=%d nexus_debt_final=%d\n",
            $report['seed'],
            $report['profile'],
            $report['opening'] ?? 'auto',
            $report['engine'] ?? 'legacy',
            $report['outcome']['status'],
            $report['outcome']['fail_reason'] ?? '-',
            $report['outcome']['sols'],
            $report['phase2_start_sol'] ?? '-',
            $report['outcome']['score'],
            $report['actions']['ok'],
            $report['actions']['attempted'],
            $report['actions']['rejected'],
            $report['outcome']['nexus_debt_final'],
        ));

        if (! empty($report['rejections'])) {
            fwrite(STDERR, '[playtest] rejections: '.json_encode($report['rejections'])."\n");
        }
    }
}
