<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HangarService
{
    private const HANGAR_BUILDING_ID = 44;
    private const ALLOWED_SHIP_IDS   = [37, 47, 85];
    private const SHIP_MAX_STATUS    = 20; // matches buildings.max_status_points convention
    private const REPAIR_SP_PER_AP   = 2;  // status_points restored per AP spent

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
        private readonly TickService   $tickService,
        private readonly TrustService  $trustService,
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

        $instanceIds = array_map(fn($h) => $h->instance_id, $hangars);

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
            $iid  = $hangar->instance_id;
            $ship = $ships[$iid] ?? null;

            $shipData = null;
            if ($ship !== null) {
                $mission = $missions[$iid] ?? null;
                $shipData = [
                    'id'             => (int) $ship->id,
                    'ship_id'        => (int) $ship->ship_id,
                    'name'           => $ship->name,
                    'ship_state'     => $ship->ship_state,
                    'level'          => (int) $ship->level,
                    'status_points'  => (float) $ship->status_points,
                    'ap_spend'       => (int) $ship->ap_spend,
                    'deliver_at_tick' => $ship->deliver_at_tick !== null ? (int) $ship->deliver_at_tick : null,
                    'active_mission' => $mission !== null ? (array) $mission : null,
                ];
            }

            $slots[] = [
                'instance_id'   => (int) $iid,
                'hangar_level'  => (int) $hangar->level,
                'hangar_status' => (float) $hangar->status_points,
                'ship'          => $shipData,
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
            ->map(fn($row) => [
                'id'                => (int) $row->id,
                'ship_id'           => (int) $row->ship_id,
                'name'              => $row->name,
                'ship_state'        => $row->ship_state,
                'deliver_at_tick'   => $row->deliver_at_tick !== null ? (int) $row->deliver_at_tick : null,
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
     * @param int  $colonyId
     * @param int  $shipId           Must be one of ALLOWED_SHIP_IDS.
     * @param bool $useNexusCredit   Take the ship on debt (no upfront Credits).
     * @param int  $consulApSpent    AP invested by the Konsul advisor — each AP reduces
     *                               the final credit cost by CONSUL_AP_DISCOUNT Cr.
     *
     * @throws RuntimeException on validation or insufficient credit failures.
     */
    public function requestShip(
        int  $colonyId,
        int  $shipId,
        bool $useNexusCredit = false,
        int  $consulApSpent  = 0,
    ): void {
        if (!in_array($shipId, self::ALLOWED_SHIP_IDS, true)) {
            throw new RuntimeException("Ship type {$shipId} is not orderable from the Nexus.");
        }

        if ($consulApSpent < 0) {
            throw new RuntimeException("consulApSpent must be zero or positive.");
        }

        $configKey = self::SHIP_ID_TO_CONFIG_KEY[$shipId];

        $baseCost      = (int) config("ships.{$configKey}.nexus_cost", 0);
        $deliveryTicks = (int) config("ships.{$configKey}.nexus_delivery_ticks", 1);

        // Apply Konsul discount — each AP reduces cost, floor at 0 (cannot go negative).
        $discount    = $consulApSpent * self::CONSUL_AP_DISCOUNT;
        $finalCost   = max(0, $baseCost - $discount);

        $currentTick = $this->tickService->getTickCount();
        $pendingDecayTicks = (int) config('game.hangar.pending_decay_ticks', 5);

        DB::transaction(function () use (
            $colonyId, $shipId, $configKey, $useNexusCredit,
            $finalCost, $deliveryTicks, $currentTick, $pendingDecayTicks,
        ): void {
            // Resolve the user who owns this colony (needed for credit checks).
            $userId = (int) DB::table('glx_colonies')
                ->where('id', $colonyId)
                ->value('user_id');

            if ($useNexusCredit) {
                // Nexus-Kredit path: requires CC level >= minimum threshold.
                $minCcLevel = (int) config('game.hangar.nexus_credit_min_cc_level', 2);
                $ccLevel    = (int) DB::table('colony_buildings')
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
                ->when(!empty($occupiedInstanceIds), fn($q) => $q->whereNotIn('instance_id', $occupiedInstanceIds))
                ->orderBy('instance_id')
                ->value('instance_id');

            $hangarInstanceId  = $freeSlot !== null ? (int) $freeSlot : null;
            $shipState         = 'building';
            $pendingUntilTick  = null;

            if ($hangarInstanceId === null) {
                // No free hangar slot — ship enters pending state.
                $shipState        = 'pending';
                $pendingUntilTick = $currentTick + $pendingDecayTicks;
            }

            DB::table('colony_ships')->insert([
                'colony_id'          => $colonyId,
                'ship_id'            => $shipId,
                'hangar_instance_id' => $hangarInstanceId,
                'ship_state'         => $shipState,
                'level'              => 0,
                'status_points'      => self::SHIP_MAX_STATUS,
                'ap_spend'           => 0,
                'deliver_at_tick'    => $deliverAtTick,
                'pending_until_tick' => $pendingUntilTick,
            ]);
        });
    }

    /**
     * Assign a pending ship (no hangar) to a free hangar slot.
     *
     * @param int $colonyId
     * @param int $shipRowId   The auto-increment colony_ships.id (PK).
     * @param int $instanceId  Hangar instance_id in colony_buildings to assign to.
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

            if (!$hangarExists) {
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
                    'ship_state'         => 'docked',
                ]);
        });
    }

    /**
     * Dispatch a docked ship on a mission. Creates an active mission log row.
     */
    public function dispatchShip(int $colonyId, int $instanceId, string $destination, int $solDistance): void
    {
        if (trim($destination) === '') {
            throw new RuntimeException("Destination must not be empty.");
        }

        if ($solDistance < 1) {
            throw new RuntimeException("Sol distance must be at least 1.");
        }

        DB::transaction(function () use ($colonyId, $instanceId, $destination, $solDistance): void {
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

            $currentTick = $this->tickService->getTickCount();

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update(['ship_state' => 'dispatched']);

            DB::table('colony_hangar_missions')->insert([
                'colony_id'     => $colonyId,
                'instance_id'   => $instanceId,
                'ship_id'       => $ship->ship_id,
                'destination'   => $destination,
                'sol_distance'  => $solDistance,
                'dispatch_tick' => $currentTick,
                'recall_tick'   => null,
                'state'         => 'active',
            ]);
        });
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
                    'state'       => 'recalled',
                ]);

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update(['ship_state' => 'docked']);
        });
    }

    /**
     * Repair a docked ship. Each AP restores REPAIR_SP_PER_AP status_points, capped at max.
     * ap_spend on the ship row is incremented to track total AP invested.
     */
    public function repairShip(int $colonyId, int $instanceId, int $apSpent): void
    {
        if ($apSpent < 1) {
            throw new RuntimeException("At least 1 AP must be spent on repairs.");
        }

        DB::transaction(function () use ($colonyId, $instanceId, $apSpent): void {
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

            $restored  = $apSpent * self::REPAIR_SP_PER_AP;
            $newStatus = min(self::SHIP_MAX_STATUS, $current + $restored);

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->update([
                    'status_points' => $newStatus,
                    'ap_spend'      => $ship->ap_spend + $apSpent,
                ]);
        });
    }
}
