<?php

namespace App\Console\Commands;

use App\Enums\BuildingId;
use App\Events\SolAdvanced;
use App\Models\Advisor;
use App\Models\Colony;
use App\Models\ColonyBuilding;
use App\Models\ColonyResearch;
use App\Models\ColonyResource;
use App\Models\Run;
use App\Models\UserResource;
use App\Services\BarService;
use App\Services\EncounterService;
use App\Services\EventService;
use App\Services\HarvesterEntitlementService;
use App\Services\MerchantService;
use App\Services\OnboardingTriggerService;
use App\Services\ProjectBonusService;
use App\Services\ResourcesService;
use App\Services\RunProgressService;
use App\Services\TickService;
use App\Services\TrustService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * GameTick — processes one game tick.
 *
 * Run manually:         php artisan game:tick
 * Run for a given run:  php artisan game:tick --run=1
 * Override tick number: php artisan game:tick --tick=16300
 *
 * Steps per tick (renumbered sequentially 2026-08-18 — was gapped 0→4→6 from
 * removed/merged steps, and missing several since added; step order below
 * matches handle()'s actual call order):
 *  1. Hangar deliveries   — transition building→docked ships; expire pending ships
 *  2. Hangar missions     — resolve dispatched missions (complete/abort), apply rewards
 *  3. Building decay      — decrement status_points (per-type decay_rate); level-down at ≤ 0
 *  4. Research decay      — decrement colony_researches.status_points; level-down at ≤ 0
 *  5. Supply cap          — SET user_resources.supply = CC_flat + housing_level × 8 (cap model)
 *  6. Resource generation — produce colony resources per industry building level (trust multiplier applied)
 *  7. Food consumption    — deduct Organics per colonist; trust penalty on shortfall
 *  8. Encounters          — roll GDD §9 hazards (storm/instability/plague) per colony, Phase-1 ramp applies
 *  9. Trust calculation   — recalculate colony trust and store in colony_resources (resource_id=12)
 * 10. Passive Credits     — nexus_subsidy (config('game.credits.nexus_subsidy')) + Uplink Station level ×
 *                           relay_bonus_per_uplink_level, added to user Credits
 * 11. Advisor upkeep      — deduct Credits per active advisor by rank (config('game.advisor.upkeep')); clamped to ≥ 0
 * 12. Advisor ticks       — increment active_ticks, check rank promotions
 * 13. Bar offers          — expire stale offers, generate new NPC/Corvan offers per colony with Bar
 * 14. Merchant spawn      — check each colony for a new Traveling Merchant visit
 * 15. Run structure       — phase transitions, objective progress, run-end checks (outside the DB transaction
 *                           above so endRun() can commit independently — see inline comment at the call site)
 * 15a. Nexus interventions — Phase-2-only: Sol-30/50/65/80 warnings/sanctions, nexus_debt fail
 */
class GameTick extends Command
{
    protected $signature = 'game:tick
                                {--run=  : Run ID to process (omit only when exactly one run is active)}
                                {--tick= : Override the tick number (default: from run or time-based)}';

    protected $description = 'Process one game tick (decay, supply, resources, trust)';

    public function __construct(
        private readonly TickService $tickService,
        private readonly EventService $eventService,
        private readonly TrustService $trustService,
        private readonly ResourcesService $resourcesService,
        private readonly OnboardingTriggerService $onboardingTriggerService,
        private readonly ProjectBonusService $projectBonusService,
        private readonly BarService $barService,
        private readonly MerchantService $merchantService,
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Resolve the Run record: explicit --run= ID, or the sole active run.
        //
        // The fallback deliberately refuses to guess: it is not user-scoped, so with
        // several active runs (game.run.allow_multiple) it would silently tick an
        // arbitrary player's run. Convenience only holds while the choice is unambiguous.
        $runId = $this->option('run');

        if (! $runId) {
            $activeRuns = Run::where('status', 'active')->get();

            if ($activeRuns->count() > 1) {
                $ids = $activeRuns->pluck('id')->implode(', ');
                $this->error("Several active runs exist ({$ids}). Pass --run=ID to pick one.");

                return self::FAILURE;
            }

            $run = $activeRuns->first();
        } else {
            $run = Run::find((int) $runId);
        }

        if (! $run) {
            $this->error($runId
                ? "Run #{$runId} not found."
                : 'No active run found. Start a run before processing a tick.'
            );

            return self::FAILURE;
        }

        // Ensure started_at is set on the very first tick of a run.
        if ($run->started_at === null) {
            $run->started_at = now();
            $run->save();
        }

        // Tick number: explicit --tick override wins; otherwise use the run's current_tick.
        if ($override = $this->option('tick')) {
            $this->tickService->setTickCount((int) $override);
        } else {
            $this->tickService->setTickCount($run->current_tick);
        }

        $tick = $this->tickService->getTickCount();
        $this->info("Processing tick {$tick} (Run #{$run->id})…");

        DB::transaction(function () use ($tick, $run) {
            [$delivered, $expired] = $this->processHangarDeliveries($tick);
            $this->line("  Hangar ships delivered:   {$delivered}");
            $this->line("  Hangar ships expired:     {$expired}");

            [$completed, $aborted] = $this->processHangarMissions($tick, (int) ($run->rng_seed ?? 0));
            $this->line("  Hangar missions resolved: {$completed} completed, {$aborted} aborted");

            $n = $this->processBuildingDecay($tick);
            $this->line("  Buildings levelled down:  {$n}");

            $n = $this->processResearchDecay($tick);
            $this->line("  Researches levelled down: {$n}");

            $n = $this->calculateSupply();
            $this->line("  Users supply updated:     {$n}");

            $n = $this->generateResources($tick);
            $this->line("  Colonies with resources:  {$n}");

            $n = $this->processFoodConsumption($tick);
            $this->line("  Colonies fed:             {$n}");

            $n = $this->processEncounters($tick, (int) ($run->rng_seed ?? 0), (int) $run->phase);
            $this->line("  Encounters processed:     {$n}");

            $n = $this->calculateTrust($tick);
            $this->line("  Colonies trust updated:   {$n}");

            $n = $this->generatePassiveCredits($tick);
            $this->line("  Users passive credits:    {$n}");

            $n = $this->deductAdvisorUpkeep($tick);
            $this->line("  Advisor upkeep deducted:  {$n}");

            $n = $this->incrementAdvisorTicks();
            $this->line("  Advisors ticked:          {$n}");

            $n = $this->processBarOffers($tick);
            $this->line("  Bar offers generated:     {$n}");

            $n = $this->processMerchantSpawn($tick);
            $this->line("  Merchant visits spawned:  {$n}");
        });

        // Step 15 — Run structure: phase transitions, objective progress, run-end checks.
        // Runs outside the main DB::transaction so that endRun() can commit independently
        // and return early without rolling back the tick's resource/decay work.
        $run->refresh();
        $runProgressService = $this->laravel->make(RunProgressService::class);

        if ($run->phase === 1) {
            if ($runProgressService->checkPhase1Completion($run)) {
                $runProgressService->transitionToPhase2($run);
                $run->refresh();
                $this->line('  Phase 1 completed — transitioning to Phase 2.');
            } else {
                $runProgressService->checkPhase1DeadlineWarnings($run);
            }
        }

        if ($run->phase === 2) {
            $runProgressService->updateObjectiveProgress($run);

            // Step 15a — Nexus interventions (warnings, sanctions, nexus_debt fail).
            $runProgressService->checkNexusInterventions($run);
            $run->refresh();

            // Early-exit if Nexus ended the run (nexus_debt fail path in checkNexusInterventions).
            if ($run->status !== 'active') {
                $this->warn("  Run #{$run->id} ended by Nexus intervention: {$run->fail_reason}");
                $this->info("Tick {$tick} done.");

                return self::SUCCESS;
            }

            // Win condition: at least 2 of 3 objectives completed.
            $completedCount = $run->objectives()->whereNotNull('completed_at')->count();
            if ($completedCount >= 2) {
                $runProgressService->endRun($run, 'completed');
                $this->info("  Run #{$run->id} completed! Score: {$run->score}");
                $this->info("Tick {$tick} done.");

                return self::SUCCESS;
            }
        }

        $failReason = $runProgressService->checkFailStates($run);
        if ($failReason) {
            $runProgressService->endRun($run, 'failed', $failReason);
            $this->warn("  Run #{$run->id} failed: {$failReason}");
            $this->info("Tick {$tick} done.");

            return self::SUCCESS;
        }

        event(new SolAdvanced($run, $tick));

        $this->info("Tick {$tick} done.");

        return self::SUCCESS;
    }

    // ── 0. Hangar deliveries ────────────────────────────────────────────────

    /**
     * Processes Nexus ship deliveries and pending-ship expiry.
     *
     * Delivery:  building → docked  when deliver_at_tick <= current tick.
     * Expiry:    pending ships whose pending_until_tick < current tick are deleted.
     *
     * @return array{int, int} [$deliveredCount, $expiredCount]
     */
    private function processHangarDeliveries(int $tick): array
    {
        // 1. Deliver ships: building → docked (deliver_at_tick reached).
        $delivered = DB::table('colony_ships')
            ->where('ship_state', 'building')
            ->whereNotNull('deliver_at_tick')
            ->where('deliver_at_tick', '<=', $tick)
            ->update(['ship_state' => 'docked', 'deliver_at_tick' => null]);

        // 2. Decay pending ships (no hangar assigned, deadline expired).
        $expired = DB::table('colony_ships')
            ->where('ship_state', 'pending')
            ->whereNotNull('pending_until_tick')
            ->where('pending_until_tick', '<', $tick)
            ->delete();

        return [$delivered, $expired];
    }

    // ── 0b. Hangar missions: wear + resolution (GDD §7/§8b) ─────────────────

    /**
     * Applies ship wear and resolves catalog missions for every dispatched ship.
     *
     * Per tick and dispatched ship: subtract wear_per_sol (config/ships.php).
     * SP ≤ 0 → mission aborted (no reward, ship limps home at 0 SP) — abort has
     * precedence over completion. Otherwise, once tick reaches
     * dispatch_tick + 2 × sol_distance the mission completes and rewards are paid.
     *
     * @return array{int, int} [$completedCount, $abortedCount]
     */
    private function processHangarMissions(int $tick, int $rngSeed): array
    {
        $shipsConfig = config('ships');
        $shipKeyById = [];
        foreach ($shipsConfig as $key => $cfg) {
            $shipKeyById[(int) $cfg['id']] = $key;
        }

        $missions = DB::table('colony_hangar_missions as m')
            ->join('colony_ships as cs', function ($join) {
                $join->on('cs.colony_id', '=', 'm.colony_id')
                    ->on('cs.hangar_instance_id', '=', 'm.instance_id');
            })
            ->where('m.state', 'active')
            ->where('cs.ship_state', 'dispatched')
            ->get([
                'm.id as mission_id', 'm.colony_id', 'm.instance_id', 'm.destination',
                'm.sol_distance', 'm.target', 'm.dispatch_tick',
                'cs.id as colony_ship_id', 'cs.ship_id', 'cs.status_points',
            ]);

        $completed = 0;
        $aborted = 0;

        foreach ($missions as $mission) {
            $shipKey = $shipKeyById[(int) $mission->ship_id] ?? null;
            $wear = (float) ($shipsConfig[$shipKey]['wear_per_sol'] ?? 1.0);
            $newSp = (float) $mission->status_points - $wear;

            $colony = Colony::find($mission->colony_id);
            $userId = $colony?->user_id;

            if ($newSp <= 0) {
                // Ship worn out mid-mission: abort, no reward, limp home at 0 SP.
                DB::table('colony_ships')->where('id', $mission->colony_ship_id)
                    ->update(['ship_state' => 'docked', 'status_points' => 0]);
                DB::table('colony_hangar_missions')->where('id', $mission->mission_id)
                    ->update(['state' => 'aborted', 'recall_tick' => $tick]);

                if ($userId !== null) {
                    $this->eventService->createEvent([
                        'user' => $userId,
                        'tick' => $tick,
                        'event' => 'hangar.mission_aborted',
                        'area' => 'colony',
                        'parameters' => json_encode([
                            'mission_key' => $mission->destination,
                            'ship_id' => (int) $mission->ship_id,
                            'colony_id' => (int) $mission->colony_id,
                        ]),
                    ]);
                }

                $aborted++;

                continue;
            }

            DB::table('colony_ships')->where('id', $mission->colony_ship_id)
                ->update(['status_points' => $newSp]);

            $returnTick = (int) $mission->dispatch_tick + 2 * (int) $mission->sol_distance;
            if ($tick < $returnTick) {
                continue; // still under way
            }

            // Mission complete: pay rewards, dock the ship.
            $catalogEntry = config("missions.catalog.{$mission->destination}");
            $rewardDetails = [];
            if ($catalogEntry !== null) {
                $rewardDetails = $this->payMissionRewards(
                    (int) $mission->colony_id,
                    $userId,
                    $catalogEntry['reward'],
                    $mission->target !== null ? json_decode($mission->target, true) : null,
                    $rngSeed + (int) $mission->mission_id,
                    $tick
                );
            }

            DB::table('colony_ships')->where('id', $mission->colony_ship_id)
                ->update(['ship_state' => 'docked']);
            DB::table('colony_hangar_missions')->where('id', $mission->mission_id)
                ->update(['state' => 'completed']);

            if ($userId !== null) {
                $this->eventService->createEvent([
                    'user' => $userId,
                    'tick' => $tick,
                    'event' => 'hangar.mission_completed',
                    'area' => 'colony',
                    'parameters' => json_encode([
                        'mission_key' => $mission->destination,
                        'ship_id' => (int) $mission->ship_id,
                        'colony_id' => (int) $mission->colony_id,
                        'rewards' => $rewardDetails,
                    ]),
                ]);
            }

            $completed++;
        }

        return [$completed, $aborted];
    }

    /**
     * Pays out a catalog mission reward and returns the concrete amounts for logging.
     *
     * Range values [min,max] and loot_table picks roll deterministically from the
     * run rng_seed + mission id (ADR 0003) — same run, same mission, same outcome.
     */
    private function payMissionRewards(
        int $colonyId,
        ?int $userId,
        array $reward,
        ?array $target,
        int $seed,
        int $tick
    ): array {
        // loot_table: seeded pick of one entry, then resolve that entry's rewards.
        if (isset($reward['loot_table'])) {
            $table = $reward['loot_table'];
            $reward = $table[$this->seededRoll($seed, 0, count($table) - 1)];
        }

        $resourceIds = ['regolith' => 3, 'compounds' => 4, 'organics' => 5];
        $details = [];

        foreach ($reward as $type => $value) {
            if (is_array($value) && count($value) === 2 && isset($value[0], $value[1])) {
                $value = $this->seededRoll($seed + crc32($type), (int) $value[0], (int) $value[1]);
            }

            if ($type === 'credits') {
                if ($userId !== null) {
                    DB::table('user_resources')->where('user_id', $userId)->increment('credits', (int) $value);
                }
            } elseif (isset($resourceIds[$type])) {
                DB::table('colony_resources')
                    ->where('colony_id', $colonyId)
                    ->where('resource_id', $resourceIds[$type])
                    ->update(['amount' => DB::raw('amount + '.(int) $value)]);
            } elseif ($type === 'trust_event') {
                $this->trustService->fireEvent($colonyId, (string) $value, $tick);
            } elseif ($type === 'reveal_tiles') {
                $value = $this->revealTiles($colonyId, (int) $value);
            } elseif ($type === 'deep_scan') {
                $this->deepScanTarget($colonyId, $target);
            } elseif ($type === 'research_ap') {
                $this->grantResearchAp($colonyId, $target, (int) $value);
            } elseif ($type === 'harvester_instance') {
                // Weg B (mission_harvester_salvage, GDD §4c "Harvester-Zweitinstanz:
                // Bezugsquelle", freigegeben 2026-08-05): grants the earned entitlement,
                // it does NOT place the building — the player still picks a Regolith
                // tile via ColonyController::placeBuilding, which then arrives damaged
                // (HarvesterEntitlementService::isSalvageSourced).
                if ($userId !== null) {
                    $this->harvesterEntitlementService->grantSalvage($userId);
                }
            }

            $details[$type] = $value;
        }

        return $details;
    }

    /**
     * Reveals up to $count unexplored exploration-zone tiles (deterministic order).
     * Returns the number actually revealed. Bypasses the AP-gated exploreTile().
     */
    private function revealTiles(int $colonyId, int $count): int
    {
        $tiles = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('is_explored', 0)
            ->where('is_colony_zone', 0)
            ->orderBy('ring')->orderBy('q')->orderBy('r')
            ->limit($count)
            ->get(['q', 'r']);

        foreach ($tiles as $tile) {
            DB::table('colony_tiles')
                ->where('colony_id', $colonyId)
                ->where('q', $tile->q)->where('r', $tile->r)
                ->update(['is_explored' => 1]);
        }

        return $tiles->count();
    }

    /**
     * Deep-scans the mission's target tile (player-picked at dispatch).
     */
    private function deepScanTarget(int $colonyId, ?array $target): void
    {
        if (! isset($target['q'], $target['r'])) {
            return;
        }

        DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('q', (int) $target['q'])->where('r', (int) $target['r'])
            ->update(['is_deep_scanned' => 1]);
    }

    /**
     * Grants research-AP progress on the player-chosen knowledge, capped at the
     * next levelup threshold (no auto-levelup) — bypasses the AP-gated invest().
     */
    private function grantResearchAp(int $colonyId, ?array $target, int $points): void
    {
        $researchId = (int) ($target['research_id'] ?? 0);
        if ($researchId === 0) {
            return;
        }

        $row = DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $researchId)
            ->first(['level', 'ap_spend']);

        $currentLevel = (int) ($row->level ?? 0);
        $costs = collect(config('knowledge'))->firstWhere('id', $researchId)['levelup_costs'] ?? [];
        $rawThreshold = (int) ($costs[$currentLevel + 1] ?? PHP_INT_MAX);
        $threshold = $rawThreshold === PHP_INT_MAX
            ? PHP_INT_MAX
            : $this->projectBonusService->effectiveKnowledgeApForLevelup($colonyId, $rawThreshold);
        $newSpend = min(((int) ($row->ap_spend ?? 0)) + $points, $threshold);

        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $colonyId, 'research_id' => $researchId],
            ['ap_spend' => $newSpend, 'level' => $currentLevel]
        );
    }

    /**
     * Deterministic integer roll in [min, max] — LCG hash, same pattern as BarService.
     */
    private function seededRoll(int $seed, int $min, int $max): int
    {
        if ($max <= $min) {
            return $min;
        }
        $hash = abs(($seed * 1664525 + 1013904223) & 0x7FFFFFFF);

        return $min + ($hash % ($max - $min + 1));
    }

    // ── 4. Building decay ────────────────────────────────────────────────────

    private function processBuildingDecay(int $tick): int
    {
        $fallbackRate = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates = DB::table('buildings')->pluck('decay_rate', 'id');
        $maxSPMap = DB::table('buildings')->pluck('max_status_points', 'id');
        $buildingNames = DB::table('buildings')->pluck('name', 'id');
        $levelled = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(buildings).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        // Sicherheits-Hub recycling: colonies that have securityHub built get a
        // fraction of build costs back on any building level-down (recycle_pct
        // itself is read inside applyLevelDown()).
        $secHubId = (int) config('buildings.securityHub.id', 53);
        $secHubColonies = DB::table('colony_buildings')
            ->where('building_id', $secHubId)
            ->where('level', '>', 0)
            ->pluck('colony_id')
            ->flip()
            ->all();

        // Build cost map for recycling: building_id → [resource_id => amount]
        // Only tradeable colony resources (3=regolith, 4=compounds, 5=organics).
        $tradeableIds = [3, 4, 5];
        $buildCostMap = DB::table('building_costs')
            ->whereIn('resource_id', $tradeableIds)
            ->get()
            ->groupBy('building_id')
            ->map(fn ($rows) => $rows->pluck('amount', 'resource_id')->all())
            ->all();

        $buildings = ColonyBuilding::where('level', '>', 0)->get();

        foreach ($buildings as $cb) {
            $rate = (float) ($decayRates[$cb->building_id] ?? $fallbackRate);
            $overCapMult = in_array($cb->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus = (float) $cb->status_points - ($rate * $overCapMult);
            $where = [
                'colony_id' => $cb->colony_id,
                'building_id' => $cb->building_id,
                'instance_id' => $cb->instance_id,
            ];

            if ($newStatus <= 0) {
                $this->applyLevelDown($cb, $tick, $maxSPMap->all(), $buildingNames->all(), $secHubColonies, $buildCostMap);
                $levelled++;
            } else {
                DB::table('colony_buildings')->where($where)
                    ->update(['status_points' => $newStatus]);

                // Trigger 1 — onboarding_decay: fires once when a building first
                // drops below 80 % of its max_status_points.
                $maxSP = (int) ($maxSPMap[$cb->building_id] ?? 20);
                if ($newStatus < ($maxSP * 0.8)) {
                    $colony = Colony::find($cb->colony_id);
                    $userId = $colony === null ? null : $colony->user_id;
                    if ($userId !== null && ! $this->onboardingTriggerService->hasFired($userId, 'onboarding_decay')) {
                        $this->eventService->createEvent([
                            'user' => $userId,
                            'tick' => $tick,
                            'event' => 'onboarding_decay',
                            'area' => 'techtree',
                            'parameters' => json_encode(['colony_id' => $cb->colony_id, 'tech_id' => $cb->building_id]),
                        ]);
                        $this->onboardingTriggerService->markFired($userId, 'onboarding_decay');
                    }
                }
            }
        }

        return $levelled;
    }

    /**
     * Levels a building down by 1 (min 0), restores its status_points to max, logs
     * a techtree.level_down event, and applies securityHub build-cost recycling if
     * active. Shared by processBuildingDecay() (SP hits 0 from ordinary decay) and
     * processEncounters() (SP hits 0 from a Kritisch-tier danger, GDD §9).
     */
    private function applyLevelDown(
        object $cb,
        int $tick,
        array $maxSPMap,
        array $buildingNames,
        array $secHubColonies,
        array $buildCostMap
    ): void {
        $maxSP = (int) ($maxSPMap[$cb->building_id] ?? 20);
        $newLevel = max(0, $cb->level - 1);
        $where = [
            'colony_id' => $cb->colony_id,
            'building_id' => $cb->building_id,
            'instance_id' => $cb->instance_id,
        ];

        DB::table('colony_buildings')->where($where)->update([
            'level' => $newLevel,
            'status_points' => $maxSP,
        ]);

        $colony = Colony::find($cb->colony_id);
        $this->eventService->createEvent([
            'user' => $colony === null ? 0 : ($colony->user_id ?? 0),
            'tick' => $tick,
            'event' => 'techtree.level_down',
            'area' => 'techtree',
            'parameters' => json_encode([
                'entity_type' => 'building',
                'entity_name' => $buildingNames[$cb->building_id] ?? '',
                'new_level' => $newLevel,
                'tech_id' => $cb->building_id,
                'colony_id' => $cb->colony_id,
            ]),
        ]);

        if (isset($secHubColonies[$cb->colony_id]) && isset($buildCostMap[$cb->building_id])) {
            $recyclePct = (float) config('buildings.securityHub.recycle_pct', 0.10);
            foreach ($buildCostMap[$cb->building_id] as $resId => $baseAmount) {
                $returned = (int) max(1, floor($baseAmount * $recyclePct));
                DB::table('colony_resources')->updateOrInsert(
                    ['colony_id' => $cb->colony_id, 'resource_id' => $resId],
                    ['amount' => DB::raw("amount + {$returned}")]
                );
            }
        }
    }

    // ── 6. Research decay ────────────────────────────────────────────────────

    private function processResearchDecay(int $tick): int
    {
        $fallbackRate = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates = DB::table('researches')->pluck('decay_rate', 'id');
        $maxSPMap = DB::table('researches')->pluck('max_status_points', 'id');
        $researchNames = DB::table('researches')->pluck('name', 'id');
        $levelled = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(researches).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        $knowledgeIds = collect(config('knowledge'))->pluck('id')->toArray();

        // Kenntnisse (purpose='knowledge') never decay — GDD §10.
        $researches = ColonyResearch::where('level', '>', 0)
            ->whereNotIn('research_id', $knowledgeIds)
            ->get();

        foreach ($researches as $cr) {
            $rate = (float) ($decayRates[$cr->research_id] ?? $fallbackRate);
            $overCapMult = in_array($cr->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus = (float) $cr->status_points - ($rate * $overCapMult);
            $where = ['colony_id' => $cr->colony_id, 'research_id' => $cr->research_id];

            if ($newStatus <= 0) {
                $maxSP = (int) ($maxSPMap[$cr->research_id] ?? 20);
                $newLevel = max(0, $cr->level - 1);

                DB::table('colony_researches')->where($where)->update([
                    'level' => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cr->colony_id);
                $this->eventService->createEvent([
                    'user' => $colony === null ? 0 : ($colony->user_id ?? 0),
                    'tick' => $tick,
                    'event' => 'techtree.level_down',
                    'area' => 'techtree',
                    'parameters' => json_encode([
                        'entity_type' => 'research',
                        'entity_name' => $researchNames[$cr->research_id] ?? '',
                        'new_level' => $newLevel,
                        'tech_id' => $cr->research_id,
                    ]),
                ]);
                $levelled++;
            } else {
                DB::table('colony_researches')->where($where)
                    ->update(['status_points' => $newStatus]);
            }
        }

        return $levelled;
    }

    // ── 7. Supply cap ────────────────────────────────────────────────────────

    /**
     * Recalculates and sets the supply cap for each user.
     *
     * Cap model (GDD §6):
     *   cap = CC_flat (10) + housing_level × 8 + Σ(knowledge_cap_per_level),  max 200
     *
     * CommandCenter must be level > 0. Without CC → cap = 0.
     * The result is SET (not incremented) in user_resources.supply.
     */
    private function calculateSupply(): int
    {
        $capCC = (int) config('buildings.commandCenter.supply_cap', 10);
        $capHousing = (int) config('buildings.housingComplex.supply_cap', 8);
        $capMax = (int) config('game.supply.cap_max', 200);
        $capPerLevel = config('game.supply.knowledge_cap_per_level', []);
        $knowledgeIds = collect(config('knowledge'))->pluck('id')->toArray();

        $userIds = Colony::whereNotNull('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $colony = Colony::where('user_id', $userId)->first();
            if (! $colony) {
                continue;
            }

            $ccLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::CommandCenter->value)
                ->value('level');

            if ($ccLevel <= 0) {
                UserResource::where('user_id', $userId)->update(['supply' => 0]);

                continue;
            }

            $housingLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::Housing->value)
                ->sum('level');

            $knowledgeCap = 0;
            if (! empty($knowledgeIds)) {
                $levels = DB::table('colony_researches')
                    ->where('colony_id', $colony->id)
                    ->whereIn('research_id', $knowledgeIds)
                    ->pluck('level', 'research_id');

                foreach ($levels as $level) {
                    for ($i = 1; $i <= min((int) $level, 5); $i++) {
                        $knowledgeCap += $capPerLevel[$i] ?? 0;
                    }
                }
            }

            $cap = min($capCC + ($housingLevel * $capHousing) + $knowledgeCap, $capMax);

            UserResource::where('user_id', $userId)->update(['supply' => $cap]);

            // Trigger 2 — supply_cap_full: fires once when used supply >= cap.
            if (! $this->onboardingTriggerService->hasFired($userId, 'supply_cap_full')) {
                $usedSupply = (int) DB::table('colony_buildings as cb')
                    ->join('buildings as b', 'b.id', '=', 'cb.building_id')
                    ->where('cb.colony_id', $colony->id)
                    ->where('cb.level', '>', 0)
                    ->sum(DB::raw('cb.level * COALESCE(b.supply_cost, 0)'));

                if ($usedSupply >= $cap) {
                    $this->onboardingTriggerService->markFired($userId, 'supply_cap_full');
                }
            }
        }

        return $userIds->count();
    }

    // ── 8. Resource generation ───────────────────────────────────────────────

    /**
     * Sums the bell-curve yield for a building at a given level: curve[1] + curve[2] + …
     * + curve[level], capped at the highest configured level (the building's max_level).
     *
     * @param  array<int,int>  $curve  level => marginal yield at that level
     */
    public static function cumulativeCurveYield(array $curve, int $level): int
    {
        $cappedLevel = min($level, max(array_keys($curve) ?: [0]));
        $total = 0;
        for ($i = 1; $i <= $cappedLevel; $i++) {
            $total += $curve[$i] ?? 0;
        }

        return $total;
    }

    /**
     * Harvester yield for a single tile (GDD §4c "Erschöpfungskurve und Umzugstakt",
     * freigegeben 2026-08-03) — pure, no DB access.
     *
     *   Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
     *
     * Never drops below half of fresh_yield while resources remain; 0 once exhausted
     * ($remaining <= 0). $geologyLevel adds the geology Kenntnis bonus (GDD §13.7,
     * config('game.geology_harvester_bonus_per_level')) on top — callers that apply
     * the bonus only once per colony (not per harvester instance) must pass 0 here
     * for every instance but the one carrying the bonus (see generateHarvesterYield()).
     */
    public static function harvesterYield(string $tileType, int $remaining, int $resourceMax, int $geologyLevel): int
    {
        if ($resourceMax <= 0 || $remaining <= 0) {
            return 0;
        }

        $fresh = (int) (config('game.harvester.fresh_yield', [])[$tileType] ?? 0);
        if ($fresh <= 0) {
            return 0;
        }

        $ratio = min(1.0, $remaining / $resourceMax);
        $base = $fresh * (0.5 + 0.5 * $ratio);
        $geologyBonus = self::cumulativeCurveYield(config('game.geology_harvester_bonus_per_level', []), $geologyLevel);

        return (int) round($base) + $geologyBonus;
    }

    private function generateResources(int $tick): int
    {
        $productionConfig = config('game.production_curve', []);
        if (empty($productionConfig)) {
            $this->warn('  No production rates configured in config/game.php → skipping.');

            return 0;
        }

        // Buildings whose transit ended before this tick have arrived.
        DB::table('colony_buildings')
            ->whereNotNull('pending_until_tick')
            ->where('pending_until_tick', '<', $tick)
            ->update(['pending_until_tick' => null]);

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Apply trust production multiplier based on the colony's CURRENT trust
            // (stored from the previous tick's trust calculation — no circular dependency).
            $trust = $this->trustService->getTrust($colony->id);
            $multiplier = $this->trustService->getProductionMultiplier($trust);

            $harvesterYield = $this->generateHarvesterYield($tick, $colony, $multiplier);
            if ($harvesterYield > 0) {
                ColonyResource::where('colony_id', $colony->id)
                    ->where('resource_id', 3) // Regolith
                    ->update(['amount' => DB::raw("amount + {$harvesterYield}")]);
            }

            $agronomyBonus = $this->generateAgronomyBonus($colony, $multiplier);
            if ($agronomyBonus > 0) {
                ColonyResource::where('colony_id', $colony->id)
                    ->where('resource_id', 5) // Organika
                    ->update(['amount' => DB::raw("amount + {$agronomyBonus}")]);
            }

            foreach ($productionConfig as $buildingId => $outputs) {
                // Harvester (27) is handled above via the depletion mechanic (GDD §4c) —
                // production_curve[27] stays inert historical data (GDD §13.7).
                if ((int) $buildingId === BuildingId::Harvester->value) {
                    continue;
                }

                $building = DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $buildingId)
                    ->first();

                if (! $building || $building->level <= 0) {
                    continue;
                }

                // In transit (harvester relocation): no production this Sol.
                if ($building->pending_until_tick !== null && (int) $building->pending_until_tick >= $tick) {
                    continue;
                }

                foreach ($outputs as $resourceId => $curve) {
                    $base = self::cumulativeCurveYield($curve, (int) $building->level);
                    $yield = (int) round($base * $multiplier);
                    ColonyResource::where('colony_id', $colony->id)
                        ->where('resource_id', $resourceId)
                        ->update(['amount' => DB::raw("amount + {$yield}")]);
                }
            }
        }

        return $colonies->count();
    }

    /**
     * Sums Regolith yield across every Harvester instance of a colony (GDD §4c),
     * deducting the credited (trust-adjusted) amount from each tile's resource_amount
     * and clamping stale resource_max values down to the current config (legacy tiles
     * seeded before the 2026-08-03 500/300/160 reduction — no retroactive migration).
     *
     * The geology Kenntnis bonus (§13.7) is applied ONCE per colony — not per Harvester
     * instance — on whichever active instance is evaluated first, matching "kumuliert
     * max 12" as a cap on the total effect rather than a per-rig multiplier. The bonus
     * is deducted from that instance's tile like any other credited yield.
     */
    private function generateHarvesterYield(int $tick, Colony $colony, float $multiplier): int
    {
        $resourceMaxCfg = config('game.harvester.resource_max', []);
        $geologyId = (int) config('knowledge.geology.id', 92);

        $instances = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('level', '>', 0)
            ->get();

        if ($instances->isEmpty()) {
            return 0;
        }

        $geologyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)
            ->where('research_id', $geologyId)
            ->value('level');
        $geologyApplied = false;

        $totalCredited = 0;

        foreach ($instances as $instance) {
            if ($instance->tile_x === null || $instance->tile_y === null) {
                continue; // not placed yet
            }

            if ($instance->pending_until_tick !== null && (int) $instance->pending_until_tick >= $tick) {
                continue; // in transit (relocating) — no production this Sol
            }

            if (($instance->instability_outage_until_tick ?? null) !== null && (int) $instance->instability_outage_until_tick >= $tick) {
                continue; // Geologische Instabilität outage — no production this Sol (§9, distinct from relocation transit)
            }

            $tile = DB::table('colony_tiles')
                ->where('colony_id', $colony->id)
                ->where('q', $instance->tile_x)
                ->where('r', $instance->tile_y)
                ->first();

            if (! $tile) {
                continue;
            }

            $configMax = (int) ($resourceMaxCfg[$tile->tile_type] ?? 0);
            if ($configMax <= 0) {
                continue; // not a regolith tile — nothing to deplete/produce
            }

            $remaining = min((int) ($tile->resource_amount ?? $configMax), $configMax);

            $geologyForThisTile = $geologyApplied ? 0 : $geologyLevel;
            $base = self::harvesterYield($tile->tile_type, $remaining, $configMax, $geologyForThisTile);

            if ($base <= 0) {
                // Still clamp the stale resource_max down for consistency, even
                // when exhausted or misconfigured.
                DB::table('colony_tiles')
                    ->where('colony_id', $colony->id)
                    ->where('q', $instance->tile_x)
                    ->where('r', $instance->tile_y)
                    ->update(['resource_max' => $configMax, 'resource_amount' => $remaining]);

                continue;
            }

            if (! $geologyApplied) {
                $geologyApplied = true;
            }

            $credited = (int) round($base * $multiplier);
            $totalCredited += $credited;

            $newRemaining = max(0, $remaining - $credited);
            DB::table('colony_tiles')
                ->where('colony_id', $colony->id)
                ->where('q', $instance->tile_x)
                ->where('r', $instance->tile_y)
                ->update(['resource_max' => $configMax, 'resource_amount' => $newRemaining]);
        }

        return $totalCredited;
    }

    /**
     * agronomy Kenntnis bonus on bioFacility Organika output (GDD §13.5 parity
     * requirement, docs/superpowers/specs/2026-08-15-knowledge-effects-and-encounters-
     * design.md §3) — mirrors generateHarvesterYield()'s geology bonus pattern, but
     * bioFacility has no per-tile depletion, so this is a flat colony-level add-on.
     */
    private function generateAgronomyBonus(Colony $colony, float $multiplier): int
    {
        $bioFacilityLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 41)
            ->value('level');

        if ($bioFacilityLevel <= 0) {
            return 0;
        }

        $agronomyId = (int) config('knowledge.agronomy.id', 93);
        $agronomyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)
            ->where('research_id', $agronomyId)
            ->value('level');

        $bonus = self::cumulativeCurveYield(config('game.agronomy_agrardom_bonus_per_level', []), $agronomyLevel);

        return (int) round($bonus * $multiplier);
    }

    // ── 8a. Food consumption (Organika provisioning) ──────────────────────────

    /**
     * Each colony consumes Organika (resource 5) proportional to its used supply.
     *
     * food_need = floor(used_supply / supply_per_eater). Runs AFTER production (the
     * Sol's harvest is on hand) and BEFORE trust (the trust calc reads the resulting
     * hunger_streak via TrustService::hungerPenalty). Stock covers need → well_fed
     * event (+trust), streak reset. Stock short → consume what's left, streak grows
     * (escalating penalty). bioFacility is thereby a must-have.
     */
    private function processFoodConsumption(int $tick): int
    {
        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            $foodNeed = $this->resourcesService->foodNeed($colony->id);

            if ($foodNeed < 1) {
                // Tiny early colony — nobody to feed. Clear any streak, no bonus.
                DB::table('glx_colonies')->where('id', $colony->id)->update(['hunger_streak' => 0]);

                continue;
            }

            $stock = (int) DB::table('colony_resources')
                ->where('colony_id', $colony->id)->where('resource_id', 5)->value('amount');

            $consumed = min($stock, $foodNeed);
            if ($consumed > 0) {
                DB::table('colony_resources')
                    ->where('colony_id', $colony->id)->where('resource_id', 5)
                    ->update(['amount' => DB::raw("amount - {$consumed}")]);
            }

            if ($stock >= $foodNeed) {
                // Fed: reset streak, reward with a one-shot well_fed trust event.
                DB::table('glx_colonies')->where('id', $colony->id)->update(['hunger_streak' => 0]);
                $this->trustService->fireEvent($colony->id, 'well_fed', $tick);
            } else {
                // Short: escalate the hunger streak (drives TrustService::hungerPenalty).
                DB::table('glx_colonies')->where('id', $colony->id)
                    ->update(['hunger_streak' => DB::raw('hunger_streak + 1')]);
            }
        }

        return $colonies->count();
    }

    // ── 8a. Encounters (GDD §9 "Begegnungen & Gefahren") ────────────────────────

    /**
     * GDD §9 "Begegnungen & Gefahren" — per-Sol encounter pipeline. Two phases per
     * call: (1) resolve any encounter WARNED at tick-1 for each colony (reads
     * colony_log for area='encounter', event='...instability_warning'|'storm_warning'
     * etc. at tick-1, resolves using CURRENT status_points), (2) roll new warnings
     * for tick, respecting the cooldown against each colony's last resolved encounter.
     * Danger-type-specific roll/resolve logic (rollStorm, rollInstability,
     * rollPlague) is dispatched from here. Note: instability has no warning phase
     * — its trigger event doubles as its resolution (see rollInstability()'s
     * docblock).
     *
     * Per colony, the three NEW-roll phases (storm/instability/plague) are mutually
     * exclusive within a single tick: as soon as one of them triggers, the
     * remaining roll phases are skipped for that colony this tick. The cooldown
     * (encounterOnCooldown()) only ever sees RESOLVED encounters from previous
     * Sols — Sturm's warning phase writes no resolution row on the warning tick —
     * so without this short-circuit all three could independently succeed on the
     * same Sol, defeating the cooldown's anti-spiral intent. Warning-resolution
     * (resolveStormWarning()) is not subject to this — it always runs.
     *
     * @return int number of encounters resolved this call (warnings + resolutions)
     */
    private function processEncounters(int $tick, int $rngSeed, int $phase): int
    {
        $processed = 0;
        $encounterService = new EncounterService;
        $cooldownSols = (int) config('game.encounter.cooldown_sols', 3);
        $securityHubColonies = DB::table('colony_buildings')
            ->where('building_id', (int) config('buildings.securityHub.id', 53))
            ->where('level', '>', 0)
            ->pluck('colony_id')
            ->flip()
            ->all();

        // Phase 1: the colony has no infrastructure yet (no securityHub/geology/
        // defense mitigation is realistically obtainable — those hang off the
        // Analytik-Labor, a Phase-2 building) and the Sol-30 deadline leaves only
        // ~5-10 Sol of slack. GDD §9 places danger "early" but does not intend a
        // freshly-landed colony to be as exposed as a mature one — ramp trigger
        // chance from 0 up to full strength over the first N Sols instead of
        // applying it at full strength from Sol 1.
        $phase1RampSols = max(1, (int) config('game.encounter.phase1_ramp_sols', 15));
        $rampMultiplier = $phase === 1 ? min(1.0, $tick / $phase1RampSols) : 1.0;

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Phase 1: resolve yesterday's storm warning, if any (not subject to
            // the same-Sol mutual exclusion below — it's a resolution, not a roll).
            $processed += $this->resolveStormWarning($colony, $tick, $encounterService, $securityHubColonies);

            if ($this->encounterOnCooldown($colony->id, $tick, $cooldownSols)) {
                continue;
            }

            // Phase 2: roll each danger type in turn — the first one that
            // triggers wins the Sol for this colony, the rest are skipped.
            $rollPhases = [
                fn () => $this->rollStorm($colony, $tick, $rngSeed, $rampMultiplier),
                // Geologische Instabilität: tied to the Harvester tile, no warning/
                // resolution split (GDD §9 — production outage triggers immediately,
                // it's not a building-SP encounter, EncounterService's tiers don't apply).
                fn () => $this->rollInstability($colony, $tick, $rngSeed, $rampMultiplier),
                // Seuchenausbruch: emergent trigger only (GDD §9) — never rolls on a
                // healthy colony, unlike Sturm/Instabilität which always have a nonzero
                // base chance.
                fn () => $this->rollPlague($colony, $tick, $rngSeed, $rampMultiplier),
            ];

            foreach ($rollPhases as $rollPhase) {
                $rolled = $rollPhase();
                $processed += $rolled;
                if ($rolled > 0) {
                    break;
                }
            }
        }

        return $processed;
    }

    /**
     * Whether the given colony_log row's `parameters` JSON references $colonyId.
     * Never match colony_id via a raw LIKE '%"colony_id":N%' on the JSON string —
     * that substring-matches without a delimiter boundary, so colony_id=1 would
     * false-positive on colony_id=10, 11, 100, … Decoding is the only safe way.
     * Shared by all three encounter types (storm now; instability/plague later).
     */
    private function encounterLogMatchesColony(object $row, int $colonyId): bool
    {
        $params = json_decode($row->parameters, true);

        return is_array($params) && (int) ($params['colony_id'] ?? -1) === $colonyId;
    }

    /**
     * Whether this colony resolved ANY encounter within the last $cooldownSols Sols.
     * "Resolved" = any encounter.* colony_log event that is NOT a warning — storm's
     * three outcome-tier events, plus instability/plague's immediate trigger events
     * (which have no separate warning phase, so the trigger event IS the resolution).
     */
    private function encounterOnCooldown(int $colonyId, int $tick, int $cooldownSols): bool
    {
        if ($cooldownSols <= 0) {
            return false;
        }

        $rows = DB::table('colony_log')
            ->where('area', 'encounter')
            ->where('tick', '>=', $tick - $cooldownSols)
            ->where('event', 'not like', '%_warning')
            ->get();

        foreach ($rows as $row) {
            if ($this->encounterLogMatchesColony($row, $colonyId)) {
                return true;
            }
        }

        return false;
    }

    private function rollStorm(Colony $colony, int $tick, int $rngSeed, float $rampMultiplier = 1.0): int
    {
        $buildingCount = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('level', '>', 0)
            ->where('building_id', '!=', BuildingId::Harvester->value)
            ->count();

        if ($buildingCount === 0) {
            return 0;
        }

        $cfg = config('game.encounter.storm', []);
        $baseChance = (float) ($cfg['base_chance'] ?? 0.02);
        $perBuilding = (float) ($cfg['chance_per_building'] ?? 0.01);
        $cap = (float) ($cfg['chance_cap'] ?? 0.10);

        $defenseId = (int) config('knowledge.defense.id', 96);
        $defenseLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)->where('research_id', $defenseId)->value('level');
        $reductionPct = self::cumulativeCurveYield(config('game.defense_storm_risk_reduction_per_lv', []), $defenseLevel) / 100;

        $chance = min($cap, $baseChance + $buildingCount * $perBuilding) * (1 - $reductionPct) * $rampMultiplier;

        $seed = $rngSeed + $colony->id * 7919 + $tick * 104729;
        $roll = $this->seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        // Sturm is colony-wide (GDD §9, Owner-Entscheidung 2026-09-03): it hits
        // ALL eligible (level>0, non-Harvester) Colony-Zone buildings at once, so
        // the warning carries no single target — resolveStormWarning() picks the
        // affected set itself, using CURRENT status_points at resolution time.
        $this->fireEncounterOnboardingHint($colony, $tick);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.storm_warning',
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
            ]),
        ]);

        return 1;
    }

    /**
     * Resolves a colony-wide storm warning (GDD §9, Owner-Entscheidung
     * 2026-09-03): EVERY eligible (level>0, non-Harvester) Colony-Zone building
     * gets its OWN SP-based outcome tier from EncounterService::resolveOutcome()
     * (independent rolls, no shared tier) — but the colony's Trust reacts only
     * ONCE per storm, using the worst tier among the affected buildings
     * (kritisch > beschaedigt > abgewehrt), and a single aggregated
     * `encounter.storm_resolved` colony_log entry is written instead of one row
     * per building.
     */
    private function resolveStormWarning(Colony $colony, int $tick, EncounterService $service, array $securityHubColonies): int
    {
        $warning = DB::table('colony_log')
            ->where('area', 'encounter')->where('event', 'encounter.storm_warning')
            ->where('tick', $tick - 1)
            ->get()
            ->first(fn ($row) => $this->encounterLogMatchesColony($row, $colony->id));

        if (! $warning) {
            return 0;
        }

        $targets = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('level', '>', 0)
            ->where('building_id', '!=', BuildingId::Harvester->value)
            ->orderBy('building_id')->orderBy('instance_id')
            ->get();

        if ($targets->isEmpty()) {
            return 0;   // every eligible building was demolished/relocated between warning and resolution
        }

        $maxSPById = DB::table('buildings')->pluck('max_status_points', 'id');
        $hubActive = isset($securityHubColonies[$colony->id]);

        // Worst-tier precedence for the single colony-wide trust event.
        $tierRank = ['abgewehrt' => 0, 'beschaedigt' => 1, 'kritisch' => 2];
        $counts = ['abgewehrt' => 0, 'beschaedigt' => 0, 'kritisch' => 0];
        $worstTier = 'abgewehrt';
        $worstTrustEvent = 'encounter_won';

        $maxSPMap = null;
        $buildingNames = null;
        $buildCostMap = null;

        foreach ($targets as $cb) {
            $maxSP = (int) ($maxSPById[$cb->building_id] ?? 20) ?: 20;
            $outcome = $service->resolveOutcome((int) $cb->status_points, $maxSP, $hubActive);

            DB::table('colony_buildings')
                ->where('colony_id', $colony->id)->where('building_id', $cb->building_id)->where('instance_id', $cb->instance_id)
                ->update(['status_points' => $outcome['sp_after']]);

            $counts[$outcome['tier']]++;
            if ($tierRank[$outcome['tier']] > $tierRank[$worstTier]) {
                $worstTier = $outcome['tier'];
                $worstTrustEvent = $outcome['trust_event'];
            }

            if ($outcome['forces_level_down']) {
                if ($maxSPMap === null) {
                    $maxSPMap = DB::table('buildings')->pluck('max_status_points', 'id')->all();
                    $buildingNames = DB::table('buildings')->pluck('name', 'id')->all();
                    $buildCostMap = DB::table('building_costs')->whereIn('resource_id', [3, 4, 5])
                        ->get()->groupBy('building_id')->map(fn ($rows) => $rows->pluck('amount', 'resource_id')->all())->all();
                }
                $this->applyLevelDown($cb, $tick, $maxSPMap, $buildingNames, $securityHubColonies, $buildCostMap);
            }
        }

        $this->trustService->fireEvent($colony->id, $worstTrustEvent, $tick);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.storm_resolved',
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'counts' => $counts,
                'trust_event' => $worstTrustEvent,
            ]),
        ]);

        return 1;
    }

    /**
     * Geologische Instabilität (GDD §9): unlike Sturm/Seuche, this has no SP-based
     * outcome tier — it directly disrupts Harvester production for N Sols via its
     * own instability_outage_until_tick field (colony_buildings), kept separate
     * from pending_until_tick (relocation transit) so that a player can still
     * relocate the Harvester while an instability outage is active — GDD §9's
     * stated counter-play ("Relocation setzt Zähler zurück") requires relocation
     * to remain possible during the outage; ColonyController::placeBuilding()
     * clears instability_outage_until_tick on a successful move. No warning/
     * resolution split either: it triggers and resolves in the same call, since
     * there's nothing to "defend" against.
     */
    private function rollInstability(Colony $colony, int $tick, int $rngSeed, float $rampMultiplier = 1.0): int
    {
        $harvester = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', BuildingId::Harvester->value)
            ->where('level', '>', 0)
            ->orderBy('instance_id')
            ->first();

        if (! $harvester || $harvester->placed_at_tick === null) {
            return 0;
        }

        // Already relocating, or already in an active instability outage — skip.
        if ($harvester->pending_until_tick !== null && (int) $harvester->pending_until_tick >= $tick) {
            return 0;
        }
        if (($harvester->instability_outage_until_tick ?? null) !== null && (int) $harvester->instability_outage_until_tick >= $tick) {
            return 0;
        }

        $solsSinceRelocation = max(0, $tick - (int) $harvester->placed_at_tick);
        $cfg = config('game.encounter.instability', []);
        $chancePerSol = (float) ($cfg['chance_per_sol_since_relocation'] ?? 0.0015);
        $cap = (float) ($cfg['chance_cap'] ?? 0.05);

        $geologyId = (int) config('knowledge.geology.id', 92);
        $geologyLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)->where('research_id', $geologyId)->value('level');
        $reductionPct = self::cumulativeCurveYield(config('game.geology_instability_risk_reduction_per_lv', []), $geologyLevel) / 100;

        $chance = min($cap, $solsSinceRelocation * $chancePerSol) * (1 - $reductionPct) * $rampMultiplier;

        $seed = $rngSeed + $colony->id * 15485863 + $tick * 32452843;
        $roll = $this->seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        $outageSols = (int) ($cfg['outage_sols'] ?? 3);
        DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', BuildingId::Harvester->value)->where('instance_id', $harvester->instance_id)
            ->update(['instability_outage_until_tick' => $tick + $outageSols]);

        $this->fireEncounterOnboardingHint($colony, $tick);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.instability_triggered',
            'area' => 'encounter',
            'parameters' => json_encode([
                'colony_id' => $colony->id,
                'instance_id' => $harvester->instance_id,
                'outage_until_tick' => $tick + $outageSols,
            ]),
        ]);

        return 1;
    }

    /**
     * Seuchenausbruch (GDD §9): emergent, not ambient — only rolls when
     * hunger_streak≥3 OR Trust<-20 (a healthy colony has 0% base risk, hard-gated,
     * not just a very low chance). Resolves immediately (no warning/resolution
     * split, matching instability's shape, not storm's) as a colony_threatened Trust
     * hit plus a temporary AP-reduction debuff via glx_colonies.plague_until_tick.
     */
    private function rollPlague(Colony $colony, int $tick, int $rngSeed, float $rampMultiplier = 1.0): int
    {
        $hungerStreak = (int) DB::table('glx_colonies')->where('id', $colony->id)->value('hunger_streak');
        $trust = $this->trustService->getTrust($colony->id);

        if ($hungerStreak < 3 && $trust >= -20) {
            return 0;   // healthy colony — hard gate, not a chance roll
        }

        $alreadyActive = DB::table('glx_colonies')->where('id', $colony->id)->value('plague_until_tick');
        if ($alreadyActive !== null && (int) $alreadyActive >= $tick) {
            return 0;   // don't stack a second plague on top of an active one
        }

        $chance = (float) config('game.encounter.plague.chance_per_sol_when_emergent', 0.05);

        $infirmaryLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)->where('building_id', (int) config('buildings.infirmary.id', 46))->value('level');
        $perLevel = (float) config('buildings.infirmary.plague_risk_reduction_pct_per_level', 0.08);
        $cap = (float) config('buildings.infirmary.plague_risk_reduction_cap', 0.50);
        $healthId = (int) config('knowledge.health.id', 94);
        $healthLevel = (int) DB::table('colony_researches')
            ->where('colony_id', $colony->id)->where('research_id', $healthId)->value('level');
        $healthReductionPct = self::cumulativeCurveYield(config('game.health_plague_risk_reduction_per_lv', []), $healthLevel) / 100;

        $reductionPct = min($cap, $infirmaryLevel * $perLevel + $healthReductionPct);

        $chance *= (1 - $reductionPct) * $rampMultiplier;

        $seed = $rngSeed + $colony->id * 179424673 + $tick * 32416187;
        $roll = $this->seededRoll($seed, 0, 9999) / 10000;
        if ($roll >= $chance) {
            return 0;
        }

        $debuffSols = (int) config('game.encounter.plague.debuff_sols', 5);
        DB::table('glx_colonies')->where('id', $colony->id)->update(['plague_until_tick' => $tick + $debuffSols]);
        $this->trustService->fireEvent($colony->id, 'colony_threatened', $tick);

        $this->fireEncounterOnboardingHint($colony, $tick);

        $this->eventService->createEvent([
            'user' => $colony->user_id ?? 0,
            'tick' => $tick,
            'event' => 'encounter.plague_triggered',
            'area' => 'encounter',
            'parameters' => json_encode(['colony_id' => $colony->id, 'debuff_until_tick' => $tick + $debuffSols]),
        ]);

        return 1;
    }

    /**
     * Fires the `onboarding_encounter` hint-bar trigger once per user, forever
     * (not run-scoped — user_preferences.fired_triggers has no run dimension,
     * same as the existing onboarding_decay/onboarding_trust triggers) — the
     * first time any of the three danger types (Sturm/Instabilität/Seuche)
     * triggers for this colony's user. Shared by rollStorm()/rollInstability()/
     * rollPlague() to avoid tripling this logic.
     *
     * Event key is `onboarding_encounter` (no `colony.` prefix) so it matches
     * CommLogController::log()'s `event LIKE 'onboarding%'` exclusion filter —
     * with the prefix it used to leak into the Komm-Log as a raw, untranslated
     * key instead of only driving the hint bar.
     */
    private function fireEncounterOnboardingHint(Colony $colony, int $tick): void
    {
        $userId = $colony->user_id;
        if ($userId === null || $this->onboardingTriggerService->hasFired($userId, 'onboarding_encounter')) {
            return;
        }

        $this->onboardingTriggerService->markFired($userId, 'onboarding_encounter');
        $this->eventService->createEvent([
            'user' => $userId,
            'tick' => $tick,
            'event' => 'onboarding_encounter',
            'area' => 'colony',
            'parameters' => json_encode(['colony_id' => $colony->id]),
        ]);
    }

    // ── 8b. Trust calculation ─────────────────────────────────────────────────

    private function calculateTrust(int $tick): int
    {
        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Trigger 3 — onboarding_trust: fires once when trust crosses from
            // non-negative to negative for a real (non-NPC) colony.
            $userId = $colony->user_id ?? null;
            $trustBefore = null;
            if ($userId !== null && ! $this->onboardingTriggerService->hasFired($userId, 'onboarding_trust')) {
                $trustBefore = (int) (DB::table('colony_resources')
                    ->where('colony_id', $colony->id)
                    ->where('resource_id', 12)
                    ->value('amount') ?? 0);
            }

            $this->trustService->calculateAndStore($colony, $tick);

            if ($userId !== null && $trustBefore !== null && $trustBefore >= 0) {
                $trustAfter = (int) (DB::table('colony_resources')
                    ->where('colony_id', $colony->id)
                    ->where('resource_id', 12)
                    ->value('amount') ?? 0);

                if ($trustAfter < 0) {
                    $this->eventService->createEvent([
                        'user' => $userId,
                        'tick' => $tick,
                        'event' => 'onboarding_trust',
                        'area' => 'colony',
                        'parameters' => json_encode(['colony_id' => $colony->id]),
                    ]);
                    $this->onboardingTriggerService->markFired($userId, 'onboarding_trust');
                }
            }
        }

        return $colonies->count();
    }

    // ── 8c. Passive Credits ───────────────────────────────────────────────────

    /**
     * Awards passive Credits income to every player colony per tick.
     *
     * Formula (GDD §3):
     *   nexus    = game.credits.nexus_subsidy               (flat, if CC level > 0)
     *   relay    = uplinkStation.level × game.credits.relay_bonus_per_uplink_level
     *   contract = consul_contract_income_per_rank[konsulRank]  (Konsul assigned + Cantina built)
     *   total    = nexus + relay + contract
     *
     * "Relaisvergütung" is anchored on Uplink Station, not Housing — colonists'
     * living quarters have no thematic connection to Nexus relay/sensor capacity,
     * and Uplink Station already gates every other Nexus-facing mechanic
     * (deep-scan cost, direct import, merchant frequency). Uplink Station is a
     * single instance (is_instanced=0), so a plain level lookup is correct here —
     * no summing across instances needed, unlike Housing.
     *
     * "Handelsvertrag" requires a Konsul (trader advisor) assigned to the colony AND
     * the Cantina (Bar building) at level >= 1 — the Konsul brokers trade deals
     * through it. 0 with no Konsul assigned; an intended cost of that advisor slot
     * choice, not a bug (GDD §12 Kanal 1).
     *
     * Colonies without a CC (level = 0) are skipped — the Nexus subsidy only flows
     * once the colony is operational.  NPC colonies (user_id = null) are skipped.
     *
     * @return int Number of users credited this tick.
     */
    private function generatePassiveCredits(int $tick): int
    {
        $nexusSubsidy = (int) config('game.credits.nexus_subsidy', 30);
        $relayBonusPerLevel = (int) config('game.credits.relay_bonus_per_uplink_level', 20);
        $contractIncomePerRank = config('game.credits.consul_contract_income_per_rank', [1 => 10, 2 => 25, 3 => 45]);

        $colonies = Colony::whereNotNull('user_id')->get();
        $processed = 0;

        foreach ($colonies as $colony) {
            $ccLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::CommandCenter->value)
                ->value('level');

            if ($ccLevel <= 0) {
                continue; // no CC → colony not operational → no subsidy
            }

            $uplinkLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::UplinkStation->value)
                ->value('level');

            $relayBonus = $uplinkLevel * $relayBonusPerLevel;

            $cantinaLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::Bar->value)
                ->value('level');

            $contract = 0;
            if ($cantinaLevel > 0) {
                $konsulRank = (int) DB::table('advisors')
                    ->where('colony_id', $colony->id)
                    ->where('personell_id', config('advisors.trader.id', 92))
                    ->value('rank');

                $contract = (int) ($contractIncomePerRank[$konsulRank] ?? 0);
            }

            $total = $nexusSubsidy + $relayBonus + $contract;

            DB::table('user_resources')
                ->where('user_id', $colony->user_id)
                ->increment('credits', $total);

            if ($total > 0) {
                $this->eventService->createEvent([
                    'user' => $colony->user_id ?? 0,
                    'tick' => $tick,
                    'event' => 'colony.passive_credits',
                    'area' => 'colony',
                    'parameters' => json_encode([
                        'colony_id' => $colony->id,
                        'subsidy' => $nexusSubsidy,
                        'relay_bonus' => $relayBonus,
                        'contract' => $contract,
                        'total' => $total,
                    ]),
                ]);
            }

            $processed++;
        }

        return $processed;
    }

    // ── 8d. Advisor upkeep ────────────────────────────────────────────────────

    /**
     * Deducts Credits upkeep for every active (assigned) advisor each tick.
     *
     * Upkeep schedule by rank (GDD §12):
     *   rank 1 → 10 Cr/Tick
     *   rank 2 → 50 Cr/Tick
     *   rank 3 → 160 Cr/Tick
     *
     * Credits are clamped to ≥ 0 (the player cannot go into debt from advisor upkeep).
     * Called AFTER generatePassiveCredits() so income is applied before costs.
     * Advisors without a colony assignment (unemployed) incur no upkeep.
     *
     * @return int Number of advisors processed this tick.
     */
    private function deductAdvisorUpkeep(int $tick): int
    {
        $upkeepByRank = config('game.advisor.upkeep', [1 => 10, 2 => 30, 3 => 80]);

        $advisors = Advisor::whereNotNull('colony_id')->with('colony')->get();

        foreach ($advisors as $advisor) {
            if (! $advisor->colony || $advisor->colony->user_id === null) {
                continue; // NPC colony or orphaned advisor — skip
            }

            $upkeep = (int) ($upkeepByRank[$advisor->rank] ?? 10);

            DB::table('user_resources')
                ->where('user_id', $advisor->colony->user_id)
                ->update([
                    'credits' => DB::raw("MAX(0, credits - {$upkeep})"),
                ]);
        }

        return $advisors->count();
    }

    // ── 9. Advisor ticks ─────────────────────────────────────────────────────

    // ── 10. Bar offers ───────────────────────────────────────────────────────

    private function processBarOffers(int $tick): int
    {
        $colonyIds = DB::table('colony_buildings')
            ->where('building_id', (int) config('buildings.bar.id', 52))
            ->where('level', '>', 0)
            ->pluck('colony_id');

        foreach ($colonyIds as $colonyId) {
            $this->barService->generateOffersForColony((int) $colonyId, $tick);
        }

        return $colonyIds->count();
    }

    // ── 9. Advisor ticks ─────────────────────────────────────────────────────

    // ── 11. Merchant spawn ────────────────────────────────────────────────────

    /**
     * For every player colony, check if a new Traveling Merchant visit should
     * be spawned this tick. NPC colonies (user_id = null) are skipped.
     *
     * @return int Number of new visits spawned.
     */
    private function processMerchantSpawn(int $tick): int
    {
        $colonies = Colony::whereNotNull('user_id')->get();
        $spawned = 0;

        foreach ($colonies as $colony) {
            if ($this->merchantService->shouldSpawn($colony->id, $tick)) {
                $this->merchantService->spawnVisit($colony->id, $tick);
                $this->eventService->createEvent([
                    'user' => $colony->user_id ?? 0,
                    'tick' => $tick,
                    'event' => 'merchant.visit',
                    'area' => 'colony',
                    'parameters' => json_encode(['colony_id' => $colony->id]),
                ]);
                $spawned++;
            }
        }

        return $spawned;
    }

    private function incrementAdvisorTicks(): int
    {
        $updated = DB::table('advisors')
            ->whereNull('unavailable_until_tick')
            ->whereNotNull('colony_id')
            ->increment('active_ticks');

        $thresholds = config('game.advisor.rank_thresholds', [1 => 15, 2 => 45]);
        $promotionCosts = config('game.advisor.promotion_costs', [2 => 150, 3 => 400]);

        foreach ($thresholds as $fromRank => $ticks) {
            $toRank = $fromRank + 1;
            $cost = (int) ($promotionCosts[$toRank] ?? 0);

            $eligible = DB::table('advisors as a')
                ->join('glx_colonies as c', 'c.id', '=', 'a.colony_id')
                ->where('a.rank', $fromRank)
                ->where('a.active_ticks', '>=', $ticks)
                ->whereNotNull('a.colony_id')
                ->select('a.id', 'c.user_id')
                ->get();

            foreach ($eligible as $advisor) {
                DB::transaction(function () use ($advisor, $fromRank, $toRank, $cost): void {
                    // Re-read with row lock to prevent race condition on concurrent tick runs.
                    $current = DB::table('advisors')
                        ->where('id', $advisor->id)
                        ->lockForUpdate()
                        ->first();

                    // Guard: already promoted (or demoted) since the eligible query ran.
                    if (! $current || (int) $current->rank !== $fromRank) {
                        return;
                    }

                    if ($cost > 0) {
                        $credits = (int) (DB::table('user_resources')
                            ->where('user_id', $advisor->user_id)
                            ->value('credits') ?? 0);
                        if ($credits < $cost) {
                            return; // Deferred — try again next tick
                        }
                        DB::table('user_resources')
                            ->where('user_id', $advisor->user_id)
                            ->decrement('credits', $cost);
                    }

                    DB::table('advisors')->where('id', $advisor->id)->update(['rank' => $toRank]);
                });
            }
        }

        return $updated;
    }
}
