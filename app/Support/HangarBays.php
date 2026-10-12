<?php

namespace App\Support;

/**
 * Hangar B1 (T30 spec §13.2): one hall, level n = ship class n AND n bays.
 * Bay 0 is the apron, reserved for the gift drone. Pure rules without DB access —
 * shared by HangarService, ColonyTileService::findAction() and the PlaytestBot so
 * neither service has to inject the other.
 */
final class HangarBays
{
    public const APRON = 0;

    public const MAX_BAY = 3;

    /** ship DB id => hangar level the class needs (Lv1 drone, Lv2 freighter, Lv3 corvette) */
    private const REQUIRED_LEVEL = [85 => 1, 47 => 2, 37 => 3];

    public static function requiredLevel(int $shipId): int
    {
        return self::REQUIRED_LEVEL[$shipId] ?? 1;
    }

    /** The apron opens with level 1, bay n with level n. */
    public static function bayOpen(int $bay, int $hangarLevel): bool
    {
        return $hangarLevel >= max(1, $bay);
    }

    /** GDD §7 + B1: inactive while the hall is below max(class level, bay); the apron counts as 1. */
    public static function isInactive(int $shipId, int $hangarLevel, int $bay): bool
    {
        return $hangarLevel < max(self::requiredLevel($shipId), $bay, 1);
    }

    public static function maxStatus(): int
    {
        return (int) config('game.ship_repair.max_status_points', 20);
    }

    /**
     * T30 §13.x/7 return guarantee: a ship may start only if it comes home with SP > 0
     * (wear_per_sol × 2 × sol_distance). One function for the dispatch guard, the hangar
     * target list, ColonyTileService::findAction() and the PlaytestBot.
     *
     * @return array{ready: bool, wear: float, sp_after: float, repair_clicks: int, repair_ap: int}
     */
    public static function flightReadiness(int $shipId, float $statusPoints, int $solDistance): array
    {
        $wear = round((float) config('ships.'.self::shipKey($shipId).'.wear_per_sol', 1.0) * 2 * $solDistance, 2);
        $spAfter = round($statusPoints - $wear, 2);
        $perClick = max(1, (int) config('game.ship_repair.sp_per_click', 2));
        $clicks = $spAfter > 0 ? 0 : (int) floor(-$spAfter / $perClick) + 1;

        return [
            'ready' => $spAfter > 0,
            'wear' => $wear,
            'sp_after' => $spAfter,
            'repair_clicks' => $clicks,
            'repair_ap' => $clicks * (int) config('game.ship_repair.ap_per_click', 1),
        ];
    }

    /** config/ships.php key for a ship DB id. */
    public static function shipKey(int $shipId): ?string
    {
        foreach (config('ships', []) as $key => $cfg) {
            if ((int) $cfg['id'] === $shipId) {
                return $key;
            }
        }

        return null;
    }
}
