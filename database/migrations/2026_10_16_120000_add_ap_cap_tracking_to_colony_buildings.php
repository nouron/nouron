<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-building per-Sol construction AP cap (game.build.ap_cap_per_sol): remembers in
 * which Sol the counter was last written and how much AP went into this building row
 * that Sol. A stale ap_cap_tick means "0 spent today".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->integer('ap_cap_tick')->nullable();
            $table->integer('ap_cap_spent')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('colony_buildings', function (Blueprint $table) {
            $table->dropColumn(['ap_cap_tick', 'ap_cap_spent']);
        });
    }
};
