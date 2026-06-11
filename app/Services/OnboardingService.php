<?php

namespace App\Services;

use App\Models\Colony;
use App\Models\Run;
use Illuminate\Support\Facades\DB;

/**
 * OnboardingService — sets up a new player's initial game state.
 *
 * Called once after registration. Creates the player's colony on a free
 * planet, seeds starting resources, and places the CommandCenter at level 1.
 */
class OnboardingService
{
    public function __construct(
        private readonly ColonyService $colonyService,
        private readonly TickService   $tickService,
        private readonly EventService  $eventService,
    ) {}

    /**
     * Full setup for a newly registered player.
     *
     * @throws \RuntimeException when no free planet is available
     */
    public function setupNewPlayer(int $userId, string $colonyName = ''): Colony
    {
        return DB::transaction(function () use ($userId, $colonyName) {
            $name = $colonyName ?: 'Kolonie';

            $globalTick = $this->tickService->getTickCount();
            $colony     = $this->colonyService->createColony($userId, null, $name, $globalTick);

            $this->seedResources($userId, $colony->id);
            $this->seedStartingBuilding($colony->id);
            $this->seedStartingTiles($colony->id);
            $this->eventService->createNexusBriefing($userId, 0, $colony->id);

            Run::create([
                'user_id'      => $userId,
                'colony_id'    => $colony->id,
                'current_tick' => 0,
                'status'       => 'active',
                'started_at'   => null, // set when player clicks "Mission starten" in lobby
                'settings'     => [
                    'tick_limit'     => config('game.run.tick_limit'),
                    'bypass'         => config('game.bypass'),
                    'supply_cap_max' => config('game.supply.cap_max'),
                    'max_players'    => config('game.run.max_players'),
                ],
            ]);

            return $colony;
        });
    }

    private function seedResources(int $userId, int $colonyId): void
    {
        // User-level resources (credits + supply)
        DB::table('user_resources')->updateOrInsert(
            ['user_id' => $userId],
            ['credits' => 3000, 'supply' => 15]  // supply = CC level 1 flat cap
        );

        // Colony-level resources.
        // Werkstoffe (4) and Organika (5) start at 0 — produced by Harvester/bioFacility.
        $colonyResources = [
            ['resource_id' => 3,  'colony_id' => $colonyId, 'amount' => 200],  // regolith
            ['resource_id' => 4,  'colony_id' => $colonyId, 'amount' => 0],    // werkstoffe — produced by harvester
            ['resource_id' => 5,  'colony_id' => $colonyId, 'amount' => 0],    // organika  — produced by bioFacility
            ['resource_id' => 12, 'colony_id' => $colonyId, 'amount' => 0],    // trust
        ];

        DB::table('colony_resources')->insert($colonyResources);
    }

    private function seedStartingTiles(int $colonyId): void
    {
        // Ring 0 (CC center) + ring 1 (6 tiles): colony_zone, explored.
        // Ring 2 (12 tiles): fog of war, not colony_zone.
        $tiles = [
            // ── Ring 0 ────────────────────────────────────────────────────────
            ['q' =>  0, 'r' =>  0, 'ring' => 0, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            // ── Ring 1 ────────────────────────────────────────────────────────
            ['q' =>  1, 'r' =>  0, 'ring' => 1, 'tile_type' => 'regolith_normal',    'is_colony_zone' => 1, 'is_explored' => 1],
            ['q' =>  0, 'r' =>  1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            ['q' => -1, 'r' =>  1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            ['q' => -1, 'r' =>  0, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            ['q' =>  0, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            ['q' =>  1, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 1, 'is_explored' => 1],
            // ── Ring 2 (fog) ──────────────────────────────────────────────────
            ['q' =>  2, 'r' =>  0, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  2, 'r' => -1, 'ring' => 2, 'tile_type' => 'regolith_poor',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  2, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  1, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_hazard',     'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  0, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -1, 'r' => -1, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' =>  0, 'ring' => 2, 'tile_type' => 'regolith_poor',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' =>  1, 'ring' => 2, 'tile_type' => 'terrain_impassable', 'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' =>  2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -1, 'r' =>  2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  0, 'r' =>  2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' =>  1, 'r' =>  1, 'ring' => 2, 'tile_type' => 'regolith_poor',      'is_colony_zone' => 0, 'is_explored' => 0],
        ];

        $rows = array_map(fn($t) => array_merge($t, ['colony_id' => $colonyId]), $tiles);
        DB::table('colony_tiles')->insert($rows);
    }

    private function seedStartingBuilding(int $colonyId): void
    {
        // All three start at level 1 but with reduced status (16/20 = 80%) —
        // functional but visibly damaged. Repair mechanic is a future feature;
        // natural decay will make repair critical within 5-10 Sols.
        DB::table('colony_buildings')->insert([
            [
                'colony_id'     => $colonyId,
                'building_id'   => 25, // CommandCenter
                'level'         => 1,
                'status_points' => 16,
                'ap_spend'      => 0,
                'tile_x'        => null,
                'tile_y'        => null,
            ],
            [
                'colony_id'     => $colonyId,
                'building_id'   => 27, // Harvester
                'level'         => 1,
                'status_points' => 16,
                'ap_spend'      => 0,
                'tile_x'        => 1,
                'tile_y'        => 0,
            ],
            [
                'colony_id'     => $colonyId,
                'building_id'   => 28, // HousingComplex
                'level'         => 1,
                'status_points' => 16,
                'ap_spend'      => 0,
                'tile_x'        => 0,
                'tile_y'        => 1,
            ],
        ]);
    }
}
