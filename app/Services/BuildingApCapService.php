<?php

namespace App\Services;

use App\Models\Advisor;
use Illuminate\Support\Facades\DB;

/**
 * Per-building per-Sol cap on construction AP invested toward a level-up
 * (game.build.ap_cap_per_sol). Cap = base + engineer rank bonus; a null base switches
 * the rule off. Repair AP and placement are not subject to it.
 */
class BuildingApCapService
{
    public function __construct(private readonly TickService $tickService) {}

    /** Cap for the colony this Sol, or null when the rule is off. */
    public function capFor(int $colonyId): ?int
    {
        $base = config('game.build.ap_cap_per_sol.base');
        if ($base === null) {
            return null;
        }

        $tick = $this->tickService->getTickCount();
        $engineer = Advisor::where('colony_id', $colonyId)
            ->where('personell_id', AdvisorService::idFor('engineer'))
            ->first();
        $bonus = 0;
        if ($engineer !== null && $engineer->isAvailable($tick)) {
            $bonus = (int) config('game.build.ap_cap_per_sol.engineer_bonus_per_rank.'.$engineer->rank, 0);
        }

        return (int) $base + $bonus;
    }

    public function spentThisSol(int $colonyId, int $buildingId, int $instanceId): int
    {
        $row = DB::table('colony_buildings')
            ->where(['colony_id' => $colonyId, 'building_id' => $buildingId, 'instance_id' => $instanceId])
            ->first(['ap_cap_tick', 'ap_cap_spent']);

        return $this->spentFromRow($row);
    }

    /**
     * Cap state for one building row, or null when the rule is off.
     *
     * @return array{cap: int, spent: int, remaining: int}|null
     */
    public function state(int $colonyId, int $buildingId, int $instanceId): ?array
    {
        $row = DB::table('colony_buildings')
            ->where(['colony_id' => $colonyId, 'building_id' => $buildingId, 'instance_id' => $instanceId])
            ->first(['ap_cap_tick', 'ap_cap_spent']);

        return $this->stateFromRow($this->capFor($colonyId), $row);
    }

    /**
     * Cap state from an already loaded colony_buildings row (needs ap_cap_tick/ap_cap_spent)
     * and a cap resolved once via capFor() — list paths use this to avoid one query per row.
     *
     * @return array{cap: int, spent: int, remaining: int}|null
     */
    public function stateFromRow(?int $cap, ?object $row): ?array
    {
        if ($cap === null) {
            return null;
        }
        $spent = $this->spentFromRow($row);

        return ['cap' => $cap, 'spent' => $spent, 'remaining' => max(0, $cap - $spent)];
    }

    private function spentFromRow(?object $row): int
    {
        // The counter belongs to the Sol stored beside it; a stale tick means nothing spent today.
        if ($row === null || $row->ap_cap_tick === null || (int) $row->ap_cap_tick !== $this->tickService->getTickCount()) {
            return 0;
        }

        return (int) $row->ap_cap_spent;
    }

    /** True when $ap more points would exceed this Sol's cap for the row. */
    public function wouldExceed(int $colonyId, int $buildingId, int $instanceId, int $ap = 1): bool
    {
        $state = $this->state($colonyId, $buildingId, $instanceId);

        return $state !== null && $state['remaining'] < $ap;
    }

    /** Book $ap invested into the row this Sol (no-op when the rule is off). */
    public function record(int $colonyId, int $buildingId, int $instanceId, int $ap): void
    {
        if ($ap <= 0 || $this->capFor($colonyId) === null) {
            return;
        }

        DB::table('colony_buildings')
            ->where(['colony_id' => $colonyId, 'building_id' => $buildingId, 'instance_id' => $instanceId])
            ->update([
                'ap_cap_spent' => $this->spentThisSol($colonyId, $buildingId, $instanceId) + $ap,
                'ap_cap_tick' => $this->tickService->getTickCount(),
            ]);
    }
}
