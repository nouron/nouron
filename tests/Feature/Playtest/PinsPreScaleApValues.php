<?php

namespace Tests\Feature\Playtest;

use Illuminate\Support\Facades\DB;

/**
 * Bot mechanism tests were written against the AP numbers before the AP scale 2026-11-13
 * (pool 12, caps 4, levelups 10 AP, ...). They test the bot's decision logic, not balance,
 * so they pin those numbers instead of re-deriving every fixture. Call after the seeder ran
 * (the building levelup AP lives in the buildings table).
 */
trait PinsPreScaleApValues
{
    protected function pinPreScaleApValues(): void
    {
        config([
            'game.ap.base' => 12,
            'game.advisor.ap_per_rank' => [1 => 2, 2 => 3, 3 => 4],
            'game.advisor.cap_bonus_per_rank' => [1 => 2, 2 => 3, 3 => 4],
            'game.build.ap_cap_per_sol.base' => 4,
            'game.knowledge_ap_cap_per_sol.base' => 4,
            'path_depth.training_ap_cap_per_sol.base' => 4,
            'buildings.commandCenter.cc_upgrade_ap_by_level' => [2 => 20, 3 => 40, 4 => 60, 5 => 80],
            'game.colony.explore_cost_per_ring' => [1 => 1, 2 => 2, 3 => 3],
            'game.finds.types.find_small.ap' => 3,
            'game.finds.types.find_medium.ap' => 4,
            'game.finds.types.find_large.ap' => 6,
            'game.finds.scan_ap' => 2,
            'game.finds.free_scan_uplink_level' => null,
            'missions.ap_by_distance' => [1 => 2, 2 => 4, 3 => 6, 4 => 8, 5 => 10],
            'game.harvester.relocate_ap_per_hex' => 2,
        ]);
        foreach (array_keys(config('knowledge')) as $key) {
            config(["knowledge.{$key}.levelup_costs" => [1 => 20, 2 => 28, 3 => 36, 4 => 44, 5 => 52]]);
        }
        DB::table('buildings')->where('ap_for_levelup', 4)->update(['ap_for_levelup' => 10]);
        DB::table('buildings')->where('ap_for_levelup', 8)->update(['ap_for_levelup' => 20]);
    }
}
