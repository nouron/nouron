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
     * Returned in the same {text, chip} shape as
     * KnowledgeEffectDescriptionService::effectsAtLevel() — Owner-Playtest-Fund
     * 2026-09-04: the sidebar renders both "Voraussetzungen" and "Effekte der
     * nächsten Stufe" as chips, and a shared shape lets the Blade partial handle
     * buildings and knowledge with one code path. A gate unlock is always an
     * entity NAME (ship/building/knowledge), never a resource-valued curve like
     * knowledge's per-level effects, so `chip` is always null here — the caller
     * renders the neutral fallback chip for every entry.
     *
     * @return list<array{text: string, chip: null}>
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
                $labels[] = ['text' => __('techtree.'.$name), 'chip' => null];
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
            $labels[] = ['text' => __('techtree.'.$name), 'chip' => null];
        }

        return $labels;
    }
}
