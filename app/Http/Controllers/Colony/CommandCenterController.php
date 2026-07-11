<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Models\Run;
use App\Services\ColonyService;
use App\Services\Techtree\PersonellService;
use App\Services\TickService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CommandCenterController — Kommandozentrale dashboard (GDD, Owner-Konzept
 * 2026-07-10). Bundles colony-overview data that was previously scattered
 * across popups (Phasenziele, Kolonisten-Zulage) or nowhere visible at all
 * (Run-Fortschritt, Wartungsstau, Netto-Sol-Bilanz, Berater-Kurzübersicht,
 * Vertrauens-Ereignisse) into one always-accessible screen (no build-gate —
 * the CC exists from Sol 1).
 */
class CommandCenterController extends BaseController
{
    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly PersonellService $personellService,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $run = Run::where('colony_id', $colony->id)->where('status', 'active')->first();

        $phaseProgress = $this->colonyService->getPhaseProgress($colony);
        $stipendTiers = config('game.stipend.tiers', []);

        $solLimit = $run?->getTickLimit() ?? (int) config('game.run.tick_limit', 100);
        $currentSol = $this->currentSol();
        $nexusDebt = (int) ($run?->nexus_debt ?? 0);
        $nexusDebtMax = 12000; // see resourcebar.blade.php — same magic number, no config key yet
        $nexusPct = $nexusDebtMax > 0 ? ($nexusDebt / $nexusDebtMax) * 100 : 0;
        $nexusDebtTone = match (true) {
            $nexusPct >= 95 => 'danger',
            $nexusPct >= 80 => 'warning',
            default => 'neutral',
        };

        $totalBuildings = DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('level', '>=', 1)
            ->count();

        $repairThreshold = (int) config('game.onboarding.hint_repair_urgent_sp', 3);
        $damagedBuildings = DB::table('colony_buildings')
            ->join('buildings', 'colony_buildings.building_id', '=', 'buildings.id')
            ->where('colony_buildings.colony_id', $colony->id)
            ->where('colony_buildings.level', '>=', 1)
            ->where('colony_buildings.status_points', '<=', $repairThreshold)
            ->select(
                'colony_buildings.building_id',
                'colony_buildings.instance_id',
                'colony_buildings.status_points',
                'colony_buildings.tile_x',
                'colony_buildings.tile_y',
                'buildings.name as building_key',
                'buildings.max_status_points',
            )
            ->get()
            ->map(fn ($b) => [
                'label' => __('techtree.'.$b->building_key),
                'status_points' => (int) $b->status_points,
                'max_status_points' => (int) $b->max_status_points,
                'tile_x' => $b->tile_x,
                'tile_y' => $b->tile_y,
            ])
            ->values();

        $lastSolDeltas = Cache::get("colony:{$colony->id}:last_sol_deltas");

        $advisorTypeByPersonellId = collect(config('advisors', []))
            ->mapWithKeys(fn ($cfg, $key) => [$cfg['id'] => $key]);
        $advisors = $this->personellService->getColonyAdvisors($colony->id)
            ->map(function ($advisor) use ($advisorTypeByPersonellId) {
                $typeKey = $advisorTypeByPersonellId[$advisor->personell_id] ?? null;

                return [
                    'name' => $typeKey ? __('advisors.'.$typeKey) : '?',
                    'rank' => (int) $advisor->rank,
                    'rank_name' => match ((int) $advisor->rank) {
                        1 => 'Junior',
                        2 => 'Senior',
                        3 => 'Experte',
                        default => '?',
                    },
                    'ap_per_tick' => $advisor->getApPerTick(),
                    'ap_type' => $typeKey ? (config("advisors.{$typeKey}.ap_type") ?? '') : '',
                ];
            })
            ->values();

        $trustEvents = DB::table('trust_events')
            ->where('colony_id', $colony->id)
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['tick', 'event_type'])
            ->map(fn ($row) => [
                'tick' => (int) $row->tick,
                'description' => __('trust.event_'.$row->event_type),
                'delta' => (int) config('game.trust.events.'.$row->event_type, 0),
            ])
            ->values();

        return view('colony.command_center', compact(
            'phaseProgress',
            'stipendTiers',
            'solLimit',
            'currentSol',
            'nexusDebt',
            'nexusDebtMax',
            'nexusDebtTone',
            'totalBuildings',
            'damagedBuildings',
            'lastSolDeltas',
            'advisors',
            'trustEvents',
        ));
    }
}
