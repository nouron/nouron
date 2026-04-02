<?php

namespace App\Services;

use App\Models\TradeResearchView;
use App\Models\TradeResourceView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TradeGateway — manages trade offers for resources and researches.
 *
 * All mutating operations
 * (add/remove) require a valid user_id and colony ownership check to prevent
 * players from manipulating offers belonging to other users.
 *
 * Direction convention: 0 = buy offer, 1 = sell offer.
 *
 * Read operations query the enriched views (v_trade_resources, v_trade_researches)
 * so callers receive colony name + username alongside the offer data.
 *
 * Write operations target the base tables (trade_resources, trade_researches)
 * using updateOrInsert for upsert semantics.
 */
class TradeGateway
{
    public function __construct(private readonly ColonyService $colonyService) {}

    // ── Read ─────────────────────────────────────────────────────────────────

    /**
     * Return all resource trade offers, optionally filtered by $where conditions.
     *
     * @param  array|null $where  Associative array of column => value conditions.
     * @return Collection<int, TradeResourceView>
     */
    public function getResources(?array $where = null): Collection
    {
        $query = TradeResourceView::query();
        if ($where) {
            $query->where($where);
        }
        return $query->get();
    }

    /**
     * Return all research trade offers, optionally filtered by $where conditions.
     *
     * @param  array|null $where  Associative array of column => value conditions.
     * @return Collection<int, TradeResearchView>
     */
    public function getResearches(?array $where = null): Collection
    {
        $query = TradeResearchView::query();
        if ($where) {
            $query->where($where);
        }
        return $query->get();
    }

    // ── Write — Resource Offers ───────────────────────────────────────────────

    /**
     * Create or update a resource trade offer.
     *
     * The PK is (colony_id, direction, resource_id). If a row with that
     * composite key already exists it is updated (amount, price, restriction);
     * otherwise a new row is inserted.
     *
     * Required keys in $data: colony_id, direction, resource_id, amount, price, user_id.
     * Optional key: restriction (defaults to 0).
     *
     * Returns false when ownership check fails or user_id is missing.
     */
    public function addResourceOffer(array $data): bool
    {
        if (!$this->ownershipCheck($data)) {
            return false;
        }

        DB::table('trade_resources')->updateOrInsert(
            [
                'colony_id'   => $data['colony_id'],
                'direction'   => $data['direction'],
                'resource_id' => $data['resource_id'],
            ],
            [
                'amount'      => $data['amount'],
                'price'       => $data['price'],
                'restriction' => $data['restriction'] ?? 0,
            ]
        );

        return true;
    }

    /**
     * Remove a resource trade offer matching (colony_id, direction, resource_id).
     *
     * Required keys in $data: colony_id, direction, resource_id, user_id.
     * Returns false when ownership check fails, user_id is missing, or no row matched.
     */
    public function removeResourceOffer(array $data): bool
    {
        if (!$this->ownershipCheck($data)) {
            return false;
        }

        $affected = DB::table('trade_resources')
            ->where('colony_id', $data['colony_id'])
            ->where('direction', $data['direction'])
            ->where('resource_id', $data['resource_id'])
            ->delete();

        return (bool) $affected;
    }

    // ── Write — Research Offers ───────────────────────────────────────────────

    /**
     * Create or update a research trade offer.
     *
     * The PK is (colony_id, direction, research_id). Upsert semantics identical
     * to addResourceOffer.
     *
     * Required keys in $data: colony_id, direction, research_id, amount, price, user_id.
     * Optional key: restriction (defaults to 0).
     */
    public function addResearchOffer(array $data): bool
    {
        if (!$this->ownershipCheck($data)) {
            return false;
        }

        DB::table('trade_researches')->updateOrInsert(
            [
                'colony_id'   => $data['colony_id'],
                'direction'   => $data['direction'],
                'research_id' => $data['research_id'],
            ],
            [
                'amount'      => $data['amount'],
                'price'       => $data['price'],
                'restriction' => $data['restriction'] ?? 0,
            ]
        );

        return true;
    }

    /**
     * Remove a research trade offer matching (colony_id, direction, research_id).
     *
     * Required keys in $data: colony_id, direction, research_id, user_id.
     * Returns false when ownership check fails, user_id is missing, or no row matched.
     */
    public function removeResearchOffer(array $data): bool
    {
        if (!$this->ownershipCheck($data)) {
            return false;
        }

        $affected = DB::table('trade_researches')
            ->where('colony_id', $data['colony_id'])
            ->where('direction', $data['direction'])
            ->where('research_id', $data['research_id'])
            ->delete();

        return (bool) $affected;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    /**
     * Verify that $data contains a user_id and that user owns the colony.
     */
    private function ownershipCheck(array $data): bool
    {
        if (!isset($data['user_id'])) {
            return false;
        }

        return $this->colonyService->checkColonyOwner(
            (int) $data['colony_id'],
            (int) $data['user_id']
        );
    }
}
