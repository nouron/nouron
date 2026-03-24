<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\BaseController;
use App\Services\TickService;
use App\Services\TradeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TradeController — Laravel port of Trade\Controller\TradeController (Laminas).
 *
 * Handles the player-facing trade board: listing buy/sell offers for resources
 * and researches, and allowing colony owners to add or remove their own offers.
 *
 * Routes (defined in routes/web.php under prefix /trade):
 *   GET|POST /resources          → resources()        — browse + filter resource offers
 *   GET|POST /researches         → researches()       — browse + filter research offers
 *   POST     /offer/resource     → addResourceOffer() — add/update resource offer (partial view)
 *   POST     /offer/research     → addResearchOffer() — add/update research offer (partial view)
 *   POST     /offer/remove       → removeOffer()      — remove offer (JSON)
 *
 * All routes require authentication.
 */
class TradeController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly TradeGateway $tradeGateway,
    ) {
        parent::__construct($tick);
    }

    // ── Views ─────────────────────────────────────────────────────────────────

    /**
     * List resource trade offers.
     *
     * On GET: shows all offers. On POST: applies search filter (colony_id or
     * direction) passed via form fields.
     */
    public function resources(Request $request): View
    {
        $where   = $this->buildResourceFilter($request);
        $offers  = $this->tradeGateway->getResources($where ?: null);

        return view('trade.resources', compact('offers'));
    }

    /**
     * List research trade offers.
     *
     * On GET: shows all offers. On POST: applies search filter.
     */
    public function researches(Request $request): View
    {
        $where   = $this->buildResearchFilter($request);
        $offers  = $this->tradeGateway->getResearches($where ?: null);

        return view('trade.researches', compact('offers'));
    }

    // ── POST — Add Offers ─────────────────────────────────────────────────────

    /**
     * Add or update a resource trade offer. Returns a partial view (no layout)
     * suitable for embedding via AJAX.
     *
     * Required POST fields: colony_id, direction, resource_id, amount, price.
     * Optional: restriction.
     */
    public function addResourceOffer(Request $request): View
    {
        $data = $request->validate([
            'colony_id'   => ['required', 'integer', 'min:1'],
            'direction'   => ['required', 'integer', 'in:0,1'],
            'resource_id' => ['required', 'integer', 'min:1'],
            'amount'      => ['required', 'integer', 'min:0'],
            'price'       => ['required', 'integer', 'min:0'],
            'restriction' => ['sometimes', 'integer'],
        ]);

        $data['user_id'] = $this->getCurrentUserId();
        $result = $this->tradeGateway->addResourceOffer($data);

        $offers = $this->tradeGateway->getResources(['colony_id' => $data['colony_id']]);

        return view('trade.resources', compact('offers', 'result'))->withoutLayout();
    }

    /**
     * Add or update a research trade offer. Returns a partial view (no layout).
     *
     * Required POST fields: colony_id, direction, research_id, amount, price.
     * Optional: restriction.
     */
    public function addResearchOffer(Request $request): View
    {
        $data = $request->validate([
            'colony_id'   => ['required', 'integer', 'min:1'],
            'direction'   => ['required', 'integer', 'in:0,1'],
            'research_id' => ['required', 'integer', 'min:1'],
            'amount'      => ['required', 'integer', 'min:0'],
            'price'       => ['required', 'integer', 'min:0'],
            'restriction' => ['sometimes', 'integer'],
        ]);

        $data['user_id'] = $this->getCurrentUserId();
        $result = $this->tradeGateway->addResearchOffer($data);

        $offers = $this->tradeGateway->getResearches(['colony_id' => $data['colony_id']]);

        return view('trade.researches', compact('offers', 'result'))->withoutLayout();
    }

    // ── POST — Remove Offer ───────────────────────────────────────────────────

    /**
     * Remove a trade offer. Detects resource vs. research by which ID field is
     * present in the request (resource_id or research_id).
     *
     * Returns JSON: { result: bool }.
     */
    public function removeOffer(Request $request): JsonResponse
    {
        $userId = $this->getCurrentUserId();

        $data = array_merge(
            $request->only(['colony_id', 'direction', 'resource_id', 'research_id']),
            ['user_id' => $userId]
        );

        if ($request->has('resource_id')) {
            $result = $this->tradeGateway->removeResourceOffer($data);
        } elseif ($request->has('research_id')) {
            $result = $this->tradeGateway->removeResearchOffer($data);
        } else {
            $result = false;
        }

        return response()->json(['result' => $result]);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function buildResourceFilter(Request $request): array
    {
        $filter = [];
        if ($request->isMethod('post')) {
            if ($request->filled('colony_id')) {
                $filter['colony_id'] = (int) $request->input('colony_id');
            }
            if ($request->filled('direction') && $request->input('direction') !== '') {
                $filter['direction'] = (int) $request->input('direction');
            }
        }
        return $filter;
    }

    private function buildResearchFilter(Request $request): array
    {
        $filter = [];
        if ($request->isMethod('post')) {
            if ($request->filled('colony_id')) {
                $filter['colony_id'] = (int) $request->input('colony_id');
            }
            if ($request->filled('direction') && $request->input('direction') !== '') {
                $filter['direction'] = (int) $request->input('direction');
            }
        }
        return $filter;
    }
}
