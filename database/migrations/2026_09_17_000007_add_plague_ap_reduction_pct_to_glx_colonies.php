<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            // Per-episode AP-reduction override for the active plague debuff
            // (GDD §9). Normally null, meaning AdvisorService falls back to the
            // global config('game.encounter.plague.ap_reduction_pct') default.
            // Set at trigger time by GameTick::rollPlague() — halved when Deva's
            // "Drill-Reaktionspuffer" (A42) was active and got consumed by this
            // episode, so the reduction applies for exactly this plague episode,
            // not retroactively or to the next one.
            $table->float('plague_ap_reduction_pct')->nullable()->after('plague_until_tick');
        });
    }

    public function down(): void
    {
        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('plague_ap_reduction_pct');
        });
    }
};
