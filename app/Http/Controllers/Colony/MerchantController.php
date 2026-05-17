<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\MerchantService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService  $colonyService,
        private readonly MerchantService $merchantService,
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
