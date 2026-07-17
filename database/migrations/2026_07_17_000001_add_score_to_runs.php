<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds runs.score — the final score, frozen when the run ends.
 *
 * RunProgressService::calculateScore() was computed on run end and then thrown away;
 * the lobby recomputed the same formula inline against the player's *current* credits.
 * A finished run's score therefore drifted every time the player earned or spent money.
 * Persisting it at endRun() makes the number history instead of a live query.
 *
 * Backfill: existing finished runs get a best-effort score from the same formula, with
 * the credits term omitted — the credits they ended with are not recoverable. Failed runs
 * score 0, as they always did. Active runs stay NULL until they end.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('runs', function (Blueprint $table): void {
            $table->integer('score')->nullable()->after('rng_seed');
        });

        $tickLimitDefault = (int) config('game.run.tick_limit', 100);

        $finished = DB::table('runs')
            ->whereIn('status', ['completed', 'failed'])
            ->get(['id', 'status', 'colony_id', 'current_tick', 'settings']);

        foreach ($finished as $run) {
            if ($run->status === 'failed') {
                DB::table('runs')->where('id', $run->id)->update(['score' => 0]);

                continue;
            }

            $completed = DB::table('run_objectives')
                ->where('run_id', $run->id)
                ->whereNotNull('completed_at')
                ->count();

            $trust = (int) (DB::table('colony_resources')
                ->where('colony_id', $run->colony_id)
                ->where('resource_id', 12)
                ->value('amount') ?? 0);

            $settings = json_decode((string) $run->settings, true);
            $tickLimit = (int) ($settings['tick_limit'] ?? $tickLimitDefault);

            $score = max(0, ($completed * 1000)
                + (($tickLimit - (int) $run->current_tick) * 10)
                + ($trust * 5));

            DB::table('runs')->where('id', $run->id)->update(['score' => $score]);
        }
    }

    public function down(): void
    {
        Schema::table('runs', function (Blueprint $table): void {
            $table->dropColumn('score');
        });
    }
};
