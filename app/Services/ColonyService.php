<?php

namespace App\Services;

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
     * The id is assigned by the database (auto-increment), so concurrent sign-ups
     * cannot collide. Writes go to the base table, not the v_glx_colonies view.
     * There is no galaxy/system map any more — a colony has no coordinates
     * (single home site per player).
     */
    public function createColony(int $userId, string $name): Colony
    {
        $id = DB::table('glx_colonies')->insertGetId([
            'name' => $name,
            'user_id' => $userId,
            'is_primary' => 1,
        ]);

        return Colony::findOrFail($id);
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
        if ((int) $run->phase === 1) {
            $criteria = $this->getPhase1CriteriaLabeled($colonyId, (int) $run->current_tick);

            return ['phase' => 1, 'criteria' => $criteria];
        }

        $taskCatalog = app(RunTaskCatalog::class);

        $objectives = DB::table('run_objectives')
            ->where('run_id', $run->id)
            ->orderBy('id')
            ->get(['task_key', 'current_value', 'target_value', 'completed_at'])
            ->map(function ($obj, int $index) use ($taskCatalog): array {
                // The first drawn objective is open from the start (Owner 2026-10-25, B5);
                // the others stay hidden until their progress is above zero.
                $revealed = $index === 0 || (int) $obj->current_value > 0 || $obj->completed_at !== null;

                return [
                    'revealed' => $revealed,
                    'label' => $revealed ? $taskCatalog->label($obj->task_key, (int) $obj->target_value) : null,
                    'current' => (int) $obj->current_value,
                    'target' => (int) $obj->target_value,
                    'done' => $obj->completed_at !== null,
                ];
            })
            ->values()
            ->all();

        return ['phase' => 2, 'objectives' => $objectives];
    }

    /**
     * Listed Phase-1 criteria with player-facing labels.
     *
     * Harvester, Wohnhabitat and Agrardom still gate the phase end (phase1Criteria)
     * but are not listed — they are the obvious first builds.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPhase1CriteriaLabeled(int $colonyId, int $currentTick): array
    {
        $hidden = ['harvester', 'housing', 'agrardom'];

        return array_values(array_map(
            fn (array $c): array => $c + ['label' => __('colony.sol_report_phase1_'.$c['key'])],
            array_filter(
                app(RunProgressService::class)->phase1Criteria($colonyId, $currentTick),
                fn (array $c): bool => ! in_array($c['key'], $hidden, true)
            )
        ));
    }
}
