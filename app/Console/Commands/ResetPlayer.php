<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ResetPlayer — wipes all game state for a user and re-runs onboarding.
 *
 * Usage:
 *   php artisan game:reset-player bart
 *   php artisan game:reset-player 3          (by user_id)
 *   php artisan game:reset-player bart --yes  (skip confirmation)
 */
class ResetPlayer extends Command
{
    protected $signature = 'game:reset-player {user : Username or user_id} {--yes : Skip confirmation}';

    protected $description = 'Reset a player\'s game state to Sol 1 (dev tool)';

    public function __construct(private readonly OnboardingService $onboardingService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
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
            $this->warn("This will delete ALL game data for user '{$user->username}' (id={$user->user_id}).");
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

        $this->info("Player '{$user->username}' reset to Sol 1.");

        return self::SUCCESS;
    }
}
