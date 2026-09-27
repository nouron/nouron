<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A45 "Sieg-Timing / Ziele" (GDD §15, §18.4):
 *
 *  - run_objectives.best_streak_value: longest streak so far for streak objectives.
 *    The Nexus checkpoints measure progress with the best streak, not the running
 *    one, so a single bad Sol before a checkpoint doesn't erase the evidence.
 *  - colony_hangar_missions.succeeded: outcome of the success roll at resolution
 *    (null = not resolved / aborted / recalled, 1 = success, 0 = failed roll).
 *    task_expedition_coverage counts successful missions at difficulty >= normal.
 *
 * No backfill: existing rows keep 0 / null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('run_objectives', function (Blueprint $table) {
            $table->integer('best_streak_value')->default(0)->after('streak_value');
        });

        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->boolean('succeeded')->nullable()->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('run_objectives', function (Blueprint $table) {
            $table->dropColumn('best_streak_value');
        });

        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->dropColumn('succeeded');
        });
    }
};
