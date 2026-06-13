<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Models\Run;
use App\Services\BarService;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\MerchantService;
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
        private readonly BarService $barService,
        private readonly MerchantService $merchantService,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tick = $this->tick->getTickCount();
        $currentSol = max(1, $tick - (int) $colony->since_tick + 1);

        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level') ?? 0;

        $offers = $barLevel > 0
            ? $this->barService->getActiveOffers($colony->id, $tick)
            : collect();

        $merchantVisit = $this->merchantService->getActiveVisit($colony->id, $tick);
        $merchantItems = $merchantVisit
            ? $this->merchantService->getItemsForVisit($merchantVisit->id)->values()->toArray()
            : [];

        $hotspotsFile = base_path('data/cantina_hotspots.json');
        $hotspots = file_exists($hotspotsFile)
            ? (json_decode(file_get_contents($hotspotsFile), true) ?: [])
            : [];

        $run = Run::where('colony_id', $colony->id)->active()->first();
        $seed = $run ? $run->id : $colony->id;
        $characters = config('characters');

        $characterAssignment = [];
        foreach ($hotspots as $spotKey => $spot) {
            if (empty($spot['characters'])) {
                continue;
            }
            $idx = abs(crc32($seed.$spotKey)) % count($spot['characters']);
            $slug = $spot['characters'][$idx];
            $char = $characters[$slug] ?? null;
            if ($char) {
                $characterAssignment[$spotKey] = ['slug' => $slug] + $char;
            }
        }

        return view('colony.bar', compact(
            'colony', 'offers', 'barLevel', 'currentSol',
            'merchantVisit', 'merchantItems', 'hotspots', 'characterAssignment',
        ));
    }

    public function accept(Request $request, int $offerId): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();
        $result = $this->barService->acceptOffer($colony->id, $offerId, $userId, $tick);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $tick,
                'event' => 'trade.bar_accepted',
                'area' => 'trade',
                'parameters' => json_encode([
                    'colony_id' => $colony->id,
                    'give_resource_id' => $result['give_resource_id'],
                    'give_amount' => $result['give_amount'],
                    'get_resource_id' => $result['get_resource_id'],
                    'get_amount' => $result['get_amount'],
                ]),
            ]);
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
