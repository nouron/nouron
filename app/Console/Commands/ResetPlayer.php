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
    protected $signature   = 'game:reset-player {user : Username or user_id} {--yes : Skip confirmation}';
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
            : User::where('username', $input)->first();

        if (!$user) {
            $this->error("User not found: {$input}");
            return self::FAILURE;
        }

        if (!$this->option('yes')) {
            $this->warn("This will delete ALL game data for user '{$user->username}' (id={$user->user_id}).");
            if (!$this->confirm('Continue?', false)) {
                $this->line('Aborted.');
                return self::SUCCESS;
            }
        }

        DB::transaction(function () use ($user) {
            $colonyIds = DB::table('glx_colonies')
                ->where('user_id', $user->user_id)
                ->pluck('id');

            foreach ($colonyIds as $cid) {
                DB::table('colony_resources')->where('colony_id', $cid)->delete();
                DB::table('colony_buildings')->where('colony_id', $cid)->delete();
                DB::table('colony_tiles')->where('colony_id', $cid)->delete();
                // colony_log has no colony_id column — deleted below by user_id.
                DB::table('advisors')->where('colony_id', $cid)->delete();
                DB::table('locked_actionpoints')->where('colony_id', $cid)->delete();
                DB::table('colony_ships')->where('colony_id', $cid)->delete();
                DB::table('colony_researches')->where('colony_id', $cid)->delete();
                DB::table('colony_personell')->where('colony_id', $cid)->delete();
                DB::table('run_objectives')->where('colony_id', $cid)->delete();
                DB::table('trade_resources')->where('colony_id', $cid)->delete();
                DB::table('trust_events')->where('colony_id', $cid)->delete();
                DB::table('merchant_visits')->where('colony_id', $cid)->delete();
                DB::table('colony_hangar_missions')->where('colony_id', $cid)->delete();
            }

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
