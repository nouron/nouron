<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the difficulty column to colony_hangar_missions (GDD §8b Erfolgschance +
 * Schwierigkeitsgrad, docs/superpowers/specs/2026-09-02-hangar-mission-success-chance-design.md).
 *
 * Values: 'easy' | 'normal' | 'hard'. default('normal') so pre-existing
 * dispatch code paths and test fixtures that don't set it explicitly keep
 * working unchanged (reward_multiplier['normal'] = 1.0, i.e. today's behavior).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->string('difficulty', 20)->default('normal')->after('sol_distance');
        });
    }

    public function down(): void
    {
        Schema::table('colony_hangar_missions', function (Blueprint $table) {
            $table->dropColumn('difficulty');
        });
    }
};
