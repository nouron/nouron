<?php

namespace App\Services\Techtree;

use Illuminate\Support\Facades\DB;

/**
 * Reverse lookup of the existing required_building_id/required_building_level
 * gate relationships (buildings/researches/ships/personell) — Owner-Playtest-
 * Fund 2026-08-31: the "Voraussetzung" ("Benötigt X Lv2") display already
 * walks this relationship in one direction; players also want the other
 * direction ("Hangar Lv2 unlocks the Frachter ship") to plan ahead before
 * investing AP. Purely derived from existing gate data, no new content.
 */
class BuildingUnlockService
{
    /**
     * Entities that become available specifically at $buildingId reaching $level
     * (not "at or below" — the level a player is about to invest AP into).
     *
     * @return list<string>
     */
    public function unlocksAtLevel(int $buildingId, int $level): array
    {
        $labels = [];

        foreach (['buildings', 'ships', 'personell'] as $table) {
            $names = DB::table($table)
                ->where('is_active', 1)
                ->where('required_building_id', $buildingId)
                ->where('required_building_level', $level)
                ->pluck('name');

            foreach ($names as $name) {
                $labels[] = __('techtree.'.$name);
            }
        }

        // researches has a second, independent requirement slot
        // (required_building2_id/required_building2_level) — either slot
        // reaching $buildingId/$level counts as "unlocked by this level".
        $researchNames = DB::table('researches')
            ->where('is_active', 1)
            ->where(function ($query) use ($buildingId, $level) {
                $query->where(function ($q) use ($buildingId, $level) {
                    $q->where('required_building_id', $buildingId)
                        ->where('required_building_level', $level);
                })->orWhere(function ($q) use ($buildingId, $level) {
                    $q->where('required_building2_id', $buildingId)
                        ->where('required_building2_level', $level);
                });
            })
            ->pluck('name');

        foreach ($researchNames as $name) {
            $labels[] = __('techtree.'.$name);
        }

        return $labels;
    }
}
