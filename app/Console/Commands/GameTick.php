<?php

namespace App\Console\Commands;

use App\Enums\BuildingId;
use App\Models\Advisor;
use App\Models\Colony;
use App\Models\ColonyBuilding;
use App\Models\ColonyResearch;
use App\Models\ColonyResource;
use App\Models\Fleet;
use App\Models\FleetOrder;
use App\Models\FleetResource;
use App\Models\FleetShip;
use App\Models\Run;
use App\Models\UserResource;
use App\Services\BarService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\MoralService;
use App\Services\OnboardingTriggerService;
use App\Services\ResourcesService;
use App\Services\TickService;
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
 *  1. Fleet move orders   — update fleet position to ordered coordinates
 *  2. Fleet trade orders  — transfer resources between fleet and colony
 *  3. Fleet combat orders — resolve combat between attacking and defending fleets
 *  4. Building decay      — decrement status_points (per-type decay_rate); level-down at ≤ 0
 *  5. Ship decay          — decrement fleet_ships.status_points; combat fleets × 2; remove at ≤ 0
 *  6. Research decay      — decrement colony_researches.status_points; level-down at ≤ 0
 *  7. Supply cap          — SET user_resources.supply = CC_flat + housing_level × 8 (cap model)
 *  8. Resource generation — produce colony resources per industry building level (moral multiplier applied)
 *  8b. Moral calculation  — recalculate colony moral and store in colony_resources (resource_id=12)
 *  8c. Passive Credits    — Nexus subsidy (30 Cr) + colony tax per housing level (20 Cr/level) added to user Credits
 *  8d. Advisor upkeep     — deduct Credits per active advisor by rank (10/50/160 Cr); clamped to ≥ 0
 *  9. Advisor ticks       — increment active_ticks, check rank promotions
 * 10. Bar offers          — expire stale offers, generate new NPC offers per colony with Bar
 * 11. Merchant spawn      — check each colony for a new Traveling Merchant visit
 */
class GameTick extends Command
{
    protected $signature   = 'game:tick
                                {--run=  : Run ID to process (omit to use the first active run)}
                                {--tick= : Override the tick number (default: from run or time-based)}';
    protected $description = 'Process one game tick (fleet orders, decay, supply, resources)';

    /** Fleet IDs that were involved in an encounter this tick — used for 2× ship decay. */
    private array $encounterFleetIds = [];

    public function __construct(
        private readonly TickService               $tickService,
        private readonly EventService              $eventService,
        private readonly MoralService              $moralService,
        private readonly ResourcesService          $resourcesService,
        private readonly OnboardingTriggerService  $onboardingTriggerService,
        private readonly BarService                $barService,
        private readonly MerchantService           $merchantService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // Resolve the Run record: explicit --run= ID, or first active run as fallback.
        $runId = $this->option('run');
        $run   = $runId
            ? Run::find((int) $runId)
            : Run::where('status', 'active')->first();

        if (!$run) {
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
            $n = $this->processMoveOrders($tick);
            $this->line("  Fleet move orders:        {$n}");

            $n = $this->processTradeOrders($tick);
            $this->line("  Fleet trade orders:       {$n}");

            $n = $this->processEncounterOrders($tick);
            $this->line("  Fleet encounter orders:   {$n}");

            $n = $this->processBuildingDecay($tick);
            $this->line("  Buildings levelled down:  {$n}");

            $n = $this->processShipDecay($tick);
            $this->line("  Ship entries destroyed:   {$n}");

            $n = $this->processResearchDecay($tick);
            $this->line("  Researches levelled down: {$n}");

            $n = $this->calculateSupply();
            $this->line("  Users supply updated:     {$n}");

            $n = $this->generateResources($tick);
            $this->line("  Colonies with resources:  {$n}");

            $n = $this->calculateMoral($tick);
            $this->line("  Colonies moral updated:   {$n}");

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
        $runProgressService = $this->laravel->make(\App\Services\RunProgressService::class);

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

    // ── 1. Fleet: move orders ────────────────────────────────────────────────

    private function processMoveOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'move')
            ->where('was_processed', 0)
            ->get();

        foreach ($orders as $order) {
            $coords = json_decode($order->coordinates, true);
            if (!is_array($coords) || count($coords) < 2) {
                $this->warn("  Fleet {$order->fleet_id}: invalid move coordinates, skipping.");
                continue;
            }

            Fleet::where('id', $order->fleet_id)->update([
                'x' => $coords[0],
                'y' => $coords[1],
            ]);

            $order->update(['was_processed' => 1]);

            $fleet = Fleet::find($order->fleet_id);
            if ($fleet) {
                $this->eventService->createEvent([
                    'user'       => $fleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.fleet_arrived',
                    'area'       => 'galaxy',
                    'parameters' => json_encode(['fleet_id' => $order->fleet_id, 'coords' => $coords]),
                ]);
            }
        }

        return $orders->count();
    }

