<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\BarService;
use App\Services\ColonyService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarController extends BaseController
{
    private const BAR_BUILDING_ID = 52;

    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly BarService    $barService,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony     = $this->colonyService->getPrimeColony(Auth::id());
        $tick       = $this->tick->getTickCount();
        $currentSol = max(1, $tick - (int) $colony->since_tick + 1);

        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level') ?? 0;

        $offers = $barLevel > 0
            ? $this->barService->getActiveOffers($colony->id, $tick)
            : collect();

        return view('colony.bar', compact('colony', 'offers', 'barLevel', 'currentSol'));
    }

    public function accept(Request $request, int $offerId): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick   = $this->tick->getTickCount();
        $result = $this->barService->acceptOffer($colony->id, $offerId, $userId, $tick);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
