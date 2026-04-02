<?php

namespace App\Http\Controllers\Trade;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\TickService;
use App\Services\TradeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * TradeController — handles the player-facing trade board.
 *
 * Handles the player-facing trade board: listing buy/sell offers for resources
 * and researches, and allowing colony owners to add or remove their own offers.
 *
 * Routes (defined in routes/web.php under prefix /trade):
 *   GET|POST /resources          → resources()        — browse + filter resource offers
 *   GET|POST /researches         → researches()       — browse + filter research offers
 *   POST     /offer/resource     → addResourceOffer() — add/update resource offer
 *   POST     /offer/research     → addResearchOffer() — add/update research offer
 *   POST     /offer/remove       → removeOffer()      — remove offer (JSON)
 *
 * All routes require authentication.
 */
class TradeController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly TradeGateway $tradeGateway,
        private readonly ColonyService $colonyService,
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
        $where      = $this->buildFilter($request);
        $offers     = $this->tradeGateway->getResources($where ?: null);
        $resources  = \Illuminate\Support\Facades\DB::table('resources')->get()->keyBy('id');
        $user_id    = $this->getCurrentUserId();
        $myColonies = $user_id ? $this->colonyService->getColoniesByUserId($user_id) : collect();

        return view('trade.resources', compact('offers', 'resources', 'user_id', 'myColonies'));
    }

    // ── POST — Add Offers ─────────────────────────────────────────────────────

    /**
     * Add or update a resource trade offer.
     *
     * Required POST fields: colony_id, direction, resource_id, amount, price.
     * Optional: restriction.
     */
    public function addResourceOffer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'colony_id'   => ['required', 'integer', 'min:1'],
            'direction'   => ['required', 'integer', 'in:0,1'],
            'resource_id' => ['required', 'integer', 'min:1'],
            'amount'      => ['required', 'integer', 'min:1'],
            'price'       => ['required', 'integer', 'min:1'],
            'restriction' => ['sometimes', 'nullable', 'integer'],
        ]);

        $data['user_id'] = $this->getCurrentUserId();
        $result = $this->tradeGateway->addResourceOffer($data);

        if ($result) {
            return redirect()->route('trade.resources')
                ->with('success', 'Angebot gespeichert.');
        }

        return redirect()->route('trade.resources')
            ->with('error', 'Angebot konnte nicht gespeichert werden.');
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
        $request->validate([
            'colony_id' => ['required', 'integer', 'min:1'],
            'direction'  => ['required', 'integer', 'in:0,1'],
        ]);

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

    /**
     * Extract filter conditions from either GET or POST parameters.
     */
    private function buildFilter(Request $request): array
    {
        $filter = [];
        if ($request->filled('colony_id')) {
            $filter['colony_id'] = (int) $request->input('colony_id');
        }
        if ($request->filled('direction') && $request->input('direction') !== '') {
            $filter['direction'] = (int) $request->input('direction');
        }
        return $filter;
    }
}