    // ── 2. Fleet: trade orders ───────────────────────────────────────────────

    private function processTradeOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'trade')
            ->where('was_processed', 0)
            ->get();

        $processed = 0;

        foreach ($orders as $order) {
            $data = json_decode($order->data, true);
            if (!is_array($data)) {
                $this->warn("  Fleet {$order->fleet_id}: invalid trade data, skipping.");
                continue;
            }

            $colonyId   = (int) ($data['colony_id'] ?? 0);
            $resourceId = (int) ($data['resource_id'] ?? 0);
            $amount     = (int) ($data['amount'] ?? 0);
            $direction  = (int) ($data['direction'] ?? 0); // 0=buy(colony→fleet), 1=sell(fleet→colony)

            if (!$colonyId || !$resourceId || !$amount) {
                $this->warn("  Fleet {$order->fleet_id}: incomplete trade data (colony_id={$colonyId}), skipping.");
                continue;
            }

            $this->transferResource($colonyId, $order->fleet_id, $resourceId,
                $direction === 0 ? $amount : -$amount);

            $order->update(['was_processed' => 1]);

            $fleet = Fleet::find($order->fleet_id);
            if ($fleet) {
                $this->eventService->createEvent([
                    'user'       => $fleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.trade',
                    'area'       => 'galaxy',
                    'parameters' => json_encode(['colony_id' => $colonyId]),
                ]);
            }

            $processed++;
        }

        return $processed;
    }

    /**
     * Transfer $amount of $resourceId from colony to fleet (negative = fleet to colony).
     */
    private function transferResource(int $colonyId, int $fleetId, int $resourceId, int $amount): void
    {
        // Clamp amount to what the source actually has to prevent creating resources from nothing.
        if ($amount > 0) {
            // Colony → Fleet: clamp to colony stock
            $colonyStock = (int) ColonyResource::where('colony_id', $colonyId)
                ->where('resource_id', $resourceId)
                ->value('amount');
            $amount = min($amount, $colonyStock);
        } elseif ($amount < 0) {
            // Fleet → Colony: clamp to fleet stock
            $fleetStock = (int) FleetResource::where('fleet_id', $fleetId)
                ->where('resource_id', $resourceId)
                ->value('amount');
            $amount = max($amount, -$fleetStock);
        }

        if ($amount === 0) {
            return;
        }

        ColonyResource::where('colony_id', $colonyId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("MAX(0, amount - {$amount})")]);

        // FleetResource has a composite PK (fleet_id, resource_id) — Eloquent::increment()
        // would generate WHERE id = null and silently do nothing. Use a raw UPDATE + INSERT
        // pair instead.
        $updated = DB::table('fleet_resources')
            ->where('fleet_id', $fleetId)
            ->where('resource_id', $resourceId)
            ->update(['amount' => DB::raw("amount + {$amount}")]);

        if ($updated === 0) {
            // Row did not exist yet (new resource on this fleet)
            DB::table('fleet_resources')->insert([
                'fleet_id'    => $fleetId,
                'resource_id' => $resourceId,
                'amount'      => $amount,
            ]);
        }
    }

    // ── 3. Fleet: encounter orders ───────────────────────────────────────────

    private function processEncounterOrders(int $tick): int
    {
        $orders = FleetOrder::where('tick', $tick)
            ->where('order', 'attack')
            ->where('was_processed', 0)
            ->get();

        $shipPower = config('game.combat.ship_power', []);
        $processed = 0;

        foreach ($orders as $order) {
            $coords = json_decode($order->coordinates, true);
            if (!is_array($coords) || count($coords) < 3) {
                $this->warn("  Fleet {$order->fleet_id}: invalid encounter coordinates, skipping.");
                continue;
            }

            $initiator = Fleet::find($order->fleet_id);
            if (!$initiator) {
                continue;
            }

            Fleet::where('id', $initiator->id)->update([
                'x' => $coords[0], 'y' => $coords[1],
            ]);

            $encountered = Fleet::where('x', $coords[0])
                ->where('y', $coords[1])
                ->where('user_id', '!=', $initiator->user_id)
                ->where('id', '!=', $initiator->id)
                ->get();

            if ($encountered->isEmpty()) {
                $order->update(['was_processed' => 1]);
                $this->eventService->createEvent([
                    'user'       => $initiator->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.fleet_arrived',
                    'area'       => 'galaxy',
                    'parameters' => json_encode(['fleet_id' => $initiator->id, 'coords' => $coords]),
                ]);
                $processed++;
                continue;
            }

            $initiatorPower    = $this->calcFleetPower($initiator->id, $shipPower);
            $encounteredFleetIds = $encountered->pluck('id')->all();
            $encounteredPower  = array_sum(array_map(
                fn($id) => $this->calcFleetPower($id, $shipPower),
                $encounteredFleetIds
            ));

            $totalPower = $initiatorPower + $encounteredPower;
            if ($totalPower > 0) {
                $this->applyShipLosses($initiator->id, $encounteredPower / $totalPower, $shipPower);
                foreach ($encounteredFleetIds as $encId) {
                    $this->applyShipLosses($encId, $initiatorPower / $totalPower, $shipPower);
                }

                // Record all encounter participants for the 2× ship decay step
                $this->encounterFleetIds[] = $initiator->id;
                foreach ($encounteredFleetIds as $encId) {
                    $this->encounterFleetIds[] = $encId;
                }
            }

            $order->update(['was_processed' => 1]);

            // Notify initiating fleet
            $this->eventService->createEvent([
                'user'       => $initiator->user_id,
                'tick'       => $tick,
                'event'      => 'galaxy.encounter',
                'area'       => 'galaxy',
                'parameters' => json_encode([
                    'initiator_id'   => $initiator->user_id,
                    'encountered_id' => $encountered->first()->user_id,
                    'colony_id'      => 0,
                ]),
            ]);

            $initiatorColonyId = $this->getColonyIdByUserId($initiator->user_id);
            if ($initiatorColonyId) {
                $this->moralService->fireEvent(
                    $initiatorColonyId,
                    $initiatorPower >= $encounteredPower ? 'encounter_won' : 'encounter_lost',
                    $tick
                );
            }

            // Notify each unique encountered fleet
            foreach ($encountered->unique('user_id') as $encFleet) {
                $this->eventService->createEvent([
                    'user'       => $encFleet->user_id,
                    'tick'       => $tick,
                    'event'      => 'galaxy.encounter',
                    'area'       => 'galaxy',
                    'parameters' => json_encode([
                        'initiator_id'   => $initiator->user_id,
                        'encountered_id' => $encFleet->user_id,
                        'colony_id'      => 0,
                    ]),
                ]);

                $encounteredColonyId = $this->getColonyIdByUserId($encFleet->user_id);
                if ($encounteredColonyId) {
                    $this->moralService->fireEvent($encounteredColonyId, 'colony_threatened', $tick);
                    $this->moralService->fireEvent(
                        $encounteredColonyId,
                        $encounteredPower >= $initiatorPower ? 'encounter_won' : 'encounter_lost',
                        $tick
                    );
                }
            }

            $processed++;
        }

        return $processed;
    }

    private function getColonyIdByUserId(int $userId): ?int
    {
        return DB::table('glx_colonies')
            ->where('user_id', $userId)
            ->value('id');
    }

    private function calcFleetPower(int $fleetId, array $shipPower): int
    {
        return FleetShip::where('fleet_id', $fleetId)
            ->get()
            ->sum(fn($s) => $s->count * ($shipPower[$s->ship_id] ?? 0));
    }

    private function applyShipLosses(int $fleetId, float $lossRatio, array $shipPower): void
    {
        $ships = FleetShip::where('fleet_id', $fleetId)->get();
        foreach ($ships as $ship) {
            if (($shipPower[$ship->ship_id] ?? 0) === 0) {
                continue; // non-combat ships not destroyed in battle
            }
            $remaining = max(0, $ship->count - (int) ceil($ship->count * $lossRatio));
            if ($remaining <= 0) {
                $ship->delete();
            } else {
                $ship->update(['count' => $remaining]);
            }
        }
    }

    // ── 4. Building decay ────────────────────────────────────────────────────

    private function processBuildingDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates    = DB::table('buildings')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('buildings')->pluck('max_status_points', 'id');
        $levelled      = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(buildings).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        // Sicherheits-Hub recycling: colonies that have securityHub built get a
        // fraction of build costs back on any building level-down.
        $secHubId         = (int) config('buildings.securityHub.id', 53);
        $recyclePct       = (float) config('buildings.securityHub.recycle_pct', 0.10);
        $secHubColonies   = DB::table('colony_buildings')
            ->where('building_id', $secHubId)
            ->where('level', '>', 0)
            ->pluck('colony_id')
            ->flip()
            ->all();

        // Build cost map for recycling: building_id → [resource_id => amount]
        // Only tradeable colony resources (3=regolith, 4=compounds, 5=organics).
        $tradeableIds     = [3, 4, 5];
        $buildCostMap     = DB::table('building_costs')
            ->whereIn('resource_id', $tradeableIds)
            ->get()
            ->groupBy('building_id')
            ->map(fn($rows) => $rows->pluck('amount', 'resource_id')->all())
            ->all();

        $buildings = ColonyBuilding::where('level', '>', 0)->get();

        foreach ($buildings as $cb) {
            $rate         = (float) ($decayRates[$cb->building_id] ?? $fallbackRate);
            $overCapMult  = in_array($cb->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus    = (float) $cb->status_points - ($rate * $overCapMult);
            $where     = ['colony_id' => $cb->colony_id, 'building_id' => $cb->building_id];

            if ($newStatus <= 0) {
                $maxSP    = (int) ($maxSPMap[$cb->building_id] ?? 20);
                $newLevel = max(0, $cb->level - 1);

                DB::table('colony_buildings')->where($where)->update([
                    'level'         => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cb->colony_id);
                $this->eventService->createEvent([
                    'user'       => $colony?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => json_encode([
                        'colony_id' => $cb->colony_id,
                        'tech_id'   => $cb->building_id,
                    ]),
                ]);

                // Sicherheits-Hub: return recycle_pct of tradeable build costs on level-down.
                if (isset($secHubColonies[$cb->colony_id]) && isset($buildCostMap[$cb->building_id])) {
                    foreach ($buildCostMap[$cb->building_id] as $resId => $baseAmount) {
                        $returned = (int) max(1, floor($baseAmount * $recyclePct));
                        DB::table('colony_resources')->updateOrInsert(
                            ['colony_id' => $cb->colony_id, 'resource_id' => $resId],
                            ['amount'    => DB::raw("amount + {$returned}")]
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
                    if ($userId !== null && !$this->onboardingTriggerService->hasFired($userId, 'onboarding_decay')) {
                        $this->eventService->createEvent([
                            'user'       => $userId,
                            'tick'       => $tick,
                            'event'      => 'onboarding_decay',
                            'area'       => 'techtree',
                            'parameters' => json_encode(['colony_id' => $cb->colony_id, 'tech_id' => $cb->building_id]),
                        ]);
                        $this->onboardingTriggerService->markFired($userId, 'onboarding_decay');
                    }
                }
            }
        }

        return $levelled;
    }

    // ── 5. Ship decay ────────────────────────────────────────────────────────

    private function processShipDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $combatFactor  = (float) config('game.decay.combat_factor', 2);
        $decayRates    = DB::table('ships')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('ships')->pluck('max_status_points', 'id');
        $destroyed     = 0;

        FleetShip::orderBy('fleet_id')->orderBy('ship_id')->chunk(200, function ($fleetShips) use ($fallbackRate, $combatFactor, $decayRates, $maxSPMap, &$destroyed, $tick) {
        foreach ($fleetShips as $fs) {
            $rate = (float) ($decayRates[$fs->ship_id] ?? $fallbackRate);

            if (in_array($fs->fleet_id, $this->encounterFleetIds)) {
                $rate *= $combatFactor;
            }

            $newStatus = (float) $fs->status_points - $rate;
            $where     = ['fleet_id' => $fs->fleet_id, 'ship_id' => $fs->ship_id, 'is_cargo' => $fs->is_cargo];

            if ($newStatus <= 0) {
                // Ship fully decayed — remove from fleet
                $fleet = Fleet::find($fs->fleet_id);
                $this->eventService->createEvent([
                    'user'       => $fleet?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => json_encode([
                        'colony_id' => 0,
                        'tech_id'   => $fs->ship_id,
                    ]),
                ]);
                DB::table('fleet_ships')->where($where)->delete();
                $destroyed++;
            } else {
                DB::table('fleet_ships')->where($where)->update(['status_points' => $newStatus]);
            }
        }
        }); // end chunkById

        return $destroyed;
    }

    // ── 6. Research decay ────────────────────────────────────────────────────

    private function processResearchDecay(int $tick): int
    {
        $fallbackRate  = (float) config('game.decay.rate', 1);
        $overcapFactor = (float) config('game.decay.overcap_factor', 2.0);
        $decayRates    = DB::table('researches')->pluck('decay_rate', 'id');
        $maxSPMap      = DB::table('researches')->pluck('max_status_points', 'id');
        $levelled      = 0;

        // Build the over-cap set once before iterating — O(colonies), not O(researches).
        $overCapColonies = $this->resourcesService->getOverCapColonyIds();

        $knowledgeIds = collect(config('knowledge'))->pluck('id')->toArray();

        // Kenntnisse (purpose='knowledge') never decay — GDD §10.
        $researches = ColonyResearch::where('level', '>', 0)
            ->whereNotIn('research_id', $knowledgeIds)
            ->get();

        foreach ($researches as $cr) {
            $rate        = (float) ($decayRates[$cr->research_id] ?? $fallbackRate);
            $overCapMult = in_array($cr->colony_id, $overCapColonies) ? $overcapFactor : 1.0;
            $newStatus   = (float) $cr->status_points - ($rate * $overCapMult);
            $where     = ['colony_id' => $cr->colony_id, 'research_id' => $cr->research_id];

            if ($newStatus <= 0) {
                $maxSP    = (int) ($maxSPMap[$cr->research_id] ?? 20);
                $newLevel = max(0, $cr->level - 1);

                DB::table('colony_researches')->where($where)->update([
                    'level'         => $newLevel,
                    'status_points' => $maxSP,
                ]);

                $colony = Colony::find($cr->colony_id);
                $this->eventService->createEvent([
                    'user'       => $colony?->user_id ?? 0,
                    'tick'       => $tick,
                    'event'      => 'techtree.level_down',
                    'area'       => 'techtree',
                    'parameters' => json_encode([
                        'colony_id' => $cr->colony_id,
                        'tech_id'   => $cr->research_id,
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
        $capCC         = (int) config('buildings.commandCenter.supply_cap', 10);
        $capHousing    = (int) config('buildings.housingComplex.supply_cap', 8);
        $capMax        = (int) config('game.supply.cap_max', 200);
        $capPerLevel   = config('game.supply.knowledge_cap_per_level', []);
        $knowledgeIds  = collect(config('knowledge'))->pluck('id')->toArray();

        $userIds = Colony::whereNotNull('user_id')->distinct()->pluck('user_id');

        foreach ($userIds as $userId) {
            $colony = Colony::where('user_id', $userId)->first();
            if (!$colony) {
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
            if (!empty($knowledgeIds)) {
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
            if (!$this->onboardingTriggerService->hasFired($userId, 'supply_cap_full')) {
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

        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Apply moral production multiplier based on the colony's CURRENT moral
            // (stored from the previous tick's moral calculation — no circular dependency).
            $moral      = $this->moralService->getMoral($colony->id);
            $multiplier = $this->moralService->getProductionMultiplier($moral);

            foreach ($productionConfig as $buildingId => $outputs) {
                $building = DB::table('colony_buildings')
                    ->where('colony_id', $colony->id)
                    ->where('building_id', $buildingId)
                    ->first();

                if (!$building || $building->level <= 0) {
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

    // ── 8b. Moral calculation ─────────────────────────────────────────────────

    private function calculateMoral(int $tick): int
    {
        $colonies = Colony::all();

        foreach ($colonies as $colony) {
            // Trigger 3 — onboarding_trust: fires once when trust crosses from
            // non-negative to negative for a real (non-NPC) colony.
            $userId = $colony->user_id ?? null;
            $trustBefore = null;
            if ($userId !== null && !$this->onboardingTriggerService->hasFired($userId, 'onboarding_trust')) {
                $trustBefore = (int) (DB::table('colony_resources')
                    ->where('colony_id', $colony->id)
                    ->where('resource_id', 12)
                    ->value('amount') ?? 0);
            }

            $this->moralService->calculateAndStore($colony, $tick);

            if ($userId !== null && $trustBefore !== null && $trustBefore >= 0) {
                $trustAfter = (int) (DB::table('colony_resources')
                    ->where('colony_id', $colony->id)
                    ->where('resource_id', 12)
                    ->value('amount') ?? 0);

                if ($trustAfter < 0) {
                    $this->eventService->createEvent([
                        'user'       => $userId,
                        'tick'       => $tick,
                        'event'      => 'onboarding_trust',
                        'area'       => 'colony',
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
        $nexusSubsidy  = (int) config('game.credits.nexus_subsidy', 30);
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
            if (!$advisor->colony || $advisor->colony->user_id === null) {
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
        $spawned  = 0;

        foreach ($colonies as $colony) {
            if ($this->merchantService->shouldSpawn($colony->id, $tick)) {
                $this->merchantService->spawnVisit($colony->id, $tick);
                $this->eventService->createEvent([
                    'user'       => $colony->user_id,
                    'tick'       => $tick,
                    'event'      => 'merchant.visit',
                    'area'       => 'colony',
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

        $thresholds    = config('game.advisor.rank_thresholds', [1 => 10, 2 => 20]);
        $promotionCosts = config('game.advisor.promotion_costs', [2 => 150, 3 => 400]);

        foreach ($thresholds as $fromRank => $ticks) {
            $toRank = $fromRank + 1;
            $cost   = (int) ($promotionCosts[$toRank] ?? 0);

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
                    if (!$current || (int) $current->rank !== $fromRank) {
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
