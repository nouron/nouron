<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Services\ColonyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ColonyController extends BaseController
{
    public function __construct(private readonly ColonyService $colonyService) {}

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());

        return view('colony.index', compact('colony'));
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
