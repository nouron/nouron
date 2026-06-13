<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly MerchantService $merchantService,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    /**
     * POST /merchant/buy/{itemId}
     *
     * Purchase one merchant item. Returns JSON with ok/error and new credits balance.
     */
    public function buy(Request $request, int $itemId): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $result = $this->merchantService->buyItem($itemId, $colony->id, $userId);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $this->getTick(),
                'event' => 'trade.merchant_purchase',
                'area' => 'trade',
                'parameters' => json_encode(['colony_id' => $colony->id, 'item_id' => $itemId]),
            ]);
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * POST /merchant/visit/{visitId}/open
     *
     * Mark a merchant visit as seen (player opened the modal).
     */
    public function markVisited(Request $request, int $visitId): JsonResponse
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $this->merchantService->markVisited($visitId, $colony->id);

        return response()->json(['ok' => true]);
    }
}
