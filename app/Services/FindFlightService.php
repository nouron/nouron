<?php

namespace App\Services;

/**
 * T30 §13.3 salvage flight: distance, costs, carry and yield — one source for the
 * dispatch guard, the tick payout, the hangar target list and the find card.
 * Yield lines are additive (base + flight_bonus) so later modifiers (§13.6 voucher)
 * join as further lines instead of changing the factor.
 */
class FindFlightService
{
    public const MISSION_KEY = 'mission_find_salvage';

    public function __construct(private readonly ProjectBonusService $projectBonusService) {}

    public function solDistance(int $ring): int
    {
        $byRing = config('missions.catalog.'.self::MISSION_KEY.'.sol_distance_by_ring', []);

        return (int) ($byRing[$ring] ?? max(1, $ring - 1));
    }

    /** @return array{sol_distance:int, nav_ap_base:int, nav_ap:int, organika:int, duration:int} */
    public function costs(int $colonyId, int $ring): array
    {
        $distance = $this->solDistance($ring);
        $navBase = $distance * (int) config('missions.nav_ap_per_sol', 2);

        return [
            'sol_distance' => $distance,
            'nav_ap_base' => $navBase,
            'nav_ap' => $this->projectBonusService->effectiveNavigationApCost($colonyId, $navBase),
            // Ungated mission without extra_cost: the generic HangarService::organikaCostFor()
            // formula reduces to this — pinned by FindFlightTest (preview = charge).
            'organika' => $distance * (int) config('missions.organika_per_sol', 3),
            'duration' => 2 * $distance,
        ];
    }

    /** @return array{base:int, flight_bonus:int, total:int} */
    public function findFlightYield(int $colonyId, string $findType): array
    {
        $base = (int) (config("game.finds.types.{$findType}.rg") ?? 0);
        $total = (int) round($base * (float) config('game.finds.flight.yield_factor', 1.0));

        return ['base' => $base, 'flight_bonus' => $total - $base, 'total' => $total];
    }

    public function canCarry(?string $shipKey, string $findType): bool
    {
        return $shipKey !== null && in_array($findType, config("ships.{$shipKey}.find_carry", []), true);
    }

    public function minHangarLevel(string $findType): int
    {
        return (int) config("game.finds.flight.min_hangar_level.{$findType}", 1);
    }
}
