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
        private readonly TickService $tickService,
        private readonly EventService $eventService,
        private readonly ColonyTileService $tileService,
    ) {}

    /**
     * Full setup for a newly registered player.
     *
     * Creates a brand-new colony (no existing colony for this user) and seeds
     * it to the canonical Sol-1 starting state.
     *
     * @throws \RuntimeException when no free planet is available
     */
    public function setupNewPlayer(int $userId, string $colonyName = ''): Colony
    {
        return DB::transaction(function () use ($userId, $colonyName) {
            $name = $colonyName ?: 'Kolonie';

            $globalTick = $this->tickService->getTickCount();
            $colony = $this->colonyService->createColony($userId, $name, $globalTick);

            $this->seedSol1State($userId, $colony->id);

            return $colony;
        });
    }

    /**
     * Resets an existing colony to the canonical Sol-1 starting state.
     *
     * Used when a player abandons their current run and starts a new one
     * from the lobby (the colony record itself is kept — only its game state
     * is wiped and re-seeded). This is the same Sol-1 state produced by
     * setupNewPlayer() for a brand-new colony, so both paths stay in sync.
     *
     * Advisors are detached from the colony (colony_id = null) rather than
     * deleted — the player keeps earned advisors across runs. Use
     * ResetPlayer (dev tool) instead if advisors must be wiped entirely.
     */
    public function resetColonyToSol1(int $userId, int $colonyId): void
    {
        DB::transaction(function () use ($userId, $colonyId) {
            DB::table('colony_resources')->where('colony_id', $colonyId)->delete();
            DB::table('colony_buildings')->where('colony_id', $colonyId)->delete();
            DB::table('colony_tiles')->where('colony_id', $colonyId)->delete();
            DB::table('colony_ships')->where('colony_id', $colonyId)->delete();
            DB::table('colony_researches')->where('colony_id', $colonyId)->delete();
            DB::table('colony_personell')->where('colony_id', $colonyId)->delete();
            DB::table('trade_resources')->where('colony_id', $colonyId)->delete();
            DB::table('trust_events')->where('colony_id', $colonyId)->delete();
            DB::table('merchant_visits')->where('colony_id', $colonyId)->delete();
            DB::table('colony_hangar_missions')->where('colony_id', $colonyId)->delete();
            DB::table('locked_actionpoints')
                ->where('scope_type', 'colony')
                ->where('scope_id', $colonyId)
                ->delete();
            DB::table('colony_log')->where('user', $userId)->delete();
            DB::table('user_preferences')->where('user_id', $userId)->delete();

            // Advisors stay with the player across runs — detach, don't delete.
            DB::table('advisors')->where('colony_id', $colonyId)->update(['colony_id' => null]);

            $this->seedSol1State($userId, $colonyId);
        });
    }

    /**
     * Shared Sol-1 seed routine: resources, starting buildings, starting
     * tiles (incl. zone assignment), Nexus briefing, and the active Run
     * record. Assumes all prior colony state has already been cleared by
     * the caller.
     */
    private function seedSol1State(int $userId, int $colonyId): void
    {
        $this->seedResources($userId, $colonyId);
        $this->seedStartingBuilding($colonyId);
        $this->seedStartingTiles($colonyId);
        $this->eventService->createNexusBriefing($userId, 0, $colonyId);

        Run::create([
            'user_id' => $userId,
            'colony_id' => $colonyId,
            'current_tick' => 0,
            'status' => 'active',
            'started_at' => null, // set when player clicks "Mission starten" in lobby
            'phase' => 1,
            'nexus_debt' => 3000, // matches runs.nexus_debt column default (GDD §15 startup loan)
            'settings' => [
                'tick_limit' => config('game.run.tick_limit'),
                'bypass' => config('game.bypass'),
                'supply_cap_max' => config('game.supply.cap_max'),
                'max_players' => config('game.run.max_players'),
            ],
        ]);
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
        // Ring 0+1: explored from start. is_colony_zone assigned by assignColonyZone() below.
        // Ring 2: fog — auto-explored when CC is upgraded (assignColonyZone).
        // Ring 3+ (exploration zone): fog, except (3,0) pre-explored by Nexus Scout (Harvester target).
        // Regolith only on ring 3+ — no regolith inside colony zone.
        $tiles = [
            // ── Ring 0 ────────────────────────────────────────────────────────
            ['q' => 0, 'r' => 0, 'ring' => 0, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            // ── Ring 1 ────────────────────────────────────────────────────────
            ['q' => 1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => 0, 'r' => 1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => -1, 'r' => 1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => -1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => 0, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => 1, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 1],
            // ── Ring 2 (fog — unlocked by CC upgrade) ─────────────────────────
            ['q' => 2, 'r' => 0, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 2, 'r' => -1, 'ring' => 2, 'tile_type' => 'terrain_hazard',     'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 2, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 1, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_hazard',     'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 0, 'r' => -2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -1, 'r' => -1, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' => 0, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' => 1, 'ring' => 2, 'tile_type' => 'terrain_impassable', 'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -2, 'r' => 2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -1, 'r' => 2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 0, 'r' => 2, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 1, 'r' => 1, 'ring' => 2, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            // ── Ring 3 (exploration zone, fog) ────────────────────────────────
            ['q' => 3, 'r' => 0, 'ring' => 3, 'tile_type' => 'regolith_normal',    'is_colony_zone' => 0, 'is_explored' => 1],
            ['q' => 3, 'r' => -1, 'ring' => 3, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 3, 'r' => -2, 'ring' => 3, 'tile_type' => 'terrain_hazard',     'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 2, 'r' => 1, 'ring' => 3, 'tile_type' => 'regolith_poor',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 1, 'r' => 2, 'ring' => 3, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 0, 'r' => 3, 'ring' => 3, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -1, 'r' => 3, 'ring' => 3, 'tile_type' => 'regolith_poor',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => -3, 'r' => 0, 'ring' => 3, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
            ['q' => 0, 'r' => -3, 'ring' => 3, 'tile_type' => 'terrain_empty',      'is_colony_zone' => 0, 'is_explored' => 0],
        ];

        $rows = array_map(fn ($t) => array_merge($t, ['colony_id' => $colonyId]), $tiles);
        DB::table('colony_tiles')->insert($rows);

        // Assign colony zone based on CC Level 1 — auto-explores ring 0+1.
        $this->tileService->assignColonyZone($colonyId, 1);
    }

    private function seedStartingBuilding(int $colonyId): void
    {
        // All three start at level 1 but with reduced status (16/20 = 80%) —
        // functional but visibly damaged. Player repairs them via the Reparieren
        // button (1 Construction-AP per click); natural decay makes repair
        // critical within 5-10 Sols.
        DB::table('colony_buildings')->insert([
            [
                'colony_id' => $colonyId,
                'building_id' => 25, // CommandCenter
                'level' => 1,
                'status_points' => 16,
                'ap_spend' => 0,
                'tile_x' => null,
                'tile_y' => null,
            ],
            [
                'colony_id' => $colonyId,
                'building_id' => 27, // Harvester
                'level' => 1,
                'status_points' => 16,
                'ap_spend' => 0,
                'tile_x' => 1,
                'tile_y' => 0,
            ],
            [
                'colony_id' => $colonyId,
                'building_id' => 28, // HousingComplex
                'level' => 1,
                'status_points' => 16,
                'ap_spend' => 0,
                'tile_x' => 0,
                'tile_y' => 1,
            ],
        ]);
    }
}
