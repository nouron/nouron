<?php

namespace App\Http\Controllers\Techtree;

use App\Http\Controllers\BaseController;
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
        $colonyId  = $this->resolveColonyId();
        $advisors  = $this->personellService->getColonyAdvisors($colonyId)->load('personell');
        $freeSupply = $this->resourcesService->getFreeSupply($colonyId);
        $costPerAdvisor = (int) config('game.supply.cost_advisor', 2);

        // All hireable personell types (exclude commander — assigned via fleet config)
        $personellTypes = Personell::whereIn('id', [
            PersonellService::PERSONELL_ID_ENGINEER,
            PersonellService::PERSONELL_ID_SCIENTIST,
            PersonellService::PERSONELL_ID_TRADER,
        ])->get()->keyBy('id');

        $apInfo = [
            'construction' => $this->personellService->getTotalActionPoints('construction', $colonyId),
            'research'     => $this->personellService->getTotalActionPoints('research', $colonyId),
            'economy'      => $this->personellService->getTotalActionPoints('economy', $colonyId),
        ];

        return view('advisors.index', compact(
            'advisors', 'freeSupply', 'costPerAdvisor', 'personellTypes', 'apInfo', 'colonyId'
        ));
    }

    public function hire(Request $request)
    {
        $request->validate([
            'personell_id' => 'required|integer|in:'
                . PersonellService::PERSONELL_ID_ENGINEER . ','
                . PersonellService::PERSONELL_ID_SCIENTIST . ','
                . PersonellService::PERSONELL_ID_TRADER,
        ]);

        $colonyId    = $this->resolveColonyId();
        $userId      = $this->getCurrentUserId();
        $personellId = (int) $request->input('personell_id');

        $result = $this->personellService->hire($userId, $personellId, $colonyId);

        if ($result === false) {
            return back()->with('error', 'Nicht genug freie Supply-Kapazität, um einen neuen Berater einzustellen.');
        }

        return back()->with('success', 'Berater eingestellt.');
    }

    public function fire(int $advisorId)
    {
        $this->personellService->fire($advisorId);
        return back()->with('success', 'Berater entlassen.');
    }
}
