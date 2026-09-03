<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Models\Advisor;
use App\Models\Colony;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HangarService
{
    private const HANGAR_BUILDING_ID = 44;

    private const ALLOWED_SHIP_IDS = [37, 47, 85];

    private const SHIP_MAX_STATUS = 20; // matches buildings.max_status_points convention

    private const REPAIR_SP_PER_AP = 2;  // status_points restored per AP spent

    /**
     * Credits discount applied per AP the Konsul advisor spends during ship negotiation.
     * Tune here — never hardcode in callers.
     */
    private const CONSUL_AP_DISCOUNT = 50;

    /**
     * Map ship DB IDs → ships.php config keys so we can read nexus_cost / nexus_delivery_ticks.
     */
    private const SHIP_ID_TO_CONFIG_KEY = [
        85 => 'drone',
        37 => 'corvette',
        47 => 'freighter',
    ];

    public function __construct(
        private readonly TickService $tickService,
        private readonly TrustService $trustService,
        private readonly AdvisorService $advisorService,
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
        private readonly ProjectBonusService $projectBonusService,
    ) {}

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Returns all hangar slots for the colony, sorted by instance_id ascending.
     * Pending ships (hangar_instance_id IS NULL) are excluded.
     * Each element contains the hangar bay data and the assigned ship (or null).
     */
    public function getHangarSlots(int $colonyId): array
    {
        $hangars = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::HANGAR_BUILDING_ID)
            ->orderBy('instance_id')
            ->get(['instance_id', 'level', 'status_points'])
            ->all();

        if (empty($hangars)) {
            return [];
        }

        $instanceIds = array_map(fn ($h) => $h->instance_id, $hangars);

        // Load all ships assigned to these hangar slots in one query (exclude pending).
        $ships = DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereIn('colony_ships.hangar_instance_id', $instanceIds)
            ->get([
                'colony_ships.id',
                'colony_ships.ship_id',
                'ships.name',
                'colony_ships.ship_state',
                'colony_ships.level',
                'colony_ships.status_points',
                'colony_ships.ap_spend',
                'colony_ships.hangar_instance_id',
                'colony_ships.deliver_at_tick',
            ])
            ->keyBy('hangar_instance_id')
            ->all();

        // Load active missions for all hangar slots in one query.
        $missions = DB::table('colony_hangar_missions')
            ->where('colony_id', $colonyId)
            ->whereIn('instance_id', $instanceIds)
            ->where('state', 'active')
            ->get()
            ->keyBy('instance_id')
            ->all();

        $slots = [];
        foreach ($hangars as $hangar) {
            $iid = $hangar->instance_id;
            $ship = $ships[$iid] ?? null;

            $shipData = null;
            if ($ship !== null) {
                $mission = $missions[$iid] ?? null;
                if ($mission !== null) {
                    $mission = (array) $mission;
                    // destination carries the mission_key (catalog); enrich for the UI.
                    $mission['mission_key'] = $mission['destination'];
                    $mission['mission_name'] = __("missions.{$mission['destination']}_name");
                    $mission['return_tick'] = (int) $mission['dispatch_tick'] + 2 * (int) $mission['sol_distance'];
                }
                $shipData = [
                    'id' => (int) $ship->id,
                    'ship_id' => (int) $ship->ship_id,
                    'name' => $ship->name,
                    'ship_state' => $ship->ship_state,
                    'level' => (int) $ship->level,
                    'status_points' => (float) $ship->status_points,
                    'ap_spend' => (int) $ship->ap_spend,
                    'deliver_at_tick' => $ship->deliver_at_tick !== null ? (int) $ship->deliver_at_tick : null,
                    'active_mission' => $mission,
                ];
            }

            $slots[] = [
                'instance_id' => (int) $iid,
                'hangar_level' => (int) $hangar->level,
                'hangar_status' => (float) $hangar->status_points,
                'ship' => $shipData,
            ];
        }

        return $slots;
    }

    /**
     * Returns all pending ships for this colony (no hangar slot assigned yet).
     * Joined with ships master table for display name.
     *
     * @return array<int, array{id: int, ship_id: int, name: string, ship_state: string, pending_until_tick: int|null}>
     */
    public function getPendingShips(int $colonyId): array
    {
        return DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereNull('colony_ships.hangar_instance_id')
            ->get([
                'colony_ships.id',
                'colony_ships.ship_id',
                'ships.name',
                'colony_ships.ship_state',
                'colony_ships.deliver_at_tick',
                'colony_ships.pending_until_tick',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'ship_id' => (int) $row->ship_id,
                'name' => $row->name,
                'ship_state' => $row->ship_state,
                'deliver_at_tick' => $row->deliver_at_tick !== null ? (int) $row->deliver_at_tick : null,
                'pending_until_tick' => $row->pending_until_tick !== null ? (int) $row->pending_until_tick : null,
            ])
            ->all();
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Request a ship from the Nexus.
     *
     * The ship is created immediately in colony_ships with ship_state = 'building'.
     * It transitions to 'docked' when the tick service delivers it (via processHangarDeliveries).
     *
     * If no free hangar slot is available the ship is created with ship_state = 'pending'
     * and hangar_instance_id = NULL. A pending ship decays (deleted) after
     * config('game.hangar.pending_decay_ticks') ticks unless assigned via assignToHangar().
     *
     * @param  int  $shipId  Must be one of ALLOWED_SHIP_IDS.
     * @param  bool  $useNexusCredit  Take the ship on debt (no upfront Credits).
     * @param  int  $consulApSpent  AP invested by the Konsul advisor — each AP reduces
     *                              the final credit cost by CONSUL_AP_DISCOUNT Cr.
     *
     * @throws RuntimeException on validation or insufficient credit failures.
     */
    public function requestShip(
        int $colonyId,
        int $shipId,
        bool $useNexusCredit = false,
        int $consulApSpent = 0,
    ): void {
        if (! in_array($shipId, self::ALLOWED_SHIP_IDS, true)) {
            throw new RuntimeException("Ship type {$shipId} is not orderable from the Nexus.");
        }

        if ($consulApSpent < 0) {
            throw new RuntimeException('consulApSpent must be zero or positive.');
        }

        if ($consulApSpent > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($consulApSpent > $availableAp) {
                throw new RuntimeException(
                    "Insufficient economy AP: requested {$consulApSpent}, available {$availableAp}."
                );
            }
        }

        $configKey = self::SHIP_ID_TO_CONFIG_KEY[$shipId];

        $baseCost = (int) config("ships.{$configKey}.nexus_cost", 0);
        $deliveryTicks = (int) config("ships.{$configKey}.nexus_delivery_ticks", 1);

        // Apply Konsul discount — each AP reduces cost, floor at 0 (cannot go negative).
        $discount = $consulApSpent * self::CONSUL_AP_DISCOUNT;
        $finalCost = max(0, $baseCost - $discount);

        $currentTick = $this->tickService->getTickCount();
        $pendingDecayTicks = (int) config('game.hangar.pending_decay_ticks', 5);

        DB::transaction(function () use (
            $colonyId, $shipId, $useNexusCredit, $consulApSpent,
            $finalCost, $deliveryTicks, $currentTick, $pendingDecayTicks,
        ): void {
            // Resolve the user who owns this colony (needed for credit checks).
            $userId = (int) DB::table('glx_colonies')
                ->where('id', $colonyId)
                ->value('user_id');

            if ($useNexusCredit) {
                // Nexus-Kredit path: requires CC level >= minimum threshold.
                $minCcLevel = (int) config('game.hangar.nexus_credit_min_cc_level', 2);
                $ccLevel = (int) DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', BuildingId::CommandCenter->value)
                    ->value('level');

                if ($ccLevel < $minCcLevel) {
                    throw new RuntimeException(
                        "Nexus-Kredit requires Command Center level {$minCcLevel} (current: {$ccLevel})."
                    );
                }

                // Apply trust penalty (fire one-shot trust event).
                $this->trustService->fireEvent($colonyId, 'nexus_credit', $currentTick);

                // Track Nexus debt on the run record.
                // nexus_debt lives on the runs table (not user_resources).
                // Increment the active run's nexus_debt by the ship's base cost.
                // NOTE: If no active run is found (edge case), debt tracking is skipped —
                // the trust penalty still applies as a soft consequence.
                $run = DB::table('runs')
                    ->where('status', 'active')
                    ->first(['id', 'nexus_debt']);

                if ($run !== null) {
                    DB::table('runs')
                        ->where('id', $run->id)
                        ->update(['nexus_debt' => $run->nexus_debt + $finalCost]);
                }

                // Nexus-Kredit = immediate delivery; set deliver_at_tick = current tick
                // so processHangarDeliveries() transitions the ship to 'docked' this same tick.
                $deliverAtTick = $currentTick;

            } else {
                // Standard purchase: deduct credits upfront.
                $credits = (int) DB::table('user_resources')
                    ->where('user_id', $userId)
                    ->value('credits');

                if ($credits < $finalCost) {
                    throw new RuntimeException(
                        "Insufficient credits: need {$finalCost}, have {$credits}."
                    );
                }

                if ($finalCost > 0) {
                    DB::table('user_resources')
                        ->where('user_id', $userId)
                        ->decrement('credits', $finalCost);
                }

                $deliverAtTick = $currentTick + $deliveryTicks;
            }

            // Determine hangar slot: find the first free slot for this colony.
            $occupiedInstanceIds = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->whereNotNull('hangar_instance_id')
                ->pluck('hangar_instance_id')
                ->all();

            $freeSlot = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', self::HANGAR_BUILDING_ID)
                ->when(! empty($occupiedInstanceIds), fn ($q) => $q->whereNotIn('instance_id', $occupiedInstanceIds))
                ->orderBy('instance_id')
                ->value('instance_id');

            $hangarInstanceId = $freeSlot !== null ? (int) $freeSlot : null;
            $shipState = 'building';
            $pendingUntilTick = null;

            if ($hangarInstanceId === null) {
                // No free hangar slot — ship enters pending state.
                $shipState = 'pending';
                $pendingUntilTick = $currentTick + $pendingDecayTicks;
            }

            DB::table('colony_ships')->insert([
                'colony_id' => $colonyId,
                'ship_id' => $shipId,
                'hangar_instance_id' => $hangarInstanceId,
                'ship_state' => $shipState,
                'level' => 0,
                'status_points' => self::SHIP_MAX_STATUS,
                'ap_spend' => 0,
                'deliver_at_tick' => $deliverAtTick,
                'pending_until_tick' => $pendingUntilTick,
            ]);

            if ($consulApSpent > 0) {
                $this->advisorService->lockActionPoints($colonyId, $consulApSpent);
            }
        });
    }

    /**
     * Assign a pending ship (no hangar) to a free hangar slot.
     *
     * @param  int  $shipRowId  The auto-increment colony_ships.id (PK).
     * @param  int  $instanceId  Hangar instance_id in colony_buildings to assign to.
     *
     * @throws RuntimeException if the ship row or hangar slot is not suitable.
     */
    public function assignToHangar(int $colonyId, int $shipRowId, int $instanceId): void
    {
        DB::transaction(function () use ($colonyId, $shipRowId, $instanceId): void {
            // Verify the ship row belongs to this colony and has no hangar assigned.
            $ship = DB::table('colony_ships')
                ->where('id', $shipRowId)
                ->where('colony_id', $colonyId)
                ->whereNull('hangar_instance_id')
                ->first();

            if ($ship === null) {
                throw new RuntimeException(
                    "Ship row {$shipRowId} not found, not pending, or does not belong to this colony."
                );
            }

            // Verify the target hangar slot exists and is free.
            $hangarExists = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', self::HANGAR_BUILDING_ID)
                ->where('instance_id', $instanceId)
                ->exists();

            if (! $hangarExists) {
                throw new RuntimeException("Hangar instance {$instanceId} does not exist for this colony.");
            }

            $slotOccupied = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->exists();

            if ($slotOccupied) {
                throw new RuntimeException("Hangar instance {$instanceId} already has a ship assigned.");
            }

            DB::table('colony_ships')
                ->where('id', $shipRowId)
                ->update([
                    'hangar_instance_id' => $instanceId,
                    'pending_until_tick' => null,
                    'ship_state' => 'docked',
                ]);
        });
    }

    /**
     * Success chance for a catalog mission at a given difficulty (Spec: docs/
     * superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md).
     * base_chance[$difficulty] + generic Pilot-Rang bonus + missionsspezifischer
     * Kenntnis-Bonus (nur falls die Mission ein requires.knowledge-Gate hat,
     * pro Level über dem Gate), gecappt bei chance_cap.
     */
    public function successChanceFor(int $colonyId, array $mission, string $difficulty): float
    {
        $base = (float) config("game.missions.difficulty.base_chance.{$difficulty}", 0.70);

        $pilotRank = (int) (Advisor::where('colony_id', $colonyId)
            ->where('personell_id', config('advisors.pilot.id'))
            ->value('rank') ?? 0);
        $chance = $base + $pilotRank * (float) config('game.missions.difficulty.pilot_rank_bonus_pct', 0.05);

        $gate = $mission['requires']['knowledge'] ?? null;
        if ($gate !== null) {
            [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
            $levelsAbove = max(0, $this->knowledgeLevel($colonyId, $knowledgeKey) - $requiredLevel);
            $chance += $levelsAbove * (float) config('game.missions.difficulty.knowledge_bonus_pct_per_level', 0.03);
        }

        return min((float) config('game.missions.difficulty.chance_cap', 0.95), $chance);
    }

    /**
     * Dispatch a docked ship on a catalog mission (GDD §8b).
     *
     * Validates the mission key against config/missions.php: allowed ship type,
     * knowledge gate, target requirement (player-picked tile/knowledge) and the
     * §7 SP dispatch block. Costs: nav AP = distance × nav_ap_per_sol; organika =
     * distance × per-sol rate reduced by knowledge levels above the gate (floor 1)
     * plus any mission extra_cost.
     *
     * @param  array{q?: int, r?: int, research_id?: int}|null  $target
     */
    public function dispatchShip(int $colonyId, int $instanceId, string $missionKey, ?array $target = null): void
    {
        $mission = config("missions.catalog.{$missionKey}");
        if ($mission === null) {
            throw new RuntimeException("Unknown mission key: {$missionKey}.");
        }

        DB::transaction(function () use ($colonyId, $instanceId, $missionKey, $mission, $target): void {
            $ship = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->first();

            if ($ship === null) {
                throw new RuntimeException("No ship assigned to hangar instance {$instanceId}.");
            }

            if ($ship->ship_state !== 'docked') {
                throw new RuntimeException(
                    "Ship in hangar {$instanceId} cannot be dispatched (current state: {$ship->ship_state})."
                );
            }

            $shipKey = self::SHIP_ID_TO_CONFIG_KEY[(int) $ship->ship_id] ?? null;
            if (! in_array($shipKey, $mission['ships'], true)) {
                throw new RuntimeException(__('missions.error_wrong_ship_type'));
            }

            // §7 dispatch block: worn-down ships must be repaired first.
            $minSp = self::SHIP_MAX_STATUS * (float) config('missions.dispatch_min_sp_pct', 0.25);
            if ((float) $ship->status_points < $minSp) {
                throw new RuntimeException(__('missions.error_sp_too_low'));
            }

            // Knowledge gate.
            $gate = $mission['requires']['knowledge'] ?? null;
            if ($gate !== null) {
                [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
                if ($this->knowledgeLevel($colonyId, $knowledgeKey) < $requiredLevel) {
                    throw new RuntimeException(__('missions.error_knowledge_gate'));
                }
            }

            // Harvester second-instance entitlement missions (mission_harvester_salvage,
            // GDD §4c "Harvester-Zweitinstanz: Bezugsquelle", freigegeben 2026-08-05) must
            // not be dispatchable once the colony already holds max_instances Harvesters
            // (would earn an entitlement for nothing), NOR when the owning user already
            // holds an earned-but-unplaced entitlement via any path (Weg A/B must not
            // stack — instance_count alone doesn't catch an already-bought Orin offer).
            if (($mission['reward']['harvester_instance'] ?? false) === true) {
                $harvesterInstanceCount = DB::table('colony_buildings')
                    ->where('colony_id', $colonyId)
                    ->where('building_id', BuildingId::Harvester->value)
                    ->whereNotNull('tile_x')
                    ->count();
                $maxInstances = (int) (collect(config('buildings'))->firstWhere('id', BuildingId::Harvester->value)['max_instances'] ?? 2);
                if ($harvesterInstanceCount >= $maxInstances) {
                    throw new RuntimeException(__('missions.error_harvester_instance_full'));
                }

                $ownerUserId = Colony::find($colonyId)?->user_id;
                if ($ownerUserId !== null && $this->harvesterEntitlementService->hasEntitlement($ownerUserId)) {
                    throw new RuntimeException(__('missions.error_harvester_instance_full'));
                }
            }

            // Target requirement (player-picked tile or knowledge).
            $targetJson = null;
            if (($mission['target_type'] ?? null) !== null) {
                $targetJson = $this->validateTarget($colonyId, $missionKey, $mission['target_type'], $target);
            }

            $solDistance = (int) $mission['sol_distance'];
            $baseNavApCost = $solDistance * (int) config('missions.nav_ap_per_sol', 2);
            $navApCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $baseNavApCost);
            $organikaCost = $this->organikaCostFor($colonyId, $mission);

            if (! config('game.bypass.ap_checks')
                && $this->advisorService->getAvailableActionPoints($colonyId) < $navApCost) {
                throw new RuntimeException(__('colony.hangar_dispatch_no_nav_ap'));
            }

            if (! config('game.bypass.resource_costs')) {
                $organika = (int) DB::table('colony_resources')
                    ->where('colony_id', $colonyId)->where('resource_id', 5)->value('amount');
                if ($organika < $organikaCost) {
                    throw new RuntimeException(__('colony.hangar_dispatch_no_organika'));
                }
            }

            $currentTick = $this->tickService->getTickCount();

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update(['ship_state' => 'dispatched']);

            if (! config('game.bypass.ap_checks') && $navApCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $navApCost);
            }
            if (! config('game.bypass.resource_costs') && $organikaCost > 0) {
                DB::table('colony_resources')
                    ->where('colony_id', $colonyId)->where('resource_id', 5)
                    ->update(['amount' => DB::raw("MAX(0, amount - {$organikaCost})")]);
            }

            DB::table('colony_hangar_missions')->insert([
                'colony_id' => $colonyId,
                'instance_id' => $instanceId,
                'ship_id' => $ship->ship_id,
                'destination' => $missionKey,
                'sol_distance' => $solDistance,
                'target' => $targetJson,
                'dispatch_tick' => $currentTick,
                'recall_tick' => null,
                'state' => 'active',
            ]);
        });
    }

    /**
     * Organika dispatch cost for a catalog mission: per-sol rate reduced by
     * knowledge levels above the gate (never below the floor), plus extra_cost.
     */
    public function organikaCostFor(int $colonyId, array $mission): int
    {
        $perSol = (int) config('missions.organika_per_sol', 3);
        $gate = $mission['requires']['knowledge'] ?? null;

        if ($gate !== null) {
            [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
            $levelsAbove = max(0, $this->knowledgeLevel($colonyId, $knowledgeKey) - $requiredLevel);
            $scaling = (int) config('missions.organika_scaling_per_level', 1);
            $floor = (int) config('missions.organika_floor_per_sol', 1);
            $perSol = max($floor, $perSol - $scaling * $levelsAbove);
        }

        $cost = (int) $mission['sol_distance'] * $perSol;
        $cost += (int) ($mission['extra_cost']['organics'] ?? 0);

        return $cost;
    }

    /**
     * Current level of a knowledge (config/knowledge.php key) for a colony; 0 when unresearched.
     */
    private function knowledgeLevel(int $colonyId, string $knowledgeKey): int
    {
        $researchId = (int) config("knowledge.{$knowledgeKey}.id", 0);
        if ($researchId === 0) {
            return 0;
        }

        return (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', $researchId)
            ->value('level');
    }

    /**
     * Validate the player-picked mission target and return its canonical JSON.
     *
     * signal_tile — explored tile with a hidden event, not yet deep-scanned.
     * ruin_tile   — deep-scanned event_ruin tile not yet consumed by a ruin expedition.
     * knowledge   — any knowledge id from config/knowledge.php.
     */
    private function validateTarget(int $colonyId, string $missionKey, string $targetType, ?array $target): string
    {
        if ($targetType === 'knowledge') {
            $researchId = (int) ($target['research_id'] ?? 0);
            $knownIds = collect(config('knowledge'))->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! in_array($researchId, $knownIds, true)) {
                throw new RuntimeException(__('missions.error_invalid_target'));
            }

            return json_encode(['research_id' => $researchId]);
        }

        $q = $target['q'] ?? null;
        $r = $target['r'] ?? null;
        if ($q === null || $r === null) {
            throw new RuntimeException(__('missions.error_invalid_target'));
        }

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('q', (int) $q)->where('r', (int) $r)
            ->first();

        $valid = match ($targetType) {
            'signal_tile' => $tile !== null && $tile->is_explored && $tile->event_type !== null && ! $tile->is_deep_scanned,
            'ruin_tile' => $tile !== null && $tile->is_deep_scanned && $tile->event_type === 'event_ruin',
            default => false,
        };

        if (! $valid) {
            throw new RuntimeException(__('missions.error_invalid_target'));
        }

        $targetJson = json_encode(['q' => (int) $q, 'r' => (int) $r]);

        // Non-repeatable tile missions: each tile can only be claimed once per run.
        if (($targetType === 'ruin_tile')
            && DB::table('colony_hangar_missions')
                ->where('colony_id', $colonyId)
                ->where('destination', $missionKey)
                ->whereIn('state', ['active', 'completed'])
                ->where('target', $targetJson)
                ->exists()) {
            throw new RuntimeException(__('missions.error_target_consumed'));
        }

        return $targetJson;
    }

    /**
     * Mission catalog enriched for the dispatch UI: localized texts, computed
     * costs (incl. knowledge scaling), wear forecast per allowed ship type,
     * availability state and pickable targets.
     */
    public function getMissionCatalogFor(int $colonyId): array
    {
        $navPerSol = (int) config('missions.nav_ap_per_sol', 2);
        $shipsConfig = config('ships');

        // Same for every mission this call — compute once, not per catalog entry.
        $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
        $availableOrganika = (int) (DB::table('colony_resources')
            ->where('colony_id', $colonyId)->where('resource_id', 5)->value('amount') ?? 0);
        $bypassAp = (bool) config('game.bypass.ap_checks');
        $bypassResources = (bool) config('game.bypass.resource_costs');

        $entries = [];
        foreach (config('missions.catalog', []) as $key => $mission) {
            $dist = (int) $mission['sol_distance'];
            $navApCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $dist * $navPerSol);
            $organikaCost = $this->organikaCostFor($colonyId, $mission);

            $availability = 'ok';
            $gateInfo = null;

            $gate = $mission['requires']['knowledge'] ?? null;
            if ($gate !== null) {
                [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
                $gateInfo = [
                    'knowledge' => $knowledgeKey,
                    'knowledge_label' => __("techtree.knowledge_{$knowledgeKey}"),
                    'required_level' => (int) $requiredLevel,
                    'current_level' => $this->knowledgeLevel($colonyId, $knowledgeKey),
                ];
                if ($gateInfo['current_level'] < $requiredLevel) {
                    $availability = 'missing_knowledge';
                }
            }

            $targets = null;
            $targetType = $mission['target_type'] ?? null;
            if ($targetType !== null) {
                $targets = $this->pickableTargets($colonyId, $key, $targetType);
                if ($availability === 'ok' && $targets === []) {
                    $availability = 'missing_target';
                }
            }

            if ($availability === 'ok' && ! $bypassAp && $navApCost > $availableAp) {
                $availability = 'missing_ap';
            }
            if ($availability === 'ok' && ! $bypassResources && $organikaCost > $availableOrganika) {
                $availability = 'missing_organika';
            }

            $wear = [];
            foreach ($mission['ships'] as $shipKey) {
                $wear[$shipKey] = round((float) ($shipsConfig[$shipKey]['wear_per_sol'] ?? 1.0) * 2 * $dist, 2);
            }

            $entries[] = [
                'key' => $key,
                'name' => __("missions.{$key}_name"),
                'desc' => __("missions.{$key}_desc"),
                'reward_label' => __("missions.{$key}_reward"),
                'ships' => $mission['ships'],
                'sol_distance' => $dist,
                'duration' => 2 * $dist,
                'nav_ap' => $navApCost,
                'nav_ap_available' => $availableAp,
                'organika' => $organikaCost,
                'organika_available' => $availableOrganika,
                'wear' => $wear,
                'availability' => $availability,
                'gate' => $gateInfo,
                'target_type' => $targetType,
                'targets' => $targets,
            ];
        }

        return $entries;
    }

    /**
     * Pickable targets for a mission's target_type.
     */
    private function pickableTargets(int $colonyId, string $missionKey, string $targetType): array
    {
        if ($targetType === 'knowledge') {
            $levels = DB::table('colony_researches')
                ->where('colony_id', $colonyId)
                ->pluck('level', 'research_id');

            $targets = [];
            foreach (config('knowledge', []) as $knowledgeKey => $cfg) {
                $targets[] = [
                    'research_id' => (int) $cfg['id'],
                    'label' => __("techtree.knowledge_{$knowledgeKey}"),
                    'level' => (int) ($levels[(int) $cfg['id']] ?? 0),
                ];
            }

            return $targets;
        }

        $query = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->orderBy('ring')->orderBy('q')->orderBy('r');

        if ($targetType === 'signal_tile') {
            $query->where('is_explored', 1)->whereNotNull('event_type')->where('is_deep_scanned', 0);
        } else { // ruin_tile
            $query->where('is_deep_scanned', 1)->where('event_type', 'event_ruin');
        }

        $tiles = [];
        foreach ($query->get(['q', 'r', 'ring']) as $tile) {
            $targetJson = json_encode(['q' => (int) $tile->q, 'r' => (int) $tile->r]);
            if ($targetType === 'ruin_tile'
                && DB::table('colony_hangar_missions')
                    ->where('colony_id', $colonyId)
                    ->where('destination', $missionKey)
                    ->whereIn('state', ['active', 'completed'])
                    ->where('target', $targetJson)
                    ->exists()) {
                continue; // ruin already claimed this run
            }
            $tiles[] = ['q' => (int) $tile->q, 'r' => (int) $tile->r, 'ring' => (int) $tile->ring];
        }

        return $tiles;
    }

    /**
     * Recall an active mission. Marks the mission as recalled and the ship as docked.
     */
    public function recallShip(int $colonyId, int $instanceId): void
    {
        DB::transaction(function () use ($colonyId, $instanceId): void {
            $mission = DB::table('colony_hangar_missions')
                ->where('colony_id', $colonyId)
                ->where('instance_id', $instanceId)
                ->where('state', 'active')
                ->first();

            if ($mission === null) {
                throw new RuntimeException("No active mission found for hangar instance {$instanceId}.");
            }

            $currentTick = $this->tickService->getTickCount();

            DB::table('colony_hangar_missions')
                ->where('id', $mission->id)
                ->update([
                    'recall_tick' => $currentTick,
                    'state' => 'recalled',
                ]);

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update(['ship_state' => 'docked']);
        });
    }

    /**
     * Repair a docked ship — fixed cost: one call spends 1 Construction-AP
     * (locked by the caller) and restores REPAIR_SP_PER_AP status_points,
     * capped at max. Mirrors the building-repair interaction (1 click = 1 AP).
     * ap_spend on the ship row is incremented to track total AP invested.
     */
    public function repairShip(int $colonyId, int $instanceId): void
    {
        DB::transaction(function () use ($colonyId, $instanceId): void {
            $ship = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->first();

            if ($ship === null || $ship->ship_state !== 'docked') {
                throw new RuntimeException(
                    "No docked ship found in hangar instance {$instanceId}."
                );
            }

            $current = (float) $ship->status_points;

            if ($current >= self::SHIP_MAX_STATUS) {
                throw new RuntimeException("Ship in hangar {$instanceId} is already at full status.");
            }

            $newStatus = min(self::SHIP_MAX_STATUS, $current + self::REPAIR_SP_PER_AP);

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update([
                    'status_points' => $newStatus,
                    'ap_spend' => $ship->ap_spend + 1,
                ]);
        });
    }
}
