<?php

namespace App\Console\Commands;

use App\Models\Colony;
use App\Models\Run;
use App\Models\User;
use App\Services\ColonyTileService;
use App\Services\OnboardingService;
use App\Services\RunProgressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

/**
 * ResetPlayer — wipes all game state for a user and re-runs onboarding.
 *
 * Usage (interactive — no args needed):
 *   php artisan game:reset-player            → user + scenario select menus
 *   php artisan game:reset-player bart       → scenario select menu
 *   php artisan game:reset-player bart --yes → scenario select, no confirm prompt
 *   php artisan game:reset-player bart --yes --scenario=phase2  → fully non-interactive
 *
 * Scenarios:
 *   fresh           Sol 1 clean start (default)
 *   pre-phase2      CC Lv3 + Agrardom Lv2 + Sciencelab Lv1 + 2 advisors — one hire away from Phase 2
 *   phase2          Phase 2 active (tick 15), 3 objectives drawn, realistic early-Phase-2 state
 *   near-fail-trust Phase 2, trust = −15 (threshold −20), depleted Organika reserves
 *   near-deadline   Phase 2, tick = 95 (5 sols left), upgraded buildings, 1 objective done
 *   objectives-done Phase 2, tick = 60, all 3 objectives completed, strong economy
 *
 * Resource logic per scenario:
 *   Harvester Lv1 = 10 Rg/Sol. BioFacility Lv2 = 20 Organika/Sol, food need ~3 → net +17.
 *   Werkstoffe: no production building — 0 until Uplink-Station (not seeded here).
 *   Supply cap: CC flat 10 + Housing_Lv × 8 + knowledge-level bonuses.
 *   Building costs Rg: CC 1→3 = 150, Agrardom place+Lv2 = 60, Sciencelab place+Lv1 = 100 → ~310 total.
 *   pre-phase2 seeds Rg=150 so player can immediately place Hangar (90 Rg path gate) and hire pilot.
 */
class ResetPlayer extends Command
{
    protected $signature = 'game:reset-player
        {user? : Username or user_id (omit for interactive select)}
        {--yes : Skip confirmation prompt}
        {--scenario= : fresh|pre-phase2|phase2|near-fail-trust|near-deadline|objectives-done (omit for interactive select)}';

    protected $description = 'Reset a player\'s game state (dev tool). Interactive when run without arguments.';

    private const VALID_SCENARIOS = [
        'fresh', 'pre-phase2', 'phase2', 'near-fail-trust', 'near-deadline', 'objectives-done',
    ];

    private const SCENARIO_LABELS = [
        'fresh' => 'Sol 1          — Neustart (Standard)',
        'pre-phase2' => 'Pre-Phase 2    — CC Lv3 + 2 Berater, 1 Hire fehlt für Phase-2-Trigger',
        'phase2' => 'Phase 2        — Tick 15, 3 Objectives offen, frühe Phase 2',
        'near-fail-trust' => 'Vertrauenskrise — Trust −15 (Grenze −20), Tick 30, Org-Reserven leer',
        'near-deadline' => 'Deadline       — Tick 95 (5 Sols verbleibend), 1 Objective erledigt',
        'objectives-done' => 'Fertig         — Tick 60, alle 3 Objectives abgeschlossen',
    ];

    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly RunProgressService $runProgressService,
        private readonly ColonyTileService $tileService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        // ── Resolve user ──────────────────────────────────────────────────────

        $input = $this->argument('user');
        $user = null;

        if ($input !== null) {
            $user = is_numeric($input)
                ? User::find((int) $input)
                : User::whereRaw('LOWER(username) = LOWER(?)', [$input])->first();
        }

