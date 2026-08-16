<?php

namespace App\Services;

/**
 * Resolves a colonist-danger outcome (GDD §9 "Begegnungen & Gefahren") from a
 * building's current status_points — no enemy strength, no combat roll. Pure
 * decision logic: takes state, returns state deltas, writes nothing itself.
 * The caller (GameTick) applies the returned sp_after and, if forces_level_down
 * is true, runs the same level-down mechanics processBuildingDecay() already uses.
 */
class EncounterService
{
    /**
     * @return array{tier: string, trust_event: string, sp_after: int, forces_level_down: bool}
     */
    public function resolveOutcome(int $statusPoints, int $maxStatusPoints, bool $securityHubActive): array
    {
        $ratio = $maxStatusPoints > 0 ? $statusPoints / $maxStatusPoints : 0.0;
        $damagedThreshold = (float) config('game.encounter.damaged_threshold_pct', 0.66);
        $criticalThreshold = (float) config('game.encounter.critical_threshold_pct', 0.33);
        $mitigationPct = $securityHubActive
            ? (float) config('buildings.securityHub.event_mitigation_pct', 0.25)
            : 0.0;

        if ($ratio >= $damagedThreshold) {
            return [
                'tier' => 'abgewehrt',
                'trust_event' => 'encounter_won',
                'sp_after' => $statusPoints,
                'forces_level_down' => false,
            ];
        }

        if ($ratio >= $criticalThreshold) {
            $lossPct = (float) config('game.encounter.damaged_sp_loss_pct', 0.20);
            $loss = (int) round($maxStatusPoints * $lossPct * (1 - $mitigationPct));
            $spAfter = max(0, $statusPoints - $loss);

            return [
                'tier' => 'beschaedigt',
                'trust_event' => 'encounter_lost',
                'sp_after' => $spAfter,
                'forces_level_down' => $spAfter <= 0,
            ];
        }

        return [
            'tier' => 'kritisch',
            'trust_event' => 'colony_threatened',
            'sp_after' => 0,
            'forces_level_down' => true,
        ];
    }
}
