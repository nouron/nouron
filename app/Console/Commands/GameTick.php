<?php

namespace App\Console\Commands;

use App\Enums\BuildingId;
use App\Models\Advisor;
use App\Models\Colony;
use App\Models\ColonyBuilding;
use App\Models\ColonyResearch;
use App\Models\ColonyResource;
use App\Models\Run;
use App\Models\UserResource;
use App\Services\BarService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\OnboardingTriggerService;
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
 * Steps per tick:
 *  0. Hangar deliveries   — transition building→docked ships; expire pending ships
 *  4. Building decay      — decrement status_points (per-type decay_rate); level-down at ≤ 0
 *  6. Research decay      — decrement colony_researches.status_points; level-down at ≤ 0
 *  7. Supply cap          — SET user_resources.supply = CC_flat + housing_level × 8 (cap model)
 *  8. Resource generation — produce colony resources per industry building level (moral multiplier applied)
 *  8b. Trust calculation  — recalculate colony trust and store in colony_resources (resource_id=12)
 *  8c. Passive Credits    — Nexus subsidy (30 Cr) + colony tax per housing level (20 Cr/level) added to user Credits
 *  8d. Advisor upkeep     — deduct Credits per active advisor by rank (10/50/160 Cr); clamped to ≥ 0
 *  9. Advisor ticks       — increment active_ticks, check rank promotions
 * 10. Bar offers          — expire stale offers, generate new NPC offers per colony with Bar
 * 11. Merchant spawn      — check each colony for a new Traveling Merchant visit
 */
class GameTick extends Command
{
    protected $signature = 'game:tick
                                {--run=  : Run ID to process (omit to use the first active run)}
                                {--tick= : Override the tick number (default: from run or time-based)}';

    protected $description = 'Process one game tick (decay, supply, resources, trust)';

    public function __construct(
        private readonly TickService $tickService,
        private readonly EventService $eventService,
        private readonly TrustService $trustService,
        private readonly ResourcesService $resourcesService,
        private readonly OnboardingTriggerService $onboardingTriggerService,
        private readonly BarService $barService,
        private readonly MerchantService $merchantService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Resolve the Run record: explicit --run= ID, or first active run as fallback.
        $runId = $this->option('run');
        $run = $runId
            ? Run::find((int) $runId)
            : Run::where('status', 'active')->first();

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

        DB::transaction(function () use ($tick) {
            [$delivered, $expired] = $this->processHangarDeliveries($tick);
            $this->line("  Hangar ships delivered:   {$delivered}");
            $this->line("  Hangar ships expired:     {$expired}");

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

        // Step 12 — Run structure: phase transitions, objective progress, run-end checks.
        // Runs outside the main DB::transaction so that endRun() can commit independently
        // and return early without rolling back the tick's resource/decay work.
        $run->refresh();
        $runProgressService = $this->laravel->make(RunProgressService::class);

        if ($run->phase === 1) {
            if ($runProgressService->checkPhase1Completion($run)) {
                $runProgressService->transitionToPhase2($run);
                $run->refresh();
                $this->line('  Phase 1 completed — transitioning to Phase 2.');
            }
        }

        if ($run->phase === 2) {
            $runProgressService->updateObjectiveProgress($run);

            // Step 12a — Nexus interventions (warnings, sanctions, nexus_debt fail).
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
                $score = $runProgressService->calculateScore($run);
                $runProgressService->endRun($run, 'completed');
                $this->info("  Run #{$run->id} completed! Score: {$score}");
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
        // fraction of build costs back on any building level-down.
        $secHubId = (int) config('buildings.securityHub.id', 53);
        $recyclePct = (float) config('buildings.securityHub.recycle_pct', 0.10);
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
            $where = ['colony_id' => $cb->colony_id, 'building_id' => $cb->building_id];

            if ($newStatus <= 0) {
                $maxSP = (int) ($maxSPMap[$cb->building_id] ?? 20);
                $newLevel = max(0, $cb->level - 1);

                DB::table('colony_buildings')->where($where)->update([
                    'level' => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cb->colony_id);
                $this->eventService->createEvent([
                    'user' => $colony?->user_id ?? 0,
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

                // Sicherheits-Hub: return recycle_pct of tradeable build costs on level-down.
                if (isset($secHubColonies[$cb->colony_id]) && isset($buildCostMap[$cb->building_id])) {
                    foreach ($buildCostMap[$cb->building_id] as $resId => $baseAmount) {
                        $returned = (int) max(1, floor($baseAmount * $recyclePct));
                        DB::table('colony_resources')->updateOrInsert(
                            ['colony_id' => $cb->colony_id, 'resource_id' => $resId],
                            ['amount' => DB::raw("amount + {$returned}")]
                        );
                    }
                }

                $levelled++;
            } else {
                DB::table('colony_buildings')->where($where)
                    ->update(['status_points' => $newStatus]);

                // Trigger 1 — onboarding_decay: fires once when a building first
                // drops below 80 % of its max_status_points.
                $maxSP = (int) ($maxSPMap[$cb->building_id] ?? 20);
                if ($newStatus < ($maxSP * 0.8)) {
                    $colony = Colony::find($cb->colony_id);
                    $userId = $colony?->user_id ?? null;
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
                    'user' => $colony?->user_id ?? 0,
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

    private function generateResources(int $tick): int
    {
        $productionConfig = config('game.production', []);
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

            foreach ($productionConfig as $buildingId => $outputs) {
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

                foreach ($outputs as $resourceId => $ratePerLevel) {
                    $yield = (int) round($building->level * $ratePerLevel * $multiplier);
                    ColonyResource::where('colony_id', $colony->id)
                        ->where('resource_id', $resourceId)
                        ->update(['amount' => DB::raw("amount + {$yield}")]);
                }
            }
        }

        return $colonies->count();
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
        $perEater = max(1, (int) config('game.food.supply_per_eater', 4));
        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            $usedSupply = (int) DB::table('colony_buildings as cb')
                ->join('buildings as b', 'b.id', '=', 'cb.building_id')
                ->where('cb.colony_id', $colony->id)
                ->where('cb.level', '>', 0)
                ->sum(DB::raw('cb.level * COALESCE(b.supply_cost, 0)'));

            $foodNeed = intdiv($usedSupply, $perEater);

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
     *   nexus    = game.credits.nexus_subsidy        (flat, if CC level > 0)
     *   housing  = housingComplex.level × game.credits.tax_per_housing
     *   total    = nexus + housing
     *
     * Colonies without a CC (level = 0) are skipped — the Nexus subsidy only flows
     * once the colony is operational.  NPC colonies (user_id = null) are skipped.
     *
     * @return int Number of users credited this tick.
     */
    private function generatePassiveCredits(int $tick): int
    {
        $nexusSubsidy = (int) config('game.credits.nexus_subsidy', 30);
        $taxPerHousing = (int) config('game.credits.tax_per_housing', 20);

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

            $housingLevel = (int) DB::table('colony_buildings')
                ->where('colony_id', $colony->id)
                ->where('building_id', BuildingId::Housing->value)
                ->value('level');

            $total = $nexusSubsidy + ($housingLevel * $taxPerHousing);

            DB::table('user_resources')
                ->where('user_id', $colony->user_id)
                ->increment('credits', $total);

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
        $upkeepByRank = config('game.advisor.upkeep', [1 => 10, 2 => 50, 3 => 160]);

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
                    'user' => $colony->user_id,
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

        $thresholds = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);
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
