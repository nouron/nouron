<?php

namespace App\Services;

use App\Enums\BuildingId;
use App\Events\RunStarted;
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

            // Close any pre-existing active run before seeding a new one — the
            // singleplayer invariant is exactly one active run per user. The
            // caller (LobbyController::newRun()) already guards against this in
            // the normal player flow, but other callers (dev tools, test
            // harnesses) invoke this method directly without that guard — found
            // 2026-08-17 via a PlaytestBot tick/sol offset caused by a stale
            // active run left dangling here.
            DB::table('runs')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update(['status' => 'failed', 'fail_reason' => 'superseded', 'ended_at' => now()]);

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

        $run = Run::create([
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
            'rng_seed' => random_int(1, PHP_INT_MAX),
        ]);

        event(new RunStarted($run));
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
            // 200 → 300 → 340 (GDD §13.7 Nachtrag 2026-08-12 + Korrektur 2026-08-13):
            // die erste Bedarfsrechnung hatte den 0→1-Level-Up-Schritt (25 Rg,
            // ColonyController::LEVELUP_REGOLITH_FLAT) für die Pfadgebäude komplett
            // und für bioFacility teilweise unterschlagen — korrigierte Bedarfssumme
            // 535 Rg statt 500. Verschiebt den Floor auf ≈Sol 15,1.
            // 340 → 370 (Nachtrag 2026-08-16, game-designer review): GDD §9-Begegnungen
            // können jetzt auch in Phase 1 einen Kritisch-Tier-Sturm-Treffer landen
            // (Ø ~77,5 Rg Verlust, Band 60-95). +30 Puffer deckt ~40% eines typischen
            // Treffers ab, verschiebt den No-Storm-Floor auf ≈Sol 12,9 — bewusst kein
            // Vollschutz (das wäre eine Überkorrektur, siehe §13.7-Warnung bei 400).
            ['resource_id' => 3,  'colony_id' => $colonyId, 'amount' => 370],  // regolith
            ['resource_id' => 4,  'colony_id' => $colonyId, 'amount' => 0],    // werkstoffe — produced by harvester
            ['resource_id' => 5,  'colony_id' => $colonyId, 'amount' => 0],    // organika  — produced by bioFacility
            ['resource_id' => 12, 'colony_id' => $colonyId, 'amount' => 0],    // trust
        ];

        DB::table('colony_resources')->insert($colonyResources);
    }

    private function seedStartingTiles(int $colonyId): void
    {
        // Ring 0+1: fixed every run — building placement safety (CC/Harvester/Housing
        // land here) and the "settled core has no hazards" design rule. is_colony_zone
        // assigned by assignColonyZone() below.
        // Ring 2+3: randomized every call (roguelike) — see
        // ColonyTileService::randomizeOuterRingRows(). Exactly one ring-3 tile is
        // guaranteed pre-explored regolith (Nexus-Scout Harvester relocation target).
        //
        // (1,0) — the bootstrap Harvester's starting tile — stays terrain_empty, NOT
        // regolith: colony-zone tiles are never regolith by design (computeColonyZoneCoords
        // explicitly skips regolith_* — a zone tile hosts buildings, not extraction), and
        // onboarding's hint_2 ("Harvester in colony zone → move it out", checkHint2())
        // already exists specifically to teach the player to relocate it. Under the §4c
        // depletion mechanic (2026-08-03) this means the Sol-1 Harvester produces 0
        // Regolith until that relocation happens — a real pacing change from the old
        // level-curve mechanic (which produced regardless of tile), not a bug: it makes
        // hint_2 a hard economic step instead of a soft nudge. Flagged for game-designer/
        // owner confirmation — see HarvesterSol1BootstrapTest and the session report.
        $tiles = [
            // ── Ring 0 ────────────────────────────────────────────────────────
            ['q' => 0, 'r' => 0, 'ring' => 0, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            // ── Ring 1 ────────────────────────────────────────────────────────
            ['q' => 1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            ['q' => 0, 'r' => 1, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            ['q' => -1, 'r' => 1, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            ['q' => -1, 'r' => 0, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            ['q' => 0, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
            ['q' => 1, 'r' => -1, 'ring' => 1, 'tile_type' => 'terrain_empty', 'is_colony_zone' => 0, 'is_explored' => 1, 'resource_amount' => null, 'resource_max' => null],
        ];

        $tiles = array_merge($tiles, $this->tileService->randomizeOuterRingRows());

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
                'building_id' => BuildingId::CommandCenter->value,
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
