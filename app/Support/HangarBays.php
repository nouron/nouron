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
