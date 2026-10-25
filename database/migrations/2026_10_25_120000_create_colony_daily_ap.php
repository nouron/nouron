<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-Sol AP counters (T43-G1), one row per colony and cap key ("knowledge:94",
 * later "training:<family>", "conversation:<character>"). The counter belongs to the
 * Sol in `tick`; a stale tick means nothing was spent today. The building cap keeps
 * its own columns on colony_buildings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_daily_ap', function (Blueprint $table) {
            $table->integer('colony_id');
            $table->string('cap_key', 60);
            $table->integer('tick')->default(0);
            $table->integer('spent')->default(0);
            $table->primary(['colony_id', 'cap_key']);
            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_daily_ap');
    }
};
