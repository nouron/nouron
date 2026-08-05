<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\CorporateContactService;
use App\Services\EventService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Orin (corporate_rep) — Weg A for the Harvester second instance (GDD §4c
 * "Harvester-Zweitinstanz: Bezugsquelle", freigegeben 2026-08-05). Purchase-only
 * endpoint; the offer itself is derived server-side (CorporateContactService), never
 * trusted from client input.
 */
class CorporateContactController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly CorporateContactService $corporateContactService,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    /**
     * POST /colony/corporate-contact/buy-harvester
     *
     * Purchases Orin's current harvester offer, if any. Grants the entitlement
     * (HarvesterEntitlementService::TRIGGER_PURCHASE) — placement itself still runs
     * through the normal ColonyController::placeBuilding flow (player picks a tile).
     */
    public function buyHarvester(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $result = $this->corporateContactService->buyHarvesterOffer($colony->id, $userId, $this->getTick());

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $this->getTick(),
                'event' => 'colony.corporate_contact_purchase',
                'area' => 'colony',
                'parameters' => json_encode(['colony_id' => $colony->id, 'price' => $result['price']]),
            ]);
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
