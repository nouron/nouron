<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
use App\Models\Advisor;
use App\Models\Fleet;
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

        // Hireable types (all colony-scoped — pilot is hired via this page too,
        // then assigned to a fleet via assignCommander)
        $personellTypes = Personell::whereIn('id', [
            PersonellService::PERSONELL_ID_ENGINEER,
            PersonellService::PERSONELL_ID_SCIENTIST,
            PersonellService::PERSONELL_ID_TRADER,
            PersonellService::PERSONELL_ID_STRATEGE,
            PersonellService::PERSONELL_ID_PILOT,
        ])->get()->keyBy('id');

        // Fleet commanders of this user
        $fleetCommanders = Advisor::where('user_id', $userId)
            ->where('is_commander', true)
            ->whereNotNull('fleet_id')
            ->with(['personell', 'fleet'])
            ->get();

        // Pilots on colony (not yet assigned to a fleet) — available for fleet assignment
        $availablePilots = Advisor::where('user_id', $userId)
            ->where('personell_id', PersonellService::PERSONELL_ID_PILOT)
            ->whereNotNull('colony_id')
            ->with('personell')
            ->get();

        // Fleets without a commander (available for assignment)
        $userFleets = Fleet::where('user_id', $userId)->get();

        $apInfo = [
            'construction' => $this->personellService->getTotalActionPoints('construction', $colonyId),
            'knowledge'    => $this->personellService->getTotalActionPoints('knowledge', $colonyId),
            'economy'    => $this->personellService->getTotalActionPoints('economy', $colonyId),
            'strategy'   => $this->personellService->getTotalActionPoints('strategy', $colonyId),
            'navigation' => $this->personellService->getTotalActionPoints('navigation', $colonyId),
        ];

        return view('advisors.index', compact(
            'advisors', 'fleetCommanders', 'availablePilots', 'userFleets',
            'slotInfo', 'creditsByPersonellId', 'personellTypes', 'apInfo', 'colonyId'
        ));
    }

    public function hire(Request $request)
    {
        $hireable = implode(',', [
            PersonellService::PERSONELL_ID_ENGINEER,
            PersonellService::PERSONELL_ID_SCIENTIST,
            PersonellService::PERSONELL_ID_TRADER,
            PersonellService::PERSONELL_ID_STRATEGE,
            PersonellService::PERSONELL_ID_PILOT,
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

    public function assignCommander(Request $request, int $advisorId)
    {
        $request->validate([
            'fleet_id' => 'required|integer',
        ]);

        $userId   = $this->getCurrentUserId();
        $advisor  = Advisor::where('id', $advisorId)->where('user_id', $userId)->first();
        if (!$advisor) {
            abort(404);
        }

        $fleet = Fleet::where('id', $request->input('fleet_id'))->where('user_id', $userId)->first();
        if (!$fleet) {
            abort(404);
        }

        // Check that the fleet has no commander yet
        if (Advisor::where('fleet_id', $fleet->id)->where('is_commander', true)->exists()) {
            return back()->with('error', 'Diese Flotte hat bereits einen Kommandanten.');
        }

        $this->personellService->assignToFleet($advisorId, $fleet->id);
        return back()->with('success', 'Kommandant der Flotte zugewiesen.');
    }

    public function unassignCommander(int $advisorId)
    {
        $userId   = $this->getCurrentUserId();
        $advisor  = Advisor::where('id', $advisorId)->where('user_id', $userId)->first();
        if (!$advisor) {
            abort(404);
        }

        $colonyId = $this->resolveColonyId();
        $this->personellService->unassignFromFleet($advisorId, $colonyId);
        return back()->with('success', 'Kommandant abberufen.');
    }
}
