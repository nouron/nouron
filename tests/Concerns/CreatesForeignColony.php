<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Creates a second player with their own colony for tests that must prove a
 * player cannot reach someone else's colony (cross-colony access guards).
 *
 * The fixture (data/sql/testdata.sqlite.sql) only contains player colonies of
 * the test users; the old playerless colony 2 "Shelbyville" was removed
 * (2026-09-23). Tests that need a foreign colony build it themselves here.
 */
trait CreatesForeignColony
{
    /**
     * @param  array<int, int>  $buildings  building_id => level
     * @return array{user_id: int, colony_id: int}
     */
    protected function createForeignColony(
        array $buildings = [25 => 1],
        int $colonyId = 2,
        int $userId = 20,
    ): array {
        DB::table('user')->insert([
            'user_id' => $userId,
            'username' => 'Foreigner'.$userId,
            'display_name' => '',
            'role' => 'player',
            'password' => bcrypt('secret'),
            'email' => 'foreigner'.$userId.'@nouron.de',
            'activated' => 1,
            'activation_key' => '',
        ]);

        DB::table('user_resources')->insert([
            'user_id' => $userId,
            'credits' => 1000,
            'supply' => 0,
        ]);

        DB::table('glx_colonies')->insert([
            'id' => $colonyId,
            'name' => 'Foreign Colony',
            'user_id' => $userId,
            'since_tick' => 1,
            'is_primary' => 1,
        ]);

        foreach ($buildings as $buildingId => $level) {
            DB::table('colony_buildings')->insert([
                'colony_id' => $colonyId,
                'building_id' => $buildingId,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }

        return ['user_id' => $userId, 'colony_id' => $colonyId];
    }
}
