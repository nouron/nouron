<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class HangarService
{
    private const HANGAR_BUILDING_ID  = 44;
    private const ALLOWED_SHIP_IDS    = [37, 47, 85];
    private const SHIP_MAX_STATUS     = 20; // matches buildings.max_status_points convention
    private const REPAIR_SP_PER_AP    = 2;  // status_points restored per AP spent

    public function __construct(
        private readonly TickService $tickService,
    ) {}

    /**
     * Returns all hangar slots for the colony, sorted by instance_id ascending.
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

        // Load all ships assigned to these hangar slots in one query
        $ships = DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereIn('colony_ships.hangar_instance_id', $instanceIds)
            ->get([
                'colony_ships.ship_id',
                'ships.name',
                'colony_ships.ship_state',
                'colony_ships.level',
                'colony_ships.status_points',
                'colony_ships.ap_spend',
                'colony_ships.hangar_instance_id',
            ])
            ->keyBy('hangar_instance_id')
            ->all();

        // Load active missions for all hangar slots in one query
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
                    'ship_id'        => (int) $ship->ship_id,
                    'name'           => $ship->name,
                    'ship_state'     => $ship->ship_state,
                    'level'          => (int) $ship->level,
                    'status_points'  => (float) $ship->status_points,
                    'ap_spend'       => (int) $ship->ap_spend,
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
     * Assign a ship type to an empty hangar bay. Ship starts in 'building' state.
     */
    public function buildShip(int $colonyId, int $instanceId, int $shipId): void
    {
        if (!in_array($shipId, self::ALLOWED_SHIP_IDS, true)) {
            throw new RuntimeException("Ship type {$shipId} is not assignable to a hangar.");
        }

        DB::transaction(function () use ($colonyId, $instanceId, $shipId): void {
            // Verify the hangar instance belongs to this colony
            $hangarExists = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', self::HANGAR_BUILDING_ID)
                ->where('instance_id', $instanceId)
                ->exists();

            if (!$hangarExists) {
                throw new RuntimeException("Hangar instance {$instanceId} does not exist for this colony.");
            }

            // Check the bay is not already occupied
            $slotOccupied = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('hangar_instance_id', $instanceId)
                ->exists();

            if ($slotOccupied) {
                throw new RuntimeException("Hangar instance {$instanceId} already has a ship assigned.");
            }

            // Prevent duplicate ship type across all hangars of this colony
            $shipAlreadyAssigned = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('ship_id', $shipId)
                ->whereNotNull('hangar_instance_id')
                ->exists();

            if ($shipAlreadyAssigned) {
                throw new RuntimeException("Ship type {$shipId} is already assigned to another hangar in this colony.");
            }

            DB::table('colony_ships')->insert([
                'colony_id'          => $colonyId,
                'ship_id'            => $shipId,
                'hangar_instance_id' => $instanceId,
                'ship_state'         => 'building',
                'level'              => 0,
                'status_points'      => self::SHIP_MAX_STATUS,
                'ap_spend'           => 0,
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
                'colony_id'    => $colonyId,
                'instance_id'  => $instanceId,
                'ship_id'      => $ship->ship_id,
                'destination'  => $destination,
                'sol_distance' => $solDistance,
                'dispatch_tick' => $currentTick,
                'recall_tick'  => null,
                'state'        => 'active',
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

            $restored    = $apSpent * self::REPAIR_SP_PER_AP;
            $newStatus   = min(self::SHIP_MAX_STATUS, $current + $restored);

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
