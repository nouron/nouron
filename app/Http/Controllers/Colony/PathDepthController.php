<?php

namespace App\Http\Controllers\Colony;

use App\Exceptions\GameRuleException;
use App\Http\Controllers\BaseController;
use App\Services\AdvisorService;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\PathDepthService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PathDepthController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly EventService $eventService,
        private readonly ColonyService $colonyService,
        private readonly PathDepthService $pathDepthService,
        private readonly AdvisorService $advisorService,
    ) {
        parent::__construct($tick);
    }

    public function invest(Request $request, string $kind, string $key): JsonResponse
    {
        $validated = $request->validate(['ap' => ['sometimes', 'integer', 'min:1']]);
        $ap = (int) ($validated['ap'] ?? 1);
        // Conversations are paid in configured option sizes only (client input is not trusted).
        if ($kind === PathDepthService::KIND_CHARACTER && ! in_array($ap, array_map('intval', (array) config('path_depth.conversation_options')), true)) {
            return response()->json(['ok' => false, 'error' => 'invalid_option', 'message' => __('pathdepth.error_invalid_option')], 422);
        }
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        try {
            $result = $this->pathDepthService->invest($colony->id, $kind, $key, $ap);
        } catch (GameRuleException $e) {
            return response()->json(['ok' => false, 'error' => $e->errorCode, 'message' => $e->getMessage()], 422);
        }

        $this->eventService->createEvent([
            'user' => Auth::id(),
            'tick' => $this->getTick(),
            'event' => 'colony.pathdepth_invested',
            'area' => 'colony',
            'parameters' => json_encode([
                'kind' => $kind,
                'key' => $key,
                'ap_spend' => $result['points'],
                'level' => $result['level'],
                'leveled_up' => $result['leveled_up'],
            ]),
        ]);

        return response()->json([
            'ok' => true,
            'level' => $result['level'],
            'ap_spend' => $result['ap_spend'],
            'ap_for_levelup' => $result['ap_for_levelup'],
            'leveled_up' => $result['leveled_up'],
            'level_cap' => $result['level_cap'],
            'ap_cap' => $result['ap_cap'],
            'apAvailable' => $this->advisorService->getAvailableActionPoints($colony->id),
        ] + ($kind === PathDepthService::KIND_CHARACTER
            ? ['conversation' => $this->pathDepthService->conversation($colony->id, $key)]
            : []));
    }
}
