<?php

namespace App\Services;

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
 * Manages the 5 resource types (Credits, Supply, Compounds, Organics, Moral).
 * Credits and Versorgung are stored in user_resources (user-level);
 * the remaining 3 (Compounds (ID 4), Organics (ID 5), Moral ID 12) are stored in colony_resources.
 */
class ResourcesService
{
    use ValidatesId;

    /** Resource IDs that belong to the user (not the colony) */
    const RES_CREDITS = 1;
    const RES_SUPPLY  = 2;

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
     * @param  array|null $where  Associative WHERE conditions, e.g. ['colony_id' => 1]
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
     * @param  array|null $where  Associative WHERE conditions, e.g. ['user_id' => 3]
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
        if (!$colony) {
            throw new RuntimeException("Colony {$colonyId} not found.");
        }

        // Colony resources indexed by resource_id
        $possessions = $this->getColonyResources(['colony_id' => $colonyId])
            ->keyBy('resource_id')
            ->map(fn($r) => ['resource_id' => $r->resource_id, 'amount' => $r->amount])
            ->toArray();

        // User resources (credits + supply) — one row per user
        $userResource = $this->getUserResources(['user_id' => $colony->user_id])->first();
        if ($userResource) {
            $possessions[self::RES_CREDITS] = ['resource_id' => self::RES_CREDITS, 'amount' => $userResource->credits];
            $possessions[self::RES_SUPPLY]  = ['resource_id' => self::RES_SUPPLY,  'amount' => $userResource->supply];
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
            $amount     = is_array($cost) ? $cost['amount']      : $cost->amount;
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
     * @return bool  true on success, false if any deduction fails
     */
    public function payCosts(iterable $costs, int|string $colonyId): bool
    {
        $this->validateId($colonyId);

        return DB::transaction(function () use ($costs, $colonyId) {
            foreach ($costs as $cost) {
                $resourceId = is_array($cost) ? $cost['resource_id'] : $cost->resource_id;
                $amount     = is_array($cost) ? $cost['amount']      : $cost->amount;
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
     * @param bool $forceUserResToBeColRes  If true, even credits/supply go to colony_resources
     */
    public function increaseAmount(
        int $colonyId,
        int $resId,
        int $amount,
        bool $forceUserResToBeColRes = false
    ): bool {
        $this->validateId($colonyId);
        $this->validateId($resId);

        if (in_array($resId, [self::RES_CREDITS, self::RES_SUPPLY]) && !$forceUserResToBeColRes) {
            // User-level resource
            $colony = $this->colonyService->getColony($colonyId);
            $row = UserResource::firstOrNew(['user_id' => $colony->user_id]);
            if (!$row->exists) {
                $row->credits = 0;
                $row->supply  = 0;
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
     * Free supply = cap − Σ(active entity levels × supply_cost).
     */
    public function getFreeSupply(int $colonyId): int
    {
        $colony = $this->colonyService->getColony($colonyId);
        if (!$colony) {
            return 0;
        }

        $userResource = $this->getUserResources(['user_id' => $colony->user_id])->first();
        $cap = $userResource ? (int) $userResource->supply : 0;

        $usedBuildings = (int) DB::table('colony_buildings as cb')
            ->join('buildings as b', 'b.id', '=', 'cb.building_id')
            ->where('cb.colony_id', $colonyId)
            ->where('cb.level', '>', 0)
            ->sum(DB::raw('cb.level * COALESCE(b.supply_cost, 0)'));

        $usedShips = (int) DB::table('colony_ships as cs')
            ->join('ships as s', 's.id', '=', 'cs.ship_id')
            ->where('cs.colony_id', $colonyId)
            ->where('cs.level', '>', 0)
            ->sum(DB::raw('cs.level * COALESCE(s.supply_cost, 0)'));

        $usedResearches = (int) DB::table('colony_knowledge as cr')
            ->join('knowledge as r', 'r.id', '=', 'cr.research_id')
            ->where('cr.colony_id', $colonyId)
            ->where('cr.level', '>', 0)
            ->sum(DB::raw('cr.level * COALESCE(r.supply_cost, 0)'));

        $advisorCount = (int) DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->count();
        $usedAdvisors = $advisorCount * (int) config('game.supply.cost_advisor', 2);

        $used = $usedBuildings + $usedShips + $usedResearches + $usedAdvisors;

        return $cap - $used;
    }

    /**
     * Return all colony IDs where the owning user has consumed more supply than their cap.
     *
     * A negative free-supply value indicates over-cap status. These colonies receive
     * the overcap_factor decay penalty every tick until supply usage drops back within cap.
     *
     * @return int[]  Colony IDs with getFreeSupply() < 0
     */
    public function getOverCapColonyIds(): array
    {
        $colonyIds = DB::table('glx_colonies')->pluck('id');

        return $colonyIds
            ->filter(fn($id) => $this->getFreeSupply((int) $id) < 0)
            ->values()
            ->all();
    }
}