        if (! $user) {
            // Auto-seed empty dev DB, then show select.
            if (DB::table('user')->count() === 0) {
                $this->warn('Dev DB appears empty — running db:seed automatically...');
                $this->call('db:seed');
            }

            if ($input !== null) {
                $this->error("User not found: {$input}");

                return self::FAILURE;
            }

            $rows = DB::table('user')->select('user_id', 'username')->orderBy('username')->get();

            if ($rows->isEmpty()) {
                $this->error('No users in database.');

                return self::FAILURE;
            }

            $chosen = select(
                label: 'Spieler',
                options: $rows->pluck('username', 'user_id')->toArray(),
            );

            $user = User::find((int) $chosen);
        }

        // ── Resolve scenario ──────────────────────────────────────────────────

        $scenario = $this->option('scenario');

        if ($scenario === null) {
            $scenario = select(
                label: 'Szenario',
                options: self::SCENARIO_LABELS,
                default: 'fresh',
            );
        }

        if (! in_array($scenario, self::VALID_SCENARIOS, true)) {
            $this->error("Unknown scenario: {$scenario}");
            $this->line('Valid: '.implode(', ', self::VALID_SCENARIOS));

            return self::FAILURE;
        }

        // ── Summary + confirmation ────────────────────────────────────────────

        $this->newLine();
        table(
            headers: ['Spieler', 'ID', 'Szenario'],
            rows: [[$user->username, $user->user_id, self::SCENARIO_LABELS[$scenario]]],
        );

        if (! $this->option('yes')) {
            if (! confirm('Alle Spielerdaten löschen und Szenario anwenden?', default: false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        DB::transaction(function () use ($user) {
            $colonyIds = DB::table('glx_colonies')
                ->where('user_id', $user->user_id)
                ->pluck('id');

            // Dev-tool-specific: advisors are fully deleted (not just detached) and
            // every colony/run owned by this user is wiped, not just the active one.
            // This is a superset of OnboardingService::resetColonyToSol1() — that
            // method detaches advisors and only touches the run being replaced,
            // which is correct for the lobby "new run" flow but not for this command.
            foreach ($colonyIds as $cid) {
                DB::table('colony_resources')->where('colony_id', $cid)->delete();
                DB::table('colony_buildings')->where('colony_id', $cid)->delete();
                DB::table('colony_tiles')->where('colony_id', $cid)->delete();
                // colony_log has no colony_id column — deleted below by user_id.
                DB::table('advisors')->where('colony_id', $cid)->delete();
                // locked_actionpoints is keyed by scope_type/scope_id, not colony_id.
                // SQLite would silently treat a nonexistent "colony_id" as a string
                // literal and delete nothing — so match the real columns.
                DB::table('locked_actionpoints')
                    ->where('scope_type', 'colony')
                    ->where('scope_id', $cid)
                    ->delete();
                DB::table('colony_ships')->where('colony_id', $cid)->delete();
                DB::table('colony_researches')->where('colony_id', $cid)->delete();
                DB::table('colony_personell')->where('colony_id', $cid)->delete();
                DB::table('trade_resources')->where('colony_id', $cid)->delete();
                DB::table('trust_events')->where('colony_id', $cid)->delete();
                DB::table('merchant_visits')->where('colony_id', $cid)->delete();
                DB::table('colony_hangar_missions')->where('colony_id', $cid)->delete();
            }

            // run_objectives is keyed by run_id, not colony_id.
            $runIds = DB::table('runs')->where('user_id', $user->user_id)->pluck('id');
            DB::table('run_objectives')->whereIn('run_id', $runIds)->delete();

            DB::table('runs')->where('user_id', $user->user_id)->delete();
            DB::table('glx_colonies')->where('user_id', $user->user_id)->delete();
            DB::table('user_resources')->where('user_id', $user->user_id)->delete();
            DB::table('user_preferences')->where('user_id', $user->user_id)->delete();
            DB::table('colony_log')->where('user', $user->user_id)->delete();

            $this->onboardingService->setupNewPlayer($user->user_id, 'Kolonie');
        });

        if ($scenario !== 'fresh') {
            $colony = Colony::where('user_id', $user->user_id)->latest('id')->firstOrFail();
            $run = Run::where('user_id', $user->user_id)->where('status', 'active')->latest('id')->firstOrFail();

            DB::transaction(fn () => $this->applyScenario($scenario, $colony, $run));

            $this->info("Scenario '{$scenario}' applied.");
        }

        $this->info("Player '{$user->username}' reset".($scenario !== 'fresh' ? " to scenario '{$scenario}'" : ' to Sol 1').'.');

        return self::SUCCESS;
    }

    // ── Scenario dispatcher ───────────────────────────────────────────────────

    private function applyScenario(string $scenario, Colony $colony, Run $run): void
    {
        match ($scenario) {
            'pre-phase2' => $this->scenarioPrePhase2($colony, $run),
            'phase2' => $this->scenarioPhase2($colony, $run),
            'near-fail-trust' => $this->scenarioNearFailTrust($colony, $run),
            'near-deadline' => $this->scenarioNearDeadline($colony, $run),
            'objectives-done' => $this->scenarioObjectivesDone($colony, $run),
            default => null,
        };
    }

    // ── Shared building/advisor setup ─────────────────────────────────────────

    /**
     * Place Phase-1-complete buildings + 2 advisors (engineer + scientist).
     * Does NOT touch resources or kenntnisse — each scenario sets those itself.
     *
     * Buildings:
     *   CC (25)        Lv3  at (0,0)  — colony zone expanded accordingly
     *   Harvester (27) Lv1  at (1,0)  — already seeded
     *   Housing (28)   Lv2  at (0,1)  — already seeded, upgraded
     *   Agrardom (41)  Lv2  at (0,-1) — inserted, mandatory Organika source
     *   Sciencelab (31)Lv1  at (-1,0) — inserted, path building A
     */
    private function placePhase1Buildings(Colony $colony): void
    {
        $cid = $colony->id;

        // Ensure tiles exist so building tile_x/tile_y references are valid.
        $this->tileService->generateDefaultTiles($colony);

        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 25)
            ->update(['level' => 3, 'status_points' => 20, 'tile_x' => 0, 'tile_y' => 0, 'placed_at_tick' => 1]);

        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 27)
            ->update(['status_points' => 20]);

        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 28)
            ->update(['level' => 2, 'status_points' => 20]);

