<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Models\Colony;
use App\Models\ColonyResource;
use App\Models\Resource;
use App\Models\UserResource;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * ResourcesService — Laravel port of Resources\Service\ResourcesService.
 *
 * Manages the 9 resource types (credits, supply, water, ferum, silicates,
 * ena, lho, aku, trust). Credits and supply are stored in user_resources
 * (user-level); the remaining 7 are stored in colony_resources.
 */
class ResourcesService
{
    use ValidatesId;

    /** Resource IDs that belong to the user (not the colony) */
    const RES_CREDITS = 1;

    const RES_SUPPLY = 2;

    public function __construct(private readonly ColonyService $colonyService) {}

    // ── Read ─────────────────────────────────────────────────────────────────

    public function getResources(): Collection
    {
        return Resource::all();
    }

    public function getResource(int $id): Resource|false
    {
        return Resource::find($id) ?? false;
    }

    /**
     * @param  array|null  $where  Associative WHERE conditions, e.g. ['colony_id' => 1]
     */
    public function getColonyResources(?array $where = null): Collection
    {
        $query = ColonyResource::query();
        if ($where) {
            $query->where($where);
        }

        return $query->get();
    }

    /**
     * @param  array|null  $where  Associative WHERE conditions, e.g. ['user_id' => 3]
     */
    public function getUserResources(?array $where = null): Collection
    {
        $query = UserResource::query();
        if ($where) {
            $query->where($where);
        }

        return $query->get();
    }

    /**
     * Return combined colony + user resources for a colony, keyed by resource_id.
     *
     * Result format: [ resource_id => ['resource_id' => x, 'amount' => y], ... ]
     *
     * @throws InvalidArgumentException for invalid colony IDs
     */
    public function getPossessionsByColonyId(int|string $colonyId): array
    {
        $this->validateId($colonyId);

        $colony = $this->colonyService->getColony((int) $colonyId);
        if (! $colony) {
            throw new RuntimeException("Colony {$colonyId} not found.");
        }

        // Colony resources indexed by resource_id
        $possessions = $this->getColonyResources(['colony_id' => $colonyId])
            ->keyBy('resource_id')
            ->map(fn ($r) => ['resource_id' => $r->resource_id, 'amount' => $r->amount])
            ->toArray();

        // User resources (credits + supply) — one row per user
        $userResource = $this->getUserResources(['user_id' => $colony->user_id])->first();
        if ($userResource) {
            $possessions[self::RES_CREDITS] = ['resource_id' => self::RES_CREDITS, 'amount' => $userResource->credits];
            $possessions[self::RES_SUPPLY] = ['resource_id' => self::RES_SUPPLY,  'amount' => $userResource->supply];
        }

        return $possessions;
    }

    // ── Validation ───────────────────────────────────────────────────────────

    /**
     * Check whether a colony can afford the given costs.
     *
     * Accepts any iterable of objects/arrays that expose resource_id + amount.
     */
    public function check(iterable $costs, int|string $colonyId): bool
    {
        $this->validateId($colonyId);
        $poss = $this->getPossessionsByColonyId((int) $colonyId);

        foreach ($costs as $cost) {
            $resourceId = is_array($cost) ? $cost['resource_id'] : $cost->resource_id;
            $amount = is_array($cost) ? $cost['amount'] : $cost->amount;
            $possession = $poss[$resourceId]['amount'] ?? 0;
            if ($amount > $possession) {
                return false;
            }
        }

        return true;
    }

    // ── Write ────────────────────────────────────────────────────────────────

    /**
     * Deduct all costs from the colony in a single transaction.
     *
     * @return bool true on success, false if any deduction fails
     */
    public function payCosts(iterable $costs, int|string $colonyId): bool
    {
        $this->validateId($colonyId);

        return DB::transaction(function () use ($costs, $colonyId) {
            foreach ($costs as $cost) {
                $resourceId = is_array($cost) ? $cost['resource_id'] : $cost->resource_id;
                $amount = is_array($cost) ? $cost['amount'] : $cost->amount;
                $this->decreaseAmount((int) $colonyId, (int) $resourceId, (int) $amount);
            }

            return true;
        });
    }

