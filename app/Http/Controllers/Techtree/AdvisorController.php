<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Models\Advisor;
use App\Models\Personell;
use App\Services\ColonyService;
use App\Services\ResourcesService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AdvisorController extends BaseController
{
    public function __construct(
        TickService                       $tick,
        private readonly PersonellService $personellService,
        private readonly ResourcesService $resourcesService,
        private readonly ColonyService    $colonyService,
    ) {
        parent::__construct($tick);
    }

    private function resolveColonyId(): int
    {
        $colonyId = Session::get('activeIds.colonyId');
        if ($colonyId) {
            return (int) $colonyId;
        }
        $colony = $this->colonyService->getPrimeColony($this->getCurrentUserId());
        $id = $colony->id;
        Session::put('activeIds.colonyId', $id);
        return $id;
    }

    public function index()
    {
        $userId   = $this->getCurrentUserId();
        $colonyId = $this->resolveColonyId();

        $advisors = $this->personellService->getColonyAdvisors($colonyId)->load('personell');
        $slotInfo = $this->personellService->getAdvisorSlotInfo($colonyId);

        // Credits cost per type, keyed by personell_id
        $creditsByPersonellId = collect(config('advisors'))
            ->mapWithKeys(fn($cfg) => [$cfg['id'] => $cfg['credits']]);

        // Hireable types (all colony-scoped)
        $personellTypes = Personell::whereIn('id', [
            PersonellService::PERSONELL_ID_ENGINEER,
            PersonellService::PERSONELL_ID_SCIENTIST,
            PersonellService::PERSONELL_ID_TRADER,
            PersonellService::PERSONELL_ID_STRATEGE,
        ])->get()->keyBy('id');

        $apInfo = [
            'construction' => $this->personellService->getTotalActionPoints('construction', $colonyId),
            'research'     => $this->personellService->getTotalActionPoints('research', $colonyId),
            'economy'      => $this->personellService->getTotalActionPoints('economy', $colonyId),
            'strategy'     => $this->personellService->getTotalActionPoints('strategy', $colonyId),
        ];

        return view('advisors.index', compact(
            'advisors', 'slotInfo', 'creditsByPersonellId', 'personellTypes', 'apInfo', 'colonyId'
        ));
    }

    public function hire(Request $request)
    {
        $hireable = implode(',', [
            PersonellService::PERSONELL_ID_ENGINEER,
            PersonellService::PERSONELL_ID_SCIENTIST,
            PersonellService::PERSONELL_ID_TRADER,
            PersonellService::PERSONELL_ID_STRATEGE,
        ]);

        $request->validate([
            'personell_id' => "required|integer|in:{$hireable}",
        ]);

        $colonyId    = $this->resolveColonyId();
        $userId      = $this->getCurrentUserId();
        $personellId = (int) $request->input('personell_id');

        $result = $this->personellService->hire($userId, $personellId, $colonyId);

        if (is_string($result)) {
            $errorMessages = [
                'duplicate'            => 'Für diesen Beratertyp ist bereits ein Berater auf dieser Kolonie aktiv.',
                'slot_full'            => 'Kein freier Berater-Slot. Erhöhe das CommandCenter-Level.',
                'insufficient_credits' => 'Nicht genug Credits, um diesen Berater einzustellen.',
            ];
            return back()->with('error', $errorMessages[$result] ?? 'Berater konnte nicht eingestellt werden.');
        }

        return back()->with('success', 'Berater eingestellt.');
    }

    public function fire(int $advisorId)
    {
        $advisor = Advisor::where('id', $advisorId)
            ->where('user_id', $this->getCurrentUserId())
            ->first();

        if (!$advisor) {
            abort(404);
        }

        $this->personellService->fire($advisorId);
        return back()->with('success', 'Berater entlassen.');
    }
}