        DB::table('colony_buildings')->insert([
            'colony_id' => $cid, 'building_id' => 41, 'instance_id' => 1,
            'level' => 2, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 0, 'tile_y' => -1, 'placed_at_tick' => 2,
        ]);

        DB::table('colony_buildings')->insert([
            'colony_id' => $cid, 'building_id' => 31, 'instance_id' => 1,
            'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => -1, 'tile_y' => 0, 'placed_at_tick' => 3,
        ]);

        $this->tileService->assignColonyZone($cid, 3);

        foreach ([
            (int) config('advisors.engineer.id', 35),
            (int) config('advisors.scientist.id', 36),
        ] as $pid) {
            DB::table('advisors')->insert([
                'user_id' => $colony->user_id, 'personell_id' => $pid,
                'colony_id' => $cid, 'rank' => 1,
                'active_ticks' => 5, 'unavailable_until_tick' => null,
            ]);
        }
    }

    /**
     * Insert Hangar (44) at tile (-1,1) Ring 1 — the path building for the pilot slot.
     * All phase-2+ scenarios need this so the pilot hire gate is satisfied.
     */
    private function placeHangar(Colony $colony, int $level, int $placedAtTick = 12): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $colony->id, 'building_id' => 44, 'instance_id' => 1,
            'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => -1, 'tile_y' => 1, 'placed_at_tick' => $placedAtTick,
        ]);
    }

    /** Insert Cantina/bar (52) at tile (1,-1) Ring 1. */
    private function placeCantina(Colony $colony, int $level, int $placedAtTick = 20): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $colony->id, 'building_id' => 52, 'instance_id' => 1,
            'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 1, 'tile_y' => -1, 'placed_at_tick' => $placedAtTick,
        ]);
    }

    /** Insert SecurityHub (53) at tile (0,2) Ring 2 — unlocks strategist slot 5. */
    private function placeSecurityHub(Colony $colony, int $level, int $placedAtTick = 25): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $colony->id, 'building_id' => 53, 'instance_id' => 1,
            'level' => $level, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 0, 'tile_y' => 2, 'placed_at_tick' => $placedAtTick,
        ]);
    }

    /**
     * Explore colony-zone tiles + rings 2 and 3 (optional), then relocate the
     * Harvester to the first explored non-colony-zone Regolith tile.
     * Ring-2 tiles in this game are often terrain_empty/impassable, so Ring-3 is
     * needed to reliably find a regolith tile.
     */
    private function exploreTilesAndMoveHarvester(Colony $colony, bool $includeRing3 = false): void
    {
        $cid = $colony->id;
        $maxRing = $includeRing3 ? 3 : 2;

        DB::table('colony_tiles')
            ->where('colony_id', $cid)
            ->where(function ($q) use ($maxRing): void {
                $q->where('is_colony_zone', 1)
                    ->orWhere('ring', '<=', $maxRing);
            })
            ->update(['is_explored' => 1]);

        $rg = DB::table('colony_tiles')
            ->where('colony_id', $cid)
            ->where('is_colony_zone', 0)
            ->where('ring', '<=', $maxRing)
            ->where('tile_type', 'like', 'regolith_%')
            ->where('is_explored', 1)
            ->orderBy('ring')
            ->first();

        if ($rg) {
            DB::table('colony_buildings')
                ->where('colony_id', $cid)->where('building_id', 27)
                ->update(['tile_x' => $rg->q, 'tile_y' => $rg->r]);
        }
    }

    /** Add 3rd advisor (pilot) and transition to Phase 2 with drawn objectives. */
    private function transitionToPhase2(Colony $colony, Run $run): void
    {
        DB::table('advisors')->insert([
            'user_id' => $colony->user_id,
            'personell_id' => (int) config('advisors.pilot.id', 89),
            'colony_id' => $colony->id,
            'rank' => 1,
            'active_ticks' => 5,
            'unavailable_until_tick' => null,
        ]);

        $run->phase = 2;
        $run->phase2_start_tick = 12;
        $run->save();

        $this->runProgressService->drawObjectives($run);
    }

    /**
     * Set colony resources, user credits/supply, and run tick in one call.
     *
     * Supply cap formula (GameTick): CC flat 10 + housing_lv × 8 + knowledge bonuses.
     * With Housing Lv2 and no knowledge: 10 + 16 = 26.
     * With Housing Lv3 and knowledge worth ~24 pts: 10 + 24 + 24 = 58.
     */
    private function setResources(
        Colony $colony,
        Run $run,
        int $tick,
        int $regolith,
        int $organika,
        int $werkstoffe,
        int $trust,
        int $credits,
        int $supply,
    ): void {
        $run->current_tick = $tick;
        $run->started_at = now(); // non-null = run started; null keeps it in lobby pending state
        $run->save();

        DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 3)
            ->update(['amount' => $regolith]);

        DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 4)
            ->update(['amount' => $werkstoffe]);

        DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 5)
            ->update(['amount' => $organika]);

        DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 12)
            ->update(['amount' => $trust]);

        DB::table('user_resources')
            ->where('user_id', $colony->user_id)
            ->update(['credits' => $credits, 'supply' => $supply]);
    }

    /**
     * Seed colony_researches rows for knowledge items the player has unlocked.
     *
     * $entries = [[research_id, level, ap_spend], ...]
     * All knowledge researches use ap_for_levelup = 3 (DB value).
     */
    private function seedKenntnisse(int $colonyId, array $entries): void
    {
        foreach ($entries as [$researchId, $level, $apSpend]) {
            DB::table('colony_researches')->insert([
                'colony_id' => $colonyId,
                'research_id' => $researchId,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => $apSpend,
            ]);
        }
    }

    // ── Scenarios ─────────────────────────────────────────────────────────────

    /**
     * Tick 12 — Phase 1 one hire short of completion.
     *
     * The player has engineer + scientist. To hire the 3rd advisor (pilot) they must
     * first place Hangar (90 Rg build cost) — the path gate requires the building to
     * be placed before the advisor slot opens. Rg=150 lets them place Hangar immediately
     * (150 − 90 = 60 Rg remaining), then hire pilot for 500 credits.
     * Cantina is also an option but requires 25 Wk (Wk=0 here) → Hangar is the path.
     * 8 Sols of Agrardom Lv2 production (net +17/Sol) → 136 Organika; rounded to 120
     * accounting for early food shortfalls before Agrardom reached Lv2.
     * Credits: 3000 start − 200 (engineer) − 400 (scientist) = 2400.
     * Supply cap: CC flat 10 + Housing Lv2 × 8 = 26.
     * Kenntnisse: Scientist hired at tick ~5; ~70 research AP earned → construction Lv2
     * (ap_spend=1 toward Lv3), agronomy started (ap_spend=2).
     */
    private function scenarioPrePhase2(Colony $colony, Run $run): void
    {
        $this->placePhase1Buildings($colony);

        // Cap: CC_flat(10) + Housing_Lv2(16) + construction_Lv2(8) = 34
        $this->setResources($colony, $run,
            tick: 12,
            regolith: 150,
            organika: 120,
            werkstoffe: 0,
            trust: 25,
            credits: 2400,
            supply: 34,
        );

        // construction(90) Lv2/ap_spend=1, agronomy(93) started (ap_spend=2)
        $this->seedKenntnisse($colony->id, [
            [90, 2, 1],
            [93, 0, 2],
        ]);
    }

    /**
     * Tick 15 — 3 Sols into Phase 2.
     *
     * Phase 2 entered at tick 12. 3 additional Sols: +30 Rg, +51 net Organika.
     * Credits reduced by pilot hire fee (500). Supply unchanged (no new buildings).
     * Kenntnisse: 3 more ticks → construction reaches Lv3 (9 AP spent), agronomy still started.
     * Objectives drawn but all at 0 (too early for any progress).
     * Hangar placed at tick 12 (path gate for pilot slot).
     */
    private function scenarioPhase2(Colony $colony, Run $run): void
    {
        $this->placePhase1Buildings($colony);
        $this->placeHangar($colony, level: 1, placedAtTick: 12);
        $this->transitionToPhase2($colony, $run);

        // Cap: CC_flat(10) + Housing_Lv2(16) + construction_Lv3(13) = 39
        $this->setResources($colony, $run,
            tick: 15,
            regolith: 60,
            organika: 170,
            werkstoffe: 0,
            trust: 25,
            credits: 1900,
            supply: 39,
        );

        // construction(90) Lv3, agronomy(93) still started
        $this->seedKenntnisse($colony->id, [
            [90, 3, 0],
            [93, 0, 2],
        ]);
    }

    /**
     * Tick 30 — Trust crisis, 5 points above fail threshold.
     *
     * 15 more Sols than phase2. Trust dropped to -15 through recurring food shortfalls
     * (hunger_streak penalties). Organika depleted to 50 (the shortage that caused it).
     * Regolith accumulated (no new buildings to spend on): +150.
     * Credits: emergency merchant purchases + low AP efficiency → 800.
     * Supply: unchanged (no new buildings).
     * Kenntnisse: research slowed by trust crisis (AP multiplier 1.0 in this band,
     * but player distracted recovering food): construction Lv3, cartography Lv1.
     * Hangar placed at tick 12 (path gate for pilot slot).
     */
    private function scenarioNearFailTrust(Colony $colony, Run $run): void
    {
        $this->placePhase1Buildings($colony);
        $this->placeHangar($colony, level: 1, placedAtTick: 12);
        $this->transitionToPhase2($colony, $run);

        // Cap: CC_flat(10) + Housing_Lv2(16) + construction_Lv3(13) + cartography_Lv1(3) = 42
        $this->setResources($colony, $run,
            tick: 30,
            regolith: 210,
            organika: 50,
            werkstoffe: 0,
            trust: -15,
            credits: 800,
            supply: 42,
        );

        // construction(90) Lv3, cartography(91) Lv1 — research slowed by crisis
        $this->seedKenntnisse($colony->id, [
            [90, 3, 0],
            [91, 1, 0],
        ]);
    }

    /**
     * Tick 95 — 5 Sols before tick_limit, 1 objective done.
     *
     * Late-game state: buildings upgraded over 80+ Sols, strong economy.
     * Building upgrades vs. tick 15 state:
     *   Agrardom Lv4 (40 Organika/Sol), Sciencelab Lv3, Housing Lv3,
     *   Hangar Lv2, Cantina Lv1, SecurityHub Lv1.
     * 4 advisors: engineer, scientist, pilot, trader (rank 1).
     * Ring 1+2 tiles explored; Harvester relocated to Ring-2 regolith.
     * Supply cap: CC 10 + Housing Lv3 × 8 + knowledge ~24 pts ≈ 58.
     * Werkstoffe: bought from merchant (30 units accumulated).
     * Credits: advisor productivity + merchant sales over 80+ Sols → 7000.
     * Kenntnisse: 4 researches unlocked, including cartography at Lv4 and geology
     * (req. Sciencelab Lv2 — met). 1 of 3 objectives completed at tick 20.
     */
    private function scenarioNearDeadline(Colony $colony, Run $run): void
    {
        $this->placePhase1Buildings($colony);

        // Upgrade buildings beyond Phase-1 baseline
        $cid = $colony->id;
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 28)
            ->update(['level' => 3, 'status_points' => 20]);     // Housing Lv3
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 41)
            ->update(['level' => 4, 'status_points' => 20]);     // Agrardom Lv4
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 31)
            ->update(['level' => 3, 'status_points' => 20]);     // Sciencelab Lv3

        $this->placeHangar($colony, level: 2, placedAtTick: 20);
        $this->placeCantina($colony, level: 1, placedAtTick: 35);
        $this->placeSecurityHub($colony, level: 1, placedAtTick: 45);

        $this->transitionToPhase2($colony, $run);

        // 4th advisor: trader (Konsul) — hired after Cantina built
        DB::table('advisors')->insert([
            'user_id' => $colony->user_id, 'personell_id' => 92,
            'colony_id' => $cid, 'rank' => 1,
            'active_ticks' => 50, 'unavailable_until_tick' => null,
        ]);

        // Cap: CC_flat(10) + Housing_Lv3(24) + knowledge(constr5=20+carto4=17+geo1=3+agro3=13+hlth2=8) = 95
        $this->setResources($colony, $run,
            tick: (int) config('game.run.tick_limit', 100) - 5,
            regolith: 500,
            organika: 600,
            werkstoffe: 30,
            trust: 60,
            credits: 7000,
            supply: 95,
        );

        $this->exploreTilesAndMoveHarvester($colony, includeRing3: true);

        // construction(90) Lv5, cartography(91) Lv4, geology(92) Lv1 (req Sciencelab Lv2 — met),
        // agronomy(93) Lv3, health(94) Lv2
        $this->seedKenntnisse($colony->id, [
            [90, 5, 0],
            [91, 4, 1],
            [92, 1, 0],
            [93, 3, 2],
            [94, 2, 0],
        ]);

        // 1 objective done (tick 20), 2nd with partial progress
        $objectives = $run->objectives()->get();

        if ($objectives->count() >= 1) {
            $first = $objectives->first();
            $first->current_value = $first->target_value;
            $first->completed_at = 20;
            $first->save();
        }

        if ($objectives->count() >= 2) {
            $second = $objectives->get(1);
            $half = (int) ceil($second->target_value / 2);
            $second->current_value = $half;
            $second->streak_value = $half;
            $second->save();
        }
    }

    /**
     * Tick 60 — All 3 objectives completed, strong mid-late game economy.
     *
     * Buildings: Agrardom Lv3, Sciencelab Lv2, Housing Lv3,
     *   Hangar Lv2, Cantina Lv2, SecurityHub Lv1. 45 Sols past Phase-2 entry.
     * All 5 advisors present; engineer + scientist upgraded to Senior (rank 2)
     *   satisfying the "Expertenstab" objective (2 Seniors required).
     * All colony-zone + Ring-2 tiles explored; Harvester on Ring-2 regolith.
     * Supply cap: CC 10 + Housing Lv3 × 8 + knowledge ~16 pts ≈ 50.
     * Werkstoffe: some merchant purchases (20 units).
     * Credits: 10000 (economy objectives completed → Nexus credit bonus).
     * Objectives: all completed_at tick 40 (player finished with 60 Sols to spare).
     */
    private function scenarioObjectivesDone(Colony $colony, Run $run): void
    {
        $this->placePhase1Buildings($colony);

        // Upgrade buildings beyond Phase-1 baseline
        $cid = $colony->id;
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 28)
            ->update(['level' => 3, 'status_points' => 20]);     // Housing Lv3
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 41)
            ->update(['level' => 3, 'status_points' => 20]);     // Agrardom Lv3
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 31)
            ->update(['level' => 2, 'status_points' => 20]);     // Sciencelab Lv2

        $this->placeHangar($colony, level: 2, placedAtTick: 18);
        $this->placeCantina($colony, level: 2, placedAtTick: 22);
        $this->placeSecurityHub($colony, level: 1, placedAtTick: 30);

        $this->transitionToPhase2($colony, $run);

        // Upgrade engineer + scientist to Senior (rank 2) — needed for "Expertenstab" objective
        DB::table('advisors')
            ->where('colony_id', $cid)
            ->whereIn('personell_id', [
                (int) config('advisors.engineer.id', 35),
                (int) config('advisors.scientist.id', 36),
            ])
            ->update(['rank' => 2]);

        // Slot 4: trader (Konsul, rank 2), Slot 5: strategist (rank 1)
        DB::table('advisors')->insert([
            'user_id' => $colony->user_id, 'personell_id' => 92,
            'colony_id' => $cid, 'rank' => 2,
            'active_ticks' => 40, 'unavailable_until_tick' => null,
        ]);
        DB::table('advisors')->insert([
            'user_id' => $colony->user_id, 'personell_id' => 93,
            'colony_id' => $cid, 'rank' => 1,
            'active_ticks' => 25, 'unavailable_until_tick' => null,
        ]);

        // Cap: CC_flat(10) + Housing_Lv3(24) + knowledge(constr4=17+carto3=13+geo1=3+agro2=8+hlth1=3) = 78
        $this->setResources($colony, $run,
            tick: 60,
            regolith: 300,
            organika: 350,
            werkstoffe: 20,
            trust: 60,
            credits: 10000,
            supply: 78,
        );

        $this->exploreTilesAndMoveHarvester($colony, includeRing3: true);

        // construction(90) Lv4, cartography(91) Lv3, geology(92) Lv1 (req Sciencelab Lv2 — met),
        // agronomy(93) Lv2, health(94) Lv1
        $this->seedKenntnisse($colony->id, [
            [90, 4, 2],
            [91, 3, 0],
            [92, 1, 0],
            [93, 2, 1],
            [94, 1, 0],
        ]);

        // All 3 objectives completed at tick 40
        $run->objectives()->update([
            'current_value' => DB::raw('target_value'),
            'streak_value' => DB::raw('target_value'),
            'completed_at' => 40,
        ]);
    }
}