    /**
     * Increase a resource possession for a colony.
     *
     * Credits (1) and supply (2) are stored on the user row; all others on
     * the colony row.
     *
     * @param  bool  $forceUserResToBeColRes  If true, even credits/supply go to colony_resources
     */
    public function increaseAmount(
        int $colonyId,
        int $resId,
        int $amount,
        bool $forceUserResToBeColRes = false
    ): bool {
        $this->validateId($colonyId);
        $this->validateId($resId);

        if (in_array($resId, [self::RES_CREDITS, self::RES_SUPPLY]) && ! $forceUserResToBeColRes) {
            // User-level resource
            $colony = $this->colonyService->getColony($colonyId);
            $row = UserResource::firstOrNew(['user_id' => $colony->user_id]);
            if (! $row->exists) {
                $row->credits = 0;
                $row->supply = 0;
            }

            if ($resId === self::RES_CREDITS) {
                $row->credits += $amount;
            } else {
                $row->supply += $amount;
            }

            return (bool) $row->save();
        }

        // Colony-level resource — composite PK, use DB upsert
        $current = (int) DB::table('colony_resources')
            ->where('colony_id', $colonyId)
            ->where('resource_id', $resId)
            ->value('amount');

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $colonyId, 'resource_id' => $resId],
            ['amount' => $current + $amount]
        );

        return true;
    }

    public function decreaseAmount(int $colonyId, int $resId, int $amount): bool
    {
        return $this->increaseAmount($colonyId, $resId, -$amount);
    }

    /**
     * Calculate free supply for the user owning the given colony.
     *
     * user_resources.supply stores the supply cap (SET each tick by GameTick).
     * Free supply = cap − workplaces (building levels × supply_cost incl. the level-0
     * reserve of placed buildings, see buildingWorkplaces(), plus research usage).
     */
    public function getFreeSupply(int $colonyId): int
    {
        return $this->getSupplyBreakdown($colonyId)['free'];
    }

    /**
     * Colony's Organika need for the current Sol (GDD §14 Hunger-Mechanik, §4a).
     *
     * food_need = floor(present_colonists / supply_per_eater) — colonists who left
     * because of over-capacity do not eat (GDD §6 "Überkapazität — Konsequenzen").
     * Kept as the single source of truth for this formula — GameTick::processFoodConsumption()
     * and any reserve-floor check gated on the food buffer (e.g. Corvan's Organika-Verkauf,
     * GDD §4b/§12) must read it from here rather than re-deriving it.
     */
    public function foodNeed(int $colonyId): int
    {
        $perEater = max(1, (int) config('game.food.supply_per_eater', 4));

        return intdiv($this->colonistStatus($colonyId)['present'], $perEater);
    }

    /**
     * Colonists, homeless colonists and staffing share (GDD §6 "Überkapazität —
     * Konsequenzen", A14) — the single place for these numbers.
     *
     *   workplaces = used supply (every workplace needs one colonist), including
     *                the level-0 reserve of placed buildings (buildingWorkplaces()) —
     *                their colonists have already moved in: they need housing and eat
     *   departed   = min(glx_colonies.overcap_departed, max(0, workplaces − cap))
     *   present    = workplaces − departed
     *   homeless   = max(0, present − cap)
     *   staffing   = present / workplaces (1.0 while nobody has departed)
     *
     * The departed count is clamped live: as soon as housing is free again the
     * colonists count as returned (OvercapService::advanceStreaks() persists the
     * return and logs it at the next Sol). Free supply for the build gate stays
     * cap − workplaces — departed colonists leave unfilled workplaces that keep
     * occupying the cap (getFreeSupply()).
     *
     * @return array{cap: int, workplaces: int, departed: int, present: int, homeless: int, staffing: float}
     */
    public function colonistStatus(int $colonyId): array
    {
        $breakdown = $this->getSupplyBreakdown($colonyId);
        $cap = $breakdown['cap'];
        $workplaces = max(0, $cap - $breakdown['free']);

        $stored = (int) DB::table('glx_colonies')->where('id', $colonyId)->value('overcap_departed');
        $departed = min($stored, max(0, $workplaces - $cap));
        $present = $workplaces - $departed;

        return [
            'cap' => $cap,
            'workplaces' => $workplaces,
            'departed' => $departed,
            'present' => $present,
            'homeless' => max(0, $present - $cap),
            'staffing' => $workplaces > 0 && $departed > 0 ? $present / $workplaces : 1.0,
        ];
    }

    /**
     * Production factor from unfilled workplaces (GDD §5: produced amount × staffing
     * share). Applies to raw-material production only (Regolith, Organika).
     */
    public function staffingShare(int $colonyId): float
    {
        return $this->colonistStatus($colonyId)['staffing'];
    }

    /**
     * Breakdown of a colony's supply cap and usage, for display in the resource-bar
     * SUP chip popup (so the player can see e.g. "CC → 10, 3× Wohnhabitat → 24" and
     * where the used supply actually goes, instead of just a single opaque number).
     *
     * used.buildings includes the level-0 reserve (buildingWorkplaces()); used.reserved
     * is that share on its own, for display only (not to be added again).
     *
     * @return array{cap: int, free: int, sources: array{cc: int, housing: int, knowledge: int}, used: array{buildings: int, reserved: int, researches: int, advisors: int}}
     */
    public function getSupplyBreakdown(int $colonyId): array
    {
        $colony = $this->colonyService->getColony($colonyId);
        if (! $colony) {
            return [
                'cap' => 0, 'free' => 0,
                'sources' => ['cc' => 0, 'housing' => 0, 'knowledge' => 0],
                'used' => ['buildings' => 0, 'reserved' => 0, 'researches' => 0, 'advisors' => 0],
            ];
        }

        $userResource = $this->getUserResources(['user_id' => $colony->user_id])->first();
        $cap = $userResource ? (int) $userResource->supply : 0;

        // Cap sources — mirrors GameTick::calculateSupply() exactly (display-only,
        // does not recompute the cap; just explains the number GameTick already set).
        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::CommandCenter->value)
            ->value('level');
        $ccContribution = $ccLevel > 0 ? (int) config('buildings.commandCenter.supply_cap', 10) : 0;

        $housingLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::Housing->value)
            ->sum('level');
        $housingContribution = $housingLevel * (int) config('buildings.housingComplex.supply_cap', 8);

        $knowledgeContribution = max(0, $cap - $ccContribution - $housingContribution);

        ['total' => $usedBuildings, 'reserved' => $reservedBuildings] = $this->buildingWorkplaces($colonyId);

        $usedResearches = (int) DB::table('colony_researches as cr')
            ->join('researches as r', 'r.id', '=', 'cr.research_id')
            ->where('cr.colony_id', $colonyId)
            ->where('cr.level', '>', 0)
            ->sum(DB::raw('cr.level * COALESCE(r.supply_cost, 0)'));

        // Berater belegen KEIN Supply (GDD §6/§13, mehrfach explizit: "Berater
        // kosten ausschliesslich Credits — Supply ist nicht betroffen"). Owner-
        // Playtest-Fund 2026-08-31: dieser Wert wurde bis hier hin trotzdem als
        // Supply-Verbraucher gezählt, über einen nirgends definierten Config-
        // Key (`game.supply.cost_advisor`, reiner Fallback-Default) — echter
        // Drift zwischen Code und Design, kein beabsichtigtes Feature. Feld
        // bleibt in der Rückgabestruktur (API-Stabilität für bestehende
        // Konsumenten), ist aber immer 0.
        $usedAdvisors = 0;

        return [
            'cap' => $cap,
            'free' => $cap - ($usedBuildings + $usedResearches + $usedAdvisors),
            'sources' => ['cc' => $ccContribution, 'housing' => $housingContribution, 'knowledge' => $knowledgeContribution],
            'used' => ['buildings' => $usedBuildings, 'reserved' => $reservedBuildings, 'researches' => $usedResearches, 'advisors' => $usedAdvisors],
        ];
    }

    /**
     * Whether a colony_buildings row is placed on the map but still on level 0 —
     * it then already reserves the workplaces of its first level (GDD §6 "Supply
     * als Bau-Gate", A14 Owner decision 2026-09-24). A level-0 row without a tile
     * (seeded, never placed) reserves nothing.
     */
    public static function reservesFirstLevel(object $colonyBuilding): bool
    {
        return (int) $colonyBuilding->level === 0 && $colonyBuilding->tile_x !== null;
    }

    /**
     * Workplaces of a colony's buildings — the single place for this number (build
     * gate, supply display, colonists, onboarding trigger, colony validation):
     * level × supply_cost per building, plus supply_cost × 1 for every placed
     * building still on level 0 (reserve, see reservesFirstLevel()).
     *
     * @return array{total: int, reserved: int} reserved is the level-0 share of total
     */
    public function buildingWorkplaces(int $colonyId): array
    {
        $row = DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $colonyId)
            ->selectRaw('SUM(CASE WHEN cb.level > 0 THEN cb.level * COALESCE(b.supply_cost, 0) ELSE 0 END) as built')
            ->selectRaw('SUM(CASE WHEN cb.level = 0 AND cb.tile_x IS NOT NULL THEN COALESCE(b.supply_cost, 0) ELSE 0 END) as reserved')
            ->first();

        $built = (int) ($row->built ?? 0);
        $reserved = (int) ($row->reserved ?? 0);

        return ['total' => $built + $reserved, 'reserved' => $reserved];
    }

    /**
     * Colony IDs with homeless colonists (GDD §6 "Überkapazität"): more colonists
     * present than the cap houses. A colony whose over-capacity was resolved by
     * departure (understaffed, nobody homeless) is not included.
     *
     * @return int[]
     */
    public function getOverCapColonyIds(): array
    {
        $colonyIds = DB::table('glx_colonies')->pluck('id');

        return $colonyIds
            ->filter(fn ($id) => $this->colonistStatus((int) $id)['homeless'] > 0)
            ->values()
            ->all();
    }
}
