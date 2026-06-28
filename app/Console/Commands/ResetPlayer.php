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

/**
 * ResetPlayer — wipes all game state for a user and re-runs onboarding.
 *
 * Usage:
 *   php artisan game:reset-player bart
 *   php artisan game:reset-player 3                            (by user_id)
 *   php artisan game:reset-player bart --yes                   (skip confirmation)
 *   php artisan game:reset-player bart --yes --scenario=phase2 (jump to Phase 2)
 *
 * Scenarios:
 *   fresh           Sol 1 clean start (default)
 *   pre-phase2      CC Lv3 + 2 buildings Lv2 + 2 advisors — one hire away from Phase 2
 *   phase2          Phase 2 active (tick 15), 3 objectives drawn
 *   near-fail-trust Phase 2, trust = −15 (threshold −20)
 *   near-deadline   Phase 2, tick = 95 (5 sols left), 1 objective done
 *   objectives-done Phase 2, all 3 objectives completed
 */
class ResetPlayer extends Command
{
    protected $signature = 'game:reset-player
        {user : Username or user_id}
        {--yes : Skip confirmation}
        {--scenario=fresh : fresh|pre-phase2|phase2|near-fail-trust|near-deadline|objectives-done}';

    protected $description = 'Reset a player\'s game state (dev tool). Use --scenario to jump to a mid-game state.';

    private const VALID_SCENARIOS = [
        'fresh', 'pre-phase2', 'phase2', 'near-fail-trust', 'near-deadline', 'objectives-done',
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
        $scenario = $this->option('scenario');

        if (! in_array($scenario, self::VALID_SCENARIOS, true)) {
            $this->error("Unknown scenario: {$scenario}");
            $this->line('Valid scenarios: '.implode(', ', self::VALID_SCENARIOS));

            return self::FAILURE;
        }

        $input = $this->argument('user');

        $user = is_numeric($input)
            ? User::find((int) $input)
            : User::whereRaw('LOWER(username) = LOWER(?)', [$input])->first();

        if (! $user) {
            // Dev DB wiped (migrate:fresh without --seed) — auto-seed and retry once.
            if (DB::table('user')->count() === 0) {
                $this->warn('Dev DB appears empty — running db:seed automatically...');
                $this->call('db:seed');
                $user = is_numeric($input)
                    ? User::find((int) $input)
                    : User::whereRaw('LOWER(username) = LOWER(?)', [$input])->first();
            }
            if (! $user) {
                $this->error("User not found: {$input}");

                return self::FAILURE;
            }
        }

        if (! $this->option('yes')) {
            $suffix = $scenario !== 'fresh' ? " then apply scenario '{$scenario}'" : '';
            $this->warn("This will delete ALL game data for user '{$user->username}' (id={$user->user_id}){$suffix}.");
            if (! $this->confirm('Continue?', false)) {
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

    // ── Shared state builders ─────────────────────────────────────────────────

    /**
     * Phase 1 near-complete: CC Lv3, Housing Lv2, Agrardom Lv2, Sciencelab Lv1.
     * 2 advisors active (engineer + scientist). Missing 1 advisor for Phase 2 trigger.
     */
    private function buildPhase1State(Colony $colony, Run $run): void
    {
        $cid = $colony->id;

        // CC → Lv3, placed at center
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 25)
            ->update(['level' => 3, 'status_points' => 20, 'tile_x' => 0, 'tile_y' => 0, 'placed_at_tick' => 1]);

        // Harvester already at (1,0) from seed — stays Lv1
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 27)
            ->update(['status_points' => 20]);

        // Housing → Lv2 (already seeded at (0,1))
        DB::table('colony_buildings')
            ->where('colony_id', $cid)->where('building_id', 28)
            ->update(['level' => 2, 'status_points' => 20]);

        // Agrardom (41) — place at (0,-1) ring 1, Lv2
        DB::table('colony_buildings')->insert([
            'colony_id' => $cid, 'building_id' => 41, 'instance_id' => 1,
            'level' => 2, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => 0, 'tile_y' => -1, 'placed_at_tick' => 2,
        ]);

        // Sciencelab (31) — place at (-1,0) ring 1, Lv1
        DB::table('colony_buildings')->insert([
            'colony_id' => $cid, 'building_id' => 31, 'instance_id' => 1,
            'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'tile_x' => -1, 'tile_y' => 0, 'placed_at_tick' => 3,
        ]);

        // Expand colony zone to CC Lv3
        $this->tileService->assignColonyZone($cid, 3);

        // 2 advisors: engineer + scientist
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

        // Resources: generous but realistic
        DB::table('user_resources')
            ->where('user_id', $colony->user_id)
            ->update(['credits' => 5000, 'supply' => 30]);

        DB::table('colony_resources')
            ->where('colony_id', $cid)->where('resource_id', 3)
            ->update(['amount' => 500]);  // regolith

        DB::table('colony_resources')
            ->where('colony_id', $cid)->where('resource_id', 12)
            ->update(['amount' => 25]);  // trust

        $run->current_tick = 12;
        $run->save();
    }

    /**
     * Phase 2 base: Phase 1 complete + 3rd advisor + phase transition + objectives drawn.
     */
    private function buildPhase2State(Colony $colony, Run $run): void
    {
        $this->buildPhase1State($colony, $run);

        // 3rd advisor: pilot
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
        $run->current_tick = 15;
        $run->save();

        $this->runProgressService->drawObjectives($run);
    }

    // ── Scenarios ─────────────────────────────────────────────────────────────

    private function scenarioPrePhase2(Colony $colony, Run $run): void
    {
        $this->buildPhase1State($colony, $run);
    }

    private function scenarioPhase2(Colony $colony, Run $run): void
    {
        $this->buildPhase2State($colony, $run);
    }

    private function scenarioNearFailTrust(Colony $colony, Run $run): void
    {
        $this->buildPhase2State($colony, $run);

        // trust = -15 (threshold is -20 → 5 ticks to recover or fail)
        DB::table('colony_resources')
            ->where('colony_id', $colony->id)->where('resource_id', 12)
            ->update(['amount' => -15]);

        $run->current_tick = 30;
        $run->save();
    }

    private function scenarioNearDeadline(Colony $colony, Run $run): void
    {
        $this->buildPhase2State($colony, $run);

        $tickLimit = (int) config('game.run.tick_limit', 100);
        $run->current_tick = $tickLimit - 5;
        $run->save();

        // Mark 1 objective done, leave 2 open with partial progress
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

    private function scenarioObjectivesDone(Colony $colony, Run $run): void
    {
        $this->buildPhase2State($colony, $run);

        $run->current_tick = 60;
        $run->save();

        // All 3 objectives completed
        $run->objectives()->update([
            'current_value' => DB::raw('target_value'),
            'streak_value' => DB::raw('target_value'),
            'completed_at' => 40,
        ]);
    }
}
