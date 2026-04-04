<?php

namespace App\Services;

use App\Models\TradeResearchView;
use App\Models\TradeResourceView;
use App\Services\MoralService;
use App\Services\Techtree\PersonellService;
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
 *
 * Economy AP costs (Händler):
 *   - Creating an offer: max(1, floor(amount × price / threshold)) AP, where
 *     threshold = config('game.trade.ap_cost_threshold', 1000).
 *   - Accepting an offer: 1 AP, paid by the acceptor (buyer).
 *   - Removing an offer: 0 AP.
 * All AP checks are bypassed when config('game.dev_mode') is true.
 */
class TradeGateway
{
    public function __construct(
        private readonly ColonyService    $colonyService,
        private readonly MoralService     $moralService,
        private readonly PersonellService $personellService,
    ) {}

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
     * AP cost: max(1, floor(amount × price / threshold)) economy AP, deducted from
     * the posting colony. Bypassed in dev_mode.
     *
     * Returns false when ownership check fails or user_id is missing.
     * @throws \InvalidArgumentException when the colony lacks sufficient economy AP.
     */
    public function addResourceOffer(array $data): bool
    {
        if (!$this->ownershipCheck($data)) {
            return false;
        }

        $colonyId = (int) $data['colony_id'];
        $apCost   = $this->calcOfferApCost((int) $data['amount'], (int) $data['price']);

        if (!config('game.dev_mode')) {
            $available = $this->personellService->getEconomyPoints($colonyId);
            if ($available < $apCost) {
                throw new \InvalidArgumentException(
                    'Nicht genug Wirtschafts-AP, um dieses Angebot einzustellen.'
                );
            }
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

        if (!config('game.dev_mode')) {
            $this->personellService->lockActionPoints('economy', $colonyId, $apCost);
        }

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

    // ── Write — Accept Resource Offer ────────────────────────────────────────

    /**
     * Accept a resource trade offer, transferring resources and credits atomically.
     *
     * Rules:
     * - Sell offer (direction=1): buyer pays amount×price credits to the seller;
     *   seller's colony loses the resource, buyer's colony gains it.
     * - Buy offer (direction=0): the acceptor (seller) delivers the resource to the
     *   offering colony and receives amount×price credits in return.
     * - Restriction semantics:
     *   0 = anyone may accept
     *   1 = same group (treated as 0 until group module exists)
     *   2 = same faction (buyer.faction_id == seller.faction_id)
     *   3 = same race   (buyer.race_id   == seller.race_id)
     * - After a successful transfer the offer row is deleted from trade_resources.
     * - A player may never accept their own offer.
     * - AP cost: 1 economy AP charged to the acceptor (buyer). Bypassed in dev_mode.
     *
     * @param  int  $buyerUserId    Authenticated user who is accepting the offer.
     * @param  int  $buyerColonyId  Colony of the accepting user.
     * @param  int  $sellerColonyId Colony that posted the offer.
     * @param  int  $direction      0 = buy offer, 1 = sell offer.
     * @param  int  $resourceId     Resource being traded.
     * @return bool                 Always true on success.
     * @throws \InvalidArgumentException on any validation failure.
     */
    public function acceptResourceOffer(
        int $buyerUserId,
        int $buyerColonyId,
        int $sellerColonyId,
        int $direction,
        int $resourceId,
    ): bool {
        // Economy-AP check: acceptor must have at least 1 economy AP.
        // Performed before entering the transaction so the error surfaces immediately.
        if (!config('game.dev_mode')) {
            $available = $this->personellService->getEconomyPoints($buyerColonyId);
            if ($available < 1) {
                throw new \InvalidArgumentException(
                    'Nicht genug Wirtschafts-AP, um dieses Angebot anzunehmen.'
                );
            }
        }

        return DB::transaction(function () use (
            $buyerUserId, $buyerColonyId, $sellerColonyId, $direction, $resourceId
        ) {
            // 1. Load the offer
            $offer = DB::table('trade_resources')
                ->where('colony_id',   $sellerColonyId)
                ->where('direction',   $direction)
                ->where('resource_id', $resourceId)
                ->lockForUpdate()
                ->first();

            if (!$offer) {
                throw new \InvalidArgumentException('Angebot nicht gefunden.');
            }

            // 2. Resolve seller user_id via the colony
            $sellerColony = DB::table('glx_colonies')
                ->where('id', $sellerColonyId)
                ->first();

            if (!$sellerColony) {
                throw new \InvalidArgumentException('Verkäufer-Kolonie nicht gefunden.');
            }

            $sellerUserId = (int) $sellerColony->user_id;

            // 3. Prevent self-trade
            if ($buyerUserId === $sellerUserId) {
                throw new \InvalidArgumentException('Du kannst dein eigenes Angebot nicht annehmen.');
            }

            // 4. Restriction check
            $restriction = (int) $offer->restriction;

            if ($restriction === 2 || $restriction === 3) {
                $buyerUser  = DB::table('user')->where('user_id', $buyerUserId)->first();
                $sellerUser = DB::table('user')->where('user_id', $sellerUserId)->first();

                if (!$buyerUser || !$sellerUser) {
                    throw new \InvalidArgumentException('Benutzer nicht gefunden.');
                }

                if ($restriction === 2 && $buyerUser->faction_id !== $sellerUser->faction_id) {
                    throw new \InvalidArgumentException('Dieses Angebot ist nur für Mitglieder der gleichen Fraktion verfügbar.');
                }

                if ($restriction === 3 && $buyerUser->race_id !== $sellerUser->race_id) {
                    throw new \InvalidArgumentException('Dieses Angebot ist nur für Mitglieder der gleichen Rasse verfügbar.');
                }
            }

            $amount    = (int) $offer->amount;
            $totalCost = $amount * (int) $offer->price;

            if ($direction === 1) {
                // Sell offer: buyer pays credits, buyer receives resource from seller colony

                // 5a. Check buyer credits
                $buyerResources = DB::table('user_resources')
                    ->where('user_id', $buyerUserId)
                    ->lockForUpdate()
                    ->first();

                if (!$buyerResources || (int) $buyerResources->credits < $totalCost) {
                    throw new \InvalidArgumentException(
                        'Nicht genug Credits. Benötigt: ' . $totalCost . '.'
                    );
                }

                // 5b. Check seller has the resource
                $sellerStock = DB::table('colony_resources')
                    ->where('colony_id',   $sellerColonyId)
                    ->where('resource_id', $resourceId)
                    ->lockForUpdate()
                    ->first();

                if (!$sellerStock || (int) $sellerStock->amount < $amount) {
                    throw new \InvalidArgumentException(
                        'Verkäufer hat nicht genug Ressourcen auf Lager.'
                    );
                }

                // 6a. Transfer credits: buyer → seller
                DB::table('user_resources')
                    ->where('user_id', $buyerUserId)
                    ->decrement('credits', $totalCost);

                $sellerHasResRow = DB::table('user_resources')->where('user_id', $sellerUserId)->exists();
                if ($sellerHasResRow) {
                    DB::table('user_resources')->where('user_id', $sellerUserId)->increment('credits', $totalCost);
                } else {
                    DB::table('user_resources')->insert(['user_id' => $sellerUserId, 'credits' => $totalCost, 'supply' => 0]);
                }

                // 6b. Transfer resource: seller colony → buyer colony
                DB::table('colony_resources')
                    ->where('colony_id',   $sellerColonyId)
                    ->where('resource_id', $resourceId)
                    ->decrement('amount', $amount);

                $buyerHasResource = DB::table('colony_resources')
                    ->where('colony_id', $buyerColonyId)
                    ->where('resource_id', $resourceId)
                    ->exists();

                if ($buyerHasResource) {
                    DB::table('colony_resources')
                        ->where('colony_id',   $buyerColonyId)
                        ->where('resource_id', $resourceId)
                        ->increment('amount', $amount);
                } else {
                    DB::table('colony_resources')
                        ->insert(['colony_id' => $buyerColonyId, 'resource_id' => $resourceId, 'amount' => $amount]);
                }

            } else {
                // Buy offer (direction=0): acceptor (buyer) delivers resource to seller colony,
                // seller pays the credits.

                // 5a. Check acceptor has the resource
                $acceptorStock = DB::table('colony_resources')
                    ->where('colony_id',   $buyerColonyId)
                    ->where('resource_id', $resourceId)
                    ->lockForUpdate()
                    ->first();

                if (!$acceptorStock || (int) $acceptorStock->amount < $amount) {
                    throw new \InvalidArgumentException(
                        'Du hast nicht genug Ressourcen, um dieses Kaufangebot zu bedienen.'
                    );
                }

                // 5b. Check seller (offering party) has the credits
                $sellerResources = DB::table('user_resources')
                    ->where('user_id', $sellerUserId)
                    ->lockForUpdate()
                    ->first();

                if (!$sellerResources || (int) $sellerResources->credits < $totalCost) {
                    throw new \InvalidArgumentException(
                        'Der Anbieter hat nicht mehr genug Credits, um das Angebot zu erfüllen.'
                    );
                }

                // 6a. Transfer credits: seller → acceptor (buyer)
                DB::table('user_resources')
                    ->where('user_id', $sellerUserId)
                    ->decrement('credits', $totalCost);

                $buyerHasResRow = DB::table('user_resources')->where('user_id', $buyerUserId)->exists();
                if ($buyerHasResRow) {
                    DB::table('user_resources')->where('user_id', $buyerUserId)->increment('credits', $totalCost);
                } else {
                    DB::table('user_resources')->insert(['user_id' => $buyerUserId, 'credits' => $totalCost, 'supply' => 0]);
                }

                // 6b. Transfer resource: acceptor colony → seller colony
                DB::table('colony_resources')
                    ->where('colony_id',   $buyerColonyId)
                    ->where('resource_id', $resourceId)
                    ->decrement('amount', $amount);

                $sellerHasResource = DB::table('colony_resources')
                    ->where('colony_id',   $sellerColonyId)
                    ->where('resource_id', $resourceId)
                    ->exists();

                if ($sellerHasResource) {
                    DB::table('colony_resources')
                        ->where('colony_id',   $sellerColonyId)
                        ->where('resource_id', $resourceId)
                        ->increment('amount', $amount);
                } else {
                    DB::table('colony_resources')
                        ->insert(['colony_id' => $sellerColonyId, 'resource_id' => $resourceId, 'amount' => $amount]);
                }
            }

            // 7. Lock 1 economy AP for the acceptor (buyer).
            if (!config('game.dev_mode')) {
                $this->personellService->lockActionPoints('economy', $buyerColonyId, 1);
            }

            // 7b. Fire moral events for both parties (scheduled for next tick).
            $this->moralService->fireEvent($buyerColonyId,  'trade_success');
            $this->moralService->fireEvent($sellerColonyId, 'trade_success');

            // 8. Delete the offer
            DB::table('trade_resources')
                ->where('colony_id',   $sellerColonyId)
                ->where('direction',   $direction)
                ->where('resource_id', $resourceId)
                ->delete();

            return true;
        });
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

    /**
     * Calculate the economy AP cost for posting a trade offer.
     *
     * Formula: max(1, floor(amount × price / threshold))
     * where threshold = config('game.trade.ap_cost_threshold', 1000).
     *
     * This ensures that cheap/small offers always cost at least 1 AP, and that
     * high-value offers scale proportionally to limit market spam.
     */
    private function calcOfferApCost(int $amount, int $price): int
    {
        $threshold = (int) config('game.trade.ap_cost_threshold', 1000);
        return max(1, (int) floor($amount * $price / $threshold));
    }
}
