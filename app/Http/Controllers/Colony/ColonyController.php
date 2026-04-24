<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use App\Services\ColonyTileService;
use App\Services\TickService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ColonyController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly ColonyTileService $tileService,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        return view('colony.index', compact('colony'));
    }

    public function hexview(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tiles  = $this->tileService->getTilesForColony($colony->id);

        if ($tiles->isEmpty()) {
            $this->tileService->generateDefaultTiles($colony);
            $tiles = $this->tileService->getTilesForColony($colony->id);
        }

        $ccLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', 25)
            ->value('level') ?? 0;

        return view('colony.hexview', compact('colony', 'tiles', 'ccLevel'));
    }

    public function rename(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[^<>{}\[\]]*$/'],
        ]);

        $colony = $this->colonyService->getPrimeColony(Auth::id());

        DB::table('glx_colonies')
            ->where('id', $colony->id)
            ->update(['name' => $request->input('name')]);

        return redirect()->route('colony.index')
            ->with('success', 'Kolonienname wurde aktualisiert.');
    }
}
