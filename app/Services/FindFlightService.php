<?php

namespace App\Services;

/**
 * T30 §13.3 salvage flight: distance, costs, carry and yield — one source for the
 * dispatch guard, the tick payout, the hangar target list and the find card.
 * Yield lines are additive (base + later modifiers, e.g. the §13.6 voucher).
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

    /** @return array{sol_distance:int, ap_cost:int, organika:int, duration:int} */
    public function costs(int $colonyId, int $ring): array
    {
        $distance = $this->solDistance($ring);

        return [
            'sol_distance' => $distance,
            'ap_cost' => $this->projectBonusService->effectiveNavigationApCost($colonyId, (int) config('game.finds.flight.ap_cost')),
            'organika' => $distance * (int) config('game.finds.flight.organika_per_sol'),
            'duration' => 2 * $distance,
        ];
    }

    /** @return array{base:int, total:int} */
    public function findFlightYield(int $colonyId, string $findType): array
    {
        $base = (int) (config("game.finds.types.{$findType}.rg") ?? 0);

        return ['base' => $base, 'total' => $base];
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
