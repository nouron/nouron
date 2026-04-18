<?php

namespace App\Services;

use App\Models\Colony;
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

            $freePlanet = DB::table('glx_system_objects')
                ->whereNotIn('id', DB::table('glx_colonies')->pluck('system_object_id'))
                ->value('id');

            if (!$freePlanet) {
                throw new \RuntimeException('No free planets available for new player.');
            }

            $tick   = $this->tickService->getTickCount();
            $colony = $this->colonyService->createColony($userId, $freePlanet, $name, $tick);

            $this->seedResources($userId, $colony->id);
            $this->seedStartingBuilding($colony->id);

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
            ['resource_id' => 12, 'colony_id' => $colonyId, 'amount' => 0],    // moral
        ];

        DB::table('colony_resources')->insert($colonyResources);
    }

    private function seedStartingBuilding(int $colonyId): void
    {
        // CommandCenter (building_id=25) at level 1 — operational base from day one.
        // Harvester (building_id=27) at level 1 — immediately starts producing Regolith.
        // status_points = max_status_points (20) so both are fully intact at start.
        DB::table('colony_buildings')->insert([
            [
                'colony_id'     => $colonyId,
                'building_id'   => 25,
                'level'         => 1,
                'status_points' => 20,
                'ap_spend'      => 0,
            ],
            [
                'colony_id'     => $colonyId,
                'building_id'   => 27,
                'level'         => 1,
                'status_points' => 20,
                'ap_spend'      => 0,
            ],
        ]);
    }
}
