<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Exceptions\GameRuleException;
use App\Models\Advisor;
use App\Models\Colony;
use App\Models\ColonyTile;
use App\Support\HangarBays;
use App\Support\MissionReward;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HangarService
{
    private const HANGAR_BUILDING_ID = 44;

    private const ALLOWED_SHIP_IDS = [37, 47, 85];

    /**
     * Map ship DB IDs → ships.php config keys so we can read nexus_cost / nexus_delivery_ticks.
     */
    private const SHIP_ID_TO_CONFIG_KEY = [
        85 => 'drone',
        37 => 'corvette',
        47 => 'freighter',
    ];

    /** Machine code for a dispatch refused because the ship's hangar is below its class. */
    public const ERROR_SHIP_INACTIVE = 'ship_inactive';

    public function __construct(
        private readonly TickService $tickService,
        private readonly TrustService $trustService,
        private readonly AdvisorService $advisorService,
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
        private readonly ProjectBonusService $projectBonusService,
        private readonly EventService $eventService,
        private readonly FindFlightService $findFlightService,
        private readonly ColonyTileService $colonyTileService,
    ) {}

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * Apron (bay 0) plus bays 1-3 of the single hangar hall (B1, T30 §13.2); [] without a
     * hall. Pending ships (bay IS NULL) are excluded. A bay is open when the hall level
     * reaches it; each element carries the docked ship (or null).
     *
     * @return list<array{bay:int, open:bool, required_level:int, hangar_level:int, hangar_status:float, ship:?array}>
     */
    public function getHangarSlots(int $colonyId): array
    {
        $hall = DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::HANGAR_BUILDING_ID)
            ->orderBy('instance_id')
            ->first(['level', 'status_points']);
        if ($hall === null) {
            return [];
        }
        $level = (int) $hall->level;

        $ships = DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereNotNull('colony_ships.bay')
            ->get([
                'colony_ships.id', 'colony_ships.ship_id', 'ships.name', 'colony_ships.ship_state',
                'colony_ships.level', 'colony_ships.status_points', 'colony_ships.ap_spend',
                'colony_ships.bay', 'colony_ships.deliver_at_tick',
            ])
            ->keyBy('bay')
            ->all();

        $missions = DB::table('colony_hangar_missions')
            ->where('colony_id', $colonyId)
            ->where('state', 'active')
            ->get()
            ->keyBy('bay')
            ->all();

        $slots = [];
        for ($bay = HangarBays::APRON; $bay <= HangarBays::MAX_BAY; $bay++) {
            $ship = $ships[$bay] ?? null;
            $shipData = null;
            if ($ship !== null) {
                $mission = $missions[$bay] ?? null;
                if ($mission !== null) {
                    $mission = (array) $mission;
                    $mission['mission_key'] = $mission['destination'];
                    $mission['mission_name'] = __("missions.{$mission['destination']}_name");
                    $mission['return_tick'] = (int) $mission['dispatch_tick'] + 2 * (int) $mission['sol_distance'];
                }
                $inactive = HangarBays::isInactive((int) $ship->ship_id, $level, $bay);
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
                    'required_hangar_level' => $this->requiredHangarLevel((int) $ship->ship_id),
                    'readiness' => $this->readinessByDistance((int) $ship->ship_id, (float) $ship->status_points),
                    'inactive' => $inactive,
                    'inactive_reason' => $inactive ? $this->inactiveReason((int) $ship->ship_id, $bay) : null,
                ];
            }

            $slots[] = [
                'bay' => $bay,
                'open' => HangarBays::bayOpen($bay, $level),
                'required_level' => max(1, $bay),
                'hangar_level' => $level,
                'hangar_status' => (float) $hall->status_points,
                'ship' => $shipData,
            ];
        }

        return $slots;
    }

    /**
     * Returns all pending ships for this colony (no hangar bay assigned yet).
     * Joined with ships master table for display name.
     *
     * @return array<int, array{id: int, ship_id: int, name: string, ship_state: string, pending_until_tick: int|null}>
     */
    public function getPendingShips(int $colonyId): array
    {
        return DB::table('colony_ships')
            ->join('ships', 'ships.id', '=', 'colony_ships.ship_id')
            ->where('colony_ships.colony_id', $colonyId)
            ->whereNull('colony_ships.bay')
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

    /**
     * Hangar level a ship class needs (GDD §4c: the hangar level IS the ship
     * class — Lv1 Drohne, Lv2 Frachter, Lv3 Korvette).
     */
    public function requiredHangarLevel(int $shipId): int
    {
        return HangarBays::requiredLevel($shipId);
    }

    /**
     * "Hangar unter Schiffsstufe" (GDD §7): a ship whose hangar sits below its
     * class is inactive — it can't start a mission until the hangar is back on
     * that level. Derived from the two levels, never stored, so a rebuilt hangar
     * reactivates the ship by itself whatever lowered it (decay, storm, Rückbau).
     */
    public function isShipInactive(int $shipId, int $hangarLevel, int $bay): bool
    {
        return HangarBays::isInactive($shipId, $hangarLevel, $bay);
    }

    /**
     * Row ids (colony_ships.id) of this colony's hangar-assigned ships that are
     * currently inactive (isShipInactive()).
     *
     * @return array<int, int>
     */
    public function inactiveShipRowIds(int $colonyId): array
    {
        $level = $this->hangarMaxLevel($colonyId);

        return DB::table('colony_ships')
            ->where('colony_id', $colonyId)
            ->whereNotNull('bay')
            ->get(['id', 'ship_id', 'bay'])
            ->filter(fn (object $row): bool => HangarBays::isInactive((int) $row->ship_id, $level, (int) $row->bay))
            ->map(fn (object $row): int => (int) $row->id)
            ->values()
            ->all();
    }

    /**
     * Player-facing rule violation (T25): a stable snake_case code for the
     * client plus the translated message. Without an explicit key the message
     * comes from colony.hangar_error_{code}.
     *
     * @param  array<string, mixed>  $replace
     */
    private function ruleError(string $code, ?string $langKey = null, array $replace = []): GameRuleException
    {
        return new GameRuleException($code, __($langKey ?? "colony.hangar_error_{$code}", $replace));
    }

    private function inactiveReason(int $shipId, int $bay = 1): string
    {
        return __('colony.hangar_ship_inactive', ['level' => max($this->requiredHangarLevel($shipId), $bay, 1)]);
    }

    /**
     * T30 §13.2: the gift drone on the apron (bay 0). Idempotent — only when the hall
     * stands on level >= 1 and the apron is empty. Ships on the apron are never deleted
     * (only 'pending' ships decay, GameTick::processHangarDeliveries), so a hall
     * demolished to 0 and rebuilt never yields a second one. No Credits, debt or trust.
     */
    public function ensureGiftDrone(int $colonyId): bool
    {
        if ($this->hangarMaxLevel($colonyId) < 1) {
            return false;
        }

        return DB::transaction(function () use ($colonyId): bool {
            if (DB::table('colony_ships')->where('colony_id', $colonyId)->where('bay', HangarBays::APRON)->lockForUpdate()->exists()) {
                return false;
            }

            $tick = $this->tickService->getTickCount();
            DB::table('colony_ships')->insert([
                'colony_id' => $colonyId,
                'ship_id' => (int) config('ships.drone.id'),
                'bay' => HangarBays::APRON,
                'ship_state' => 'docked',
                'level' => 0,
                'status_points' => HangarBays::maxStatus(),
                'ap_spend' => 0,
                'deliver_at_tick' => $tick,
                'pending_until_tick' => null,
            ]);

            $this->eventService->createEvent([
                'user' => (int) DB::table('glx_colonies')->where('id', $colonyId)->value('user_id'),
                'tick' => $tick,
                'event' => 'hangar.gift_drone',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colonyId]),
            ]);

            return true;
        });
    }

    /**
     * Highest hangar building level in the colony (0 when no hangar is built),
     * which gates which ship classes are orderable from the Nexus.
     */
    public function hangarMaxLevel(int $colonyId): int
    {
        return (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', self::HANGAR_BUILDING_ID)
            ->max('level') ?? 0);
    }

    /** First free bay 1..hall level (the apron is never assigned automatically), or null. */
    public function freeBay(int $colonyId): ?int
    {
        $level = min($this->hangarMaxLevel($colonyId), HangarBays::MAX_BAY);
        $occupied = DB::table('colony_ships')->where('colony_id', $colonyId)->whereNotNull('bay')
            ->pluck('bay')->map(fn ($b) => (int) $b)->all();
        for ($bay = 1; $bay <= $level; $bay++) {
            if (! in_array($bay, $occupied, true)) {
                return $bay;
            }
        }

        return null;
    }

    /**
     * Rank of the assigned, available (not on a mission) Konsul (trader advisor);
     * 0 when none is assigned or he is unavailable. Same source as
     * BarService::traderRank() — kept here because BarService depends on this
     * service (no circular injection).
     */
    public function consulRank(int $colonyId): int
    {
        return (int) (DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', (int) config('advisors.trader.id', 92))
            ->whereNull('unavailable_until_tick')
            ->value('rank') ?? 0);
    }

    /**
     * Credits discount per AP invested in the Konsul negotiation, scaled by the
     * Konsul's rank (config game.hangar.consul_ap_discount). 0 without a Konsul.
     * Single source for both the request execution and the hangar UI preview.
     */
    public function consulApDiscountPerAp(int $colonyId): int
    {
        $rank = $this->consulRank($colonyId);

        return $rank > 0 ? (int) (config('game.hangar.consul_ap_discount')[$rank] ?? 0) : 0;
    }

    /**
     * Highest useful negotiation AP for a ship — the AP amount that drives its
     * price to 0. 0 when no Konsul is available or the ship is unknown.
     */
    public function maxConsulAp(int $colonyId, int $shipId): int
    {
        $perAp = $this->consulApDiscountPerAp($colonyId);
        $configKey = self::SHIP_ID_TO_CONFIG_KEY[$shipId] ?? null;
        if ($perAp <= 0 || $configKey === null) {
            return 0;
        }

        return (int) ceil((int) config("ships.{$configKey}.nexus_cost", 0) / $perAp);
    }

    /**
     * Data for the Nexus request modal: per ship the base cost, delivery ticks and
     * max useful negotiation AP, plus the Konsul discount per AP. Uses the same
     * helpers as requestShip() so preview and execution cannot diverge.
     *
     * @return array{consul_ap_discount: int, ships: array<int, array{cost: int, delivery_ticks: int, max_consul_ap: int}>}
     */
    public function getShipRequestCatalog(int $colonyId): array
    {
        $ships = [];
        foreach (self::SHIP_ID_TO_CONFIG_KEY as $shipId => $configKey) {
            $ships[$shipId] = [
                'cost' => (int) config("ships.{$configKey}.nexus_cost", 0),
                'delivery_ticks' => (int) config("ships.{$configKey}.nexus_delivery_ticks", 1),
                'max_consul_ap' => $this->maxConsulAp($colonyId, $shipId),
            ];
        }

        return [
            'consul_ap_discount' => $this->consulApDiscountPerAp($colonyId),
            'ships' => $ships,
        ];
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    /**
     * Request a ship from the Nexus.
     *
     * The ship is created immediately in colony_ships with ship_state = 'building'.
     * It transitions to 'docked' when the tick service delivers it (via processHangarDeliveries).
     *
     * If no free hangar bay is available the ship is created with ship_state = 'pending'
     * and bay = NULL. A pending ship decays (deleted) after
     * config('game.hangar.pending_decay_ticks') ticks unless assigned via assignToHangar().
     *
     * @param  int  $shipId  Must be one of ALLOWED_SHIP_IDS.
     * @param  bool  $useNexusCredit  Take the ship on debt (no upfront Credits).
     * @param  int  $consulApSpent  AP invested by the Konsul advisor — each AP reduces
     *                              the final credit cost by consulApDiscountPerAp() Cr
     *                              (rank-scaled). Requires an available Konsul when > 0.
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
            throw $this->ruleError('ship_not_orderable');
        }

        $requiredLevel = HangarBays::requiredLevel($shipId);
        if ($this->hangarMaxLevel($colonyId) < $requiredLevel) {
            throw $this->ruleError('hangar_level_too_low', 'colony.hangar_request_level_too_low');
        }

        if ($consulApSpent < 0) {
            throw $this->ruleError('invalid_consul_ap');
        }

        if ($consulApSpent > 0 && $this->consulRank($colonyId) < 1) {
            throw $this->ruleError('consul_required', 'colony.hangar_consul_required');
        }

        if ($consulApSpent > 0 && ! config('game.bypass.ap_checks')) {
            $availableAp = $this->advisorService->getAvailableActionPoints($colonyId);
            if ($consulApSpent > $availableAp) {
                throw $this->ruleError('insufficient_ap', null, ['requested' => $consulApSpent, 'available' => $availableAp]);
            }
        }

        $configKey = self::SHIP_ID_TO_CONFIG_KEY[$shipId];

        $baseCost = (int) config("ships.{$configKey}.nexus_cost", 0);
        $deliveryTicks = (int) config("ships.{$configKey}.nexus_delivery_ticks", 1);

        // Apply Konsul discount — each AP reduces cost (rank-scaled), floor at 0.
        $discount = $consulApSpent * $this->consulApDiscountPerAp($colonyId);
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
                    throw $this->ruleError('nexus_credit_cc_level', null, ['level' => $minCcLevel, 'current' => $ccLevel]);
                }

                // Apply trust penalty (fire one-shot trust event).
                $this->trustService->fireEvent($colonyId, 'nexus_credit', $currentTick);

                // Track Nexus debt on the run record.
                // nexus_debt lives on the runs table (not user_resources).
                // Increment the active run's nexus_debt by the ship's base cost.
                // NOTE: If no active run is found (edge case), debt tracking is skipped —
                // the trust penalty still applies as a soft consequence.
                $run = DB::table('runs')
                    ->where('user_id', $userId)
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
                    throw $this->ruleError('insufficient_credits', null, ['need' => $finalCost, 'have' => $credits]);
                }

                if ($finalCost > 0) {
                    DB::table('user_resources')
                        ->where('user_id', $userId)
                        ->decrement('credits', $finalCost);
                }

                $deliverAtTick = $currentTick + $deliveryTicks;
            }

            // First free bay of the single hall (never the apron).
            $bay = $this->freeBay($colonyId);
            $shipState = 'building';
            $pendingUntilTick = null;

            if ($bay === null) {
                // No free hangar bay — ship enters pending state.
                $shipState = 'pending';
                $pendingUntilTick = $currentTick + $pendingDecayTicks;
            }

            DB::table('colony_ships')->insert([
                'colony_id' => $colonyId,
                'ship_id' => $shipId,
                'bay' => $bay,
                'ship_state' => $shipState,
                'level' => 0,
                'status_points' => HangarBays::maxStatus(),
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
     * Grant a ship at no Credits cost and without touching Nexus-Kredit debt or
     * Trust (A41, Dax/smuggler concern "unofficial delivery"). Mirrors the ship
     * creation half of requestShip() — free hangar bay lookup, colony_ships
     * insert, delivery scheduling — but skips the cost/debt/trust branches
     * entirely, since this ship was never actually ordered through the Nexus.
     *
     * Deliberately does NOT enforce the hangar class level (HangarBays::requiredLevel) — Dax's
     * delivery is off the books, not a catalog purchase; if no hangar bay is
     * free (or no hangar exists yet) the ship simply enters 'pending' state
     * like any other delivery with no free slot.
     *
     * @throws RuntimeException if $shipId is not a known orderable ship type.
     */
    public function grantFreeShip(int $colonyId, int $shipId): void
    {
        if (! in_array($shipId, self::ALLOWED_SHIP_IDS, true)) {
            throw $this->ruleError('ship_not_orderable');
        }

        $configKey = self::SHIP_ID_TO_CONFIG_KEY[$shipId];
        $deliveryTicks = (int) config("ships.{$configKey}.nexus_delivery_ticks", 1);
        $currentTick = $this->tickService->getTickCount();
        $pendingDecayTicks = (int) config('game.hangar.pending_decay_ticks', 5);

        DB::transaction(function () use ($colonyId, $shipId, $deliveryTicks, $currentTick, $pendingDecayTicks): void {
            $bay = $this->freeBay($colonyId);
            $shipState = 'building';
            $pendingUntilTick = null;

            if ($bay === null) {
                $shipState = 'pending';
                $pendingUntilTick = $currentTick + $pendingDecayTicks;
            }

            DB::table('colony_ships')->insert([
                'colony_id' => $colonyId,
                'ship_id' => $shipId,
                'bay' => $bay,
                'ship_state' => $shipState,
                'level' => 0,
                'status_points' => HangarBays::maxStatus(),
                'ap_spend' => 0,
                'deliver_at_tick' => $currentTick + $deliveryTicks,
                'pending_until_tick' => $pendingUntilTick,
            ]);
        });
    }

    /**
     * Assign a pending ship (no bay) to a free bay 1..hall level. The apron (bay 0) is
     * reserved for the gift drone.
     *
     * @param  int  $shipRowId  The auto-increment colony_ships.id (PK).
     *
     * @throws GameRuleException if the ship row or bay is not suitable.
     */
    public function assignToHangar(int $colonyId, int $shipRowId, int $bay): void
    {
        if ($bay === HangarBays::APRON) {
            throw $this->ruleError('bay_reserved');
        }
        DB::transaction(function () use ($colonyId, $shipRowId, $bay): void {
            $ship = DB::table('colony_ships')->where('id', $shipRowId)->where('colony_id', $colonyId)->whereNull('bay')->first();
            if ($ship === null) {
                throw $this->ruleError('ship_not_pending');
            }
            if ($bay < 1 || ! HangarBays::bayOpen($bay, $this->hangarMaxLevel($colonyId)) || $bay > HangarBays::MAX_BAY) {
                throw $this->ruleError('bay_closed');
            }
            if (DB::table('colony_ships')->where('colony_id', $colonyId)->where('bay', $bay)->exists()) {
                throw $this->ruleError('hangar_occupied');
            }
            DB::table('colony_ships')->where('id', $shipRowId)
                ->update(['bay' => $bay, 'pending_until_tick' => null, 'ship_state' => 'docked']);
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
    public function dispatchShip(int $colonyId, int $bay, string $missionKey, ?array $target = null, string $difficulty = 'normal'): void
    {
        $mission = config("missions.catalog.{$missionKey}");
        if ($mission === null) {
            throw $this->ruleError('unknown_mission');
        }

        if (! in_array($difficulty, $mission['difficulties'] ?? [], true)) {
            throw $this->ruleError('invalid_difficulty', 'missions.error_invalid_difficulty');
        }

        DB::transaction(function () use ($colonyId, $bay, $missionKey, $mission, $target, $difficulty): void {
            $ship = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('bay', $bay)
                ->lockForUpdate()->first();

            if ($ship === null) {
                throw $this->ruleError('no_ship_in_hangar');
            }

            if ($ship->ship_state !== 'docked') {
                throw $this->ruleError('ship_not_docked');
            }

            // GDD §7 "Hangar unter Schiffsstufe": checked only here, at mission
            // start — a mission already under way is never aborted by it.
            $hangarLevel = $this->hangarMaxLevel($colonyId);
            if ($this->isShipInactive((int) $ship->ship_id, $hangarLevel, $bay)) {
                throw new GameRuleException(self::ERROR_SHIP_INACTIVE, $this->inactiveReason((int) $ship->ship_id, $bay));
            }

            $shipKey = self::SHIP_ID_TO_CONFIG_KEY[(int) $ship->ship_id] ?? null;
            if (! in_array($shipKey, $mission['ships'], true)) {
                throw $this->ruleError('wrong_ship_type', 'missions.error_wrong_ship_type');
            }

            // Knowledge gate.
            $gate = $mission['requires']['knowledge'] ?? null;
            if ($gate !== null) {
                [$knowledgeKey, $requiredLevel] = [array_key_first($gate), reset($gate)];
                if ($this->knowledgeLevel($colonyId, $knowledgeKey) < $requiredLevel) {
                    throw $this->ruleError('knowledge_gate', 'missions.error_knowledge_gate');
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
                    throw $this->ruleError('harvester_instance_full', 'missions.error_harvester_instance_full');
                }

                $ownerUserId = Colony::find($colonyId)?->user_id;
                if ($ownerUserId !== null && $this->harvesterEntitlementService->hasEntitlement($ownerUserId)) {
                    throw $this->ruleError('harvester_instance_full', 'missions.error_harvester_instance_full');
                }
            }

            // Target requirement (player-picked tile or knowledge).
            $targetJson = null;
            $solDistance = (int) ($mission['sol_distance'] ?? 0);
            $targetType = $mission['target_type'] ?? null;
            if ($targetType === 'find_tile') {
                // T30 §13.3: the locked tile row reserves the find; distance comes from its ring.
                $tile = DB::table('colony_tiles')->where('colony_id', $colonyId)
                    ->where('q', (int) ($target['q'] ?? PHP_INT_MIN))->where('r', (int) ($target['r'] ?? PHP_INT_MIN))
                    ->lockForUpdate()->first();
                $this->assertFindFlyable($colonyId, $tile, $shipKey);
                $targetJson = json_encode(['q' => (int) $tile->q, 'r' => (int) $tile->r]);
                $solDistance = $this->findFlightService->solDistance((int) $tile->ring);
            } elseif ($targetType !== null) {
                $targetJson = $this->validateTarget($colonyId, $missionKey, $targetType, $target);
            }

            // T30 §13.x/7: return guarantee instead of the 25 % floor — a ship never
            // starts a flight it cannot come back from.
            $readiness = HangarBays::flightReadiness((int) $ship->ship_id, (float) $ship->status_points, $solDistance);
            if (! $readiness['ready']) {
                throw $this->ruleError('ship_too_worn', 'missions.error_ship_too_worn', [
                    'current' => (float) $ship->status_points, 'wear' => $readiness['wear'], 'ap' => $readiness['repair_ap'],
                ]);
            }
            $baseNavApCost = $solDistance * (int) config('missions.nav_ap_per_sol', 2);
            $navApCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $baseNavApCost);
            $organikaCost = $this->organikaCostFor($colonyId, $mission, $solDistance);

            if (! config('game.bypass.ap_checks')
                && $this->advisorService->getAvailableActionPoints($colonyId) < $navApCost) {
                throw $this->ruleError('no_nav_ap', 'colony.hangar_dispatch_no_nav_ap');
            }

            if (! config('game.bypass.resource_costs')) {
                $organika = (int) DB::table('colony_resources')
                    ->where('colony_id', $colonyId)->where('resource_id', 5)->value('amount');
                if ($organika < $organikaCost) {
                    throw $this->ruleError('no_organika', 'colony.hangar_dispatch_no_organika');
                }
            }

            $currentTick = $this->tickService->getTickCount();

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('bay', $bay)
                ->update(['ship_state' => 'dispatched']);

            if (! config('game.bypass.ap_checks') && $navApCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $navApCost);
            }
            if (! config('game.bypass.resource_costs') && $organikaCost > 0) {
                DB::table('colony_resources')
                    ->where('colony_id', $colonyId)->where('resource_id', 5)
                    ->update(['amount' => DB::raw("CASE WHEN amount > {$organikaCost} THEN amount - {$organikaCost} ELSE 0 END")]);
            }

            DB::table('colony_hangar_missions')->insert([
                'colony_id' => $colonyId,
                'bay' => $bay,
                'ship_id' => $ship->ship_id,
                'destination' => $missionKey,
                'sol_distance' => $solDistance,
                'target' => $targetJson,
                'difficulty' => $difficulty,
                'dispatch_tick' => $currentTick,
                'recall_tick' => null,
                'state' => 'active',
            ]);
        });
    }

    /**
     * Organika dispatch cost for a catalog mission: per-sol rate reduced by
     * knowledge levels above the gate (never below the floor), plus extra_cost.
     * $solDistance overrides the catalog distance (salvage flight: from the target ring).
     */
    public function organikaCostFor(int $colonyId, array $mission, ?int $solDistance = null): int
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

        $cost = ($solDistance ?? (int) $mission['sol_distance']) * $perSol;
        $cost += (int) ($mission['extra_cost']['organics'] ?? 0);

        return $cost;
    }

    /**
     * Tile-level gates of a salvage flight (§13.3). The tile row is locked by the caller,
     * so a second dispatch to the same find waits and then sees the active mission.
     */
    private function assertFindFlyable(int $colonyId, ?object $tile, ?string $shipKey): void
    {
        if ($tile === null || $tile->event_type === null) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
        }
        // T30 §13.4: findAction() is the single source; ship status/level reasons are
        // left to the bay, carry and return-guarantee checks of dispatchShip().
        match ($this->colonyTileService->findAction($colonyId, $tile)['reason']) {
            'unscanned' => throw $this->ruleError('find_not_scanned', 'missions.error_find_not_scanned'),
            'false' => throw $this->ruleError('invalid_target', 'missions.error_invalid_target'),
            'in_flight' => throw $this->ruleError('find_in_flight', 'missions.error_find_in_flight'),
            'grandfathered', 'self' => throw $this->ruleError('find_salvage_in_progress', 'missions.error_find_salvage_in_progress'),
            default => null,
        };
        if (! $this->findFlightService->canCarry($shipKey, $tile->event_type)) {
            throw $this->ruleError('find_too_large', 'missions.error_find_too_large');
        }
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
     * ruin_tile   — deep-scanned event_ruin tile not yet consumed by a ruin expedition.
     * knowledge   — any knowledge id from config/knowledge.php.
     */
    private function validateTarget(int $colonyId, string $missionKey, string $targetType, ?array $target): string
    {
        if ($targetType === 'knowledge') {
            $researchId = (int) ($target['research_id'] ?? 0);
            $knownIds = collect(config('knowledge'))->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! in_array($researchId, $knownIds, true)) {
                throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
            }

            return json_encode(['research_id' => $researchId]);
        }

        $q = $target['q'] ?? null;
        $r = $target['r'] ?? null;
        if ($q === null || $r === null) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
        }

        $tile = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('q', (int) $q)->where('r', (int) $r)
            ->first();

        $valid = match ($targetType) {
            'ruin_tile' => $tile !== null && $tile->is_deep_scanned && $tile->event_type === 'event_ruin',
            default => false,
        };

        if (! $valid) {
            throw $this->ruleError('invalid_target', 'missions.error_invalid_target');
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
            throw $this->ruleError('target_consumed', 'missions.error_target_consumed');
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
            $dist = (int) ($mission['sol_distance'] ?? 0);

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
                if ($targetType === 'find_tile') {
                    // Header costs show the nearest pickable find; each target carries its own.
                    $dist = $targets === [] ? $this->findFlightService->solDistance(2) : min(array_column($targets, 'sol_distance'));
                }
            }

            $navApCost = $this->projectBonusService->effectiveNavigationApCost($colonyId, $dist * $navPerSol);
            $organikaCost = $this->organikaCostFor($colonyId, $mission, $dist);

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

            // T30 §13.3: roll === false (salvage flight) — no success roll, no difficulty scaling.
            $rolls = ($mission['roll'] ?? true) !== false;
            $difficultyOptions = [];
            foreach ($mission['difficulties'] ?? [] as $difficultyKey) {
                $difficultyOptions[] = [
                    'key' => $difficultyKey,
                    'label' => __("missions.difficulty_{$difficultyKey}"),
                    'chance_pct' => $rolls ? (int) round($this->successChanceFor($colonyId, $mission, $difficultyKey) * 100) : 100,
                    'reward_multiplier' => $rolls ? (float) config("game.missions.difficulty.reward_multiplier.{$difficultyKey}", 1.0) : 1.0,
                ];
            }

            $entries[] = [
                'key' => $key,
                'name' => __("missions.{$key}_name"),
                'desc' => __("missions.{$key}_desc"),
                'reward_label' => MissionReward::label($mission['reward']),
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
                'difficulty_options' => $difficultyOptions,
                'roll' => $rolls,
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

        if ($targetType === 'find_tile') {
            $tick = $this->tickService->getTickCount();
            $mission = config('missions.catalog.'.FindFlightService::MISSION_KEY);

            return DB::table('colony_tiles')
                ->where('colony_id', $colonyId)->where('is_deep_scanned', 1)
                ->whereIn('event_type', ColonyTileService::FIND_TYPES)->where('salvage_ap_spent', 0)
                ->orderBy('ring')->orderBy('q')->orderBy('r')
                ->get()
                ->reject(fn ($t) => $this->colonyTileService->findFlightReturnSol($colonyId, (int) $t->q, (int) $t->r) !== null)
                ->map(function ($t) use ($colonyId, $tick, $mission) {
                    $costs = $this->findFlightService->costs($colonyId, (int) $t->ring);
                    $carry = [];
                    foreach ($mission['ships'] as $shipKey) {
                        $carry[$shipKey] = $this->findFlightService->canCarry($shipKey, $t->event_type);
                    }

                    return ['q' => (int) $t->q, 'r' => (int) $t->r, 'ring' => (int) $t->ring, 'find_type' => $t->event_type,
                        ...$costs, 'return_sol' => $tick + $costs['duration'], 'carry' => $carry,
                        'yield' => $this->findFlightService->findFlightYield($colonyId, $t->event_type)];
                })
                ->values()
                ->all();
        }

        if ($targetType !== 'ruin_tile') {
            return [];
        }

        // ruin_tile: deep-scanned ruins not yet claimed by this mission.
        $query = DB::table('colony_tiles')
            ->where('colony_id', $colonyId)
            ->where('is_deep_scanned', 1)->where('event_type', 'event_ruin')
            ->orderBy('ring')->orderBy('q')->orderBy('r');

        $tiles = [];
        foreach ($query->get(['q', 'r', 'ring']) as $tile) {
            $targetJson = json_encode(['q' => (int) $tile->q, 'r' => (int) $tile->r]);
            if (DB::table('colony_hangar_missions')
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
     * Tick payout of a salvage flight (§13.3). Defensive: a tile that is no longer a
     * scanned real find, or a ship that cannot carry it, pays nothing (find_missing).
     *
     * @return array{find_type: ?string, find_regolith: int, flight_bonus: int, find_missing?: bool}
     */
    public function completeFindFlight(int $colonyId, ?array $target, int $shipId): array
    {
        $none = ['find_type' => null, 'find_regolith' => 0, 'flight_bonus' => 0, 'find_missing' => true];
        if (! isset($target['q'], $target['r'])) {
            return $none;
        }

        return DB::transaction(function () use ($colonyId, $target, $shipId, $none): array {
            $tile = ColonyTile::where('colony_id', $colonyId)->where('q', (int) $target['q'])->where('r', (int) $target['r'])
                ->lockForUpdate()->first();
            $type = $tile?->event_type;
            if ($tile === null || ! $tile->is_deep_scanned || ! in_array($type, ColonyTileService::FIND_TYPES, true)
                || ! $this->findFlightService->canCarry(HangarBays::shipKey($shipId), $type)) {
                return $none;
            }
            $yield = $this->findFlightService->findFlightYield($colonyId, $type);
            $this->colonyTileService->completeFind($tile, $yield['total']);

            return ['find_type' => $type, 'find_regolith' => $yield['total'], 'flight_bonus' => $yield['flight_bonus']];
        });
    }

    /**
     * Recall an active mission. Marks the mission as recalled and the ship as docked.
     */
    public function recallShip(int $colonyId, int $bay): void
    {
        DB::transaction(function () use ($colonyId, $bay): void {
            $mission = DB::table('colony_hangar_missions')
                ->where('colony_id', $colonyId)
                ->where('bay', $bay)
                ->where('state', 'active')
                ->first();

            if ($mission === null) {
                throw $this->ruleError('no_active_mission');
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
                ->where('bay', $bay)
                ->update(['ship_state' => 'docked']);
        });
    }

    /**
     * Repair a docked ship — fixed cost: one call spends ap_per_click Construction-AP
     * (locked by the caller) and restores game.ship_repair.sp_per_click status_points,
     * capped at game.ship_repair.max_status_points; AP per click is game.ship_repair.ap_per_click.
     * ap_spend on the ship row is incremented to track total AP invested.
     */
    public function repairShip(int $colonyId, int $bay): void
    {
        DB::transaction(function () use ($colonyId, $bay): void {
            $ship = DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('bay', $bay)
                ->lockForUpdate()->first();

            if ($ship === null || $ship->ship_state !== 'docked') {
                throw $this->ruleError('no_docked_ship');
            }

            $current = (float) $ship->status_points;

            $max = HangarBays::maxStatus();
            if ($current >= $max) {
                throw $this->ruleError('ship_full_status');
            }

            $newStatus = min($max, $current + (int) config('game.ship_repair.sp_per_click', 2));

            DB::table('colony_ships')
                ->where('colony_id', $colonyId)
                ->where('bay', $bay)
                ->update([
                    'status_points' => $newStatus,
                    'ap_spend' => $ship->ap_spend + (int) config('game.ship_repair.ap_per_click', 1),
                ]);
        });
    }

    /** flightReadiness() for every distance a catalog mission can have — the dialog reads it, never recomputes. */
    private function readinessByDistance(int $shipId, float $statusPoints): array
    {
        $distances = [];
        foreach (config('missions.catalog', []) as $mission) {
            foreach ([$mission['sol_distance'] ?? null, ...array_values($mission['sol_distance_by_ring'] ?? [])] as $distance) {
                if ($distance !== null) {
                    $distances[(int) $distance] = true;
                }
            }
        }
        ksort($distances);

        $readiness = [];
        foreach (array_keys($distances) as $distance) {
            $readiness[$distance] = HangarBays::flightReadiness($shipId, $statusPoints, $distance);
        }

        return $readiness;
    }
}
