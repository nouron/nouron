<?php

namespace App\Services;

use App\Models\Advisor;
use App\Models\Colony;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;

/**
 * ColonyService — Laravel port of Colony\Service\ColonyService.
 *
 * Reads colonies from v_glx_colonies (a passthrough view over glx_colonies;
 * the galaxy/system map was removed, so colonies have no coordinates).
 */
class ColonyService
{
    use ValidatesId;

    public function getColonies(): Collection
    {
        return Colony::all();
    }

    public function getColony(int|string $colonyId): Colony|false
    {
        $this->validateId($colonyId);

        return Colony::find($colonyId) ?? false;
    }

    public function getColoniesByUserId(int|string $userId): Collection
    {
        $this->validateId($userId);

        return Colony::where('user_id', $userId)->get();
    }

    /**
     * @param  Colony|int  $colony  colony object or ID
     */
    public function checkColonyOwner(Colony|int $colony, int $userId): bool
    {
        if (is_int($colony)) {
            $colony = $this->getColony($colony);
        }

        return $colony && $colony->user_id == $userId;
    }

    /**
     * Returns the primary colony for $userId.
     *
     * If the user has exactly one colony, it is treated as primary
     * (is_primary is auto-set when only one colony exists).
     *
     * @throws RuntimeException when no primary colony is found
     */
    public function getPrimeColony(int|string $userId): Colony
    {
        $this->validateId($userId);
        $colonies = $this->getColoniesByUserId((int) $userId);

        if ($colonies->count() === 1) {
            return $colonies->first();
        }

        $primary = $colonies->first(fn (Colony $c) => $c->is_primary);
        if ($primary) {
            return $primary;
        }

        throw new RuntimeException('No primary colony found for user.');
    }

    /**
     * Store the active colony in the session (only if the colony belongs to
     * the currently active user).
     */
    public function setActiveColony(int $selectedColonyId): void
    {
        $userId = Session::get('activeIds.userId');
        if ($this->checkColonyOwner($selectedColonyId, $userId)) {
            Session::put('activeIds.colonyId', $selectedColonyId);
        }
    }

    public function setSelectedColony(int $selectedColonyId): void
    {
        Session::put('selectedIds.colonyId', $selectedColonyId);
    }

    /**
     * Create a new colony row in glx_colonies.
     *
     * glx_colonies uses a manual integer PK (not auto-increment). Writes go to the
     * base table, not the v_glx_colonies view. There is no galaxy/system map any
     * more — a colony has no coordinates (single home site per player).
     */
    public function createColony(int $userId, string $name, int $sinceTick = 0): Colony
    {
        $nextId = (int) DB::table('glx_colonies')->max('id') + 1;

        DB::table('glx_colonies')->insert([
            'id' => $nextId,
            'name' => $name,
            'user_id' => $userId,
            'since_tick' => $sinceTick,
            'is_primary' => 1,
        ]);

        return Colony::findOrFail($nextId);
    }

    /**
     * Phase-1/Phase-2 progress toward advancing the run. Shared by the
     * Kommandozentrale dashboard and SolReportService (end-of-Sol report) —
     * single source of truth so both screens agree on the same criteria/objectives.
     */
    public function getPhaseProgress(Colony $colony): array
    {
        $run = DB::table('runs')
            ->where('colony_id', $colony->id)
            ->where('status', 'active')
            ->select('id', 'phase', 'current_tick')
            ->first();

        if (! $run) {
            return ['phase' => 1, 'criteria' => []];
        }

        $colonyId = $colony->id;
        $ccId = config('buildings.commandCenter.id', 25);

        if ((int) $run->phase === 1) {
            $ccLevel = (int) (DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', $ccId)
                ->value('level') ?? 0);

            $buildingsLv2 = DB::table('colony_buildings')
                ->where('colony_id', $colonyId)
                ->where('building_id', '!=', $ccId)
                ->where('level', '>=', 2)
                ->count();

            $advisorCount = Advisor::where('colony_id', $colonyId)
                ->where(function ($q) use ($run): void {
                    $q->whereNull('unavailable_until_tick')
                        ->orWhere('unavailable_until_tick', '<', $run->current_tick);
                })
                ->count();

            return [
                'phase' => 1,
                'criteria' => [
                    [
                        'key' => 'cc_level',
                        'label' => __('colony.sol_report_phase1_cc'),
                        'current' => min($ccLevel, 3),
                        'target' => 3,
                        'done' => $ccLevel >= 3,
                    ],
                    [
                        'key' => 'buildings_lv2',
                        'label' => __('colony.sol_report_phase1_buildings'),
                        'current' => min($buildingsLv2, 2),
                        'target' => 2,
                        'done' => $buildingsLv2 >= 2,
                    ],
                    [
                        'key' => 'advisors',
                        'label' => __('colony.sol_report_phase1_advisors'),
                        'current' => min($advisorCount, 3),
                        'target' => 3,
                        'done' => $advisorCount >= 3,
                    ],
                ],
            ];
        }

        $objectives = DB::table('run_objectives')
            ->where('run_id', $run->id)
            ->orderBy('id')
            ->get(['task_key', 'current_value', 'target_value', 'completed_at'])
            ->map(function ($obj): array {
                $revealed = (int) $obj->current_value > 0 || $obj->completed_at !== null;

                return [
                    'revealed' => $revealed,
                    'label' => $revealed ? __('run.'.$obj->task_key) : null,
                    'current' => (int) $obj->current_value,
                    'target' => (int) $obj->target_value,
                    'done' => $obj->completed_at !== null,
                ];
            })
            ->values()
            ->all();

        return ['phase' => 2, 'objectives' => $objectives];
    }
}
