<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delayed Uplink-Direktimport for non-Werkstoff resources (Regolith/Organika,
 * Owner-Entscheidung F5/A28, 2026-09-08/11). Payment happens immediately at
 * request time (ColonyController::nexusImportResource()); this table only
 * tracks the pending delivery until GameTick credits it on deliver_at_tick.
 * Werkstoffe keep the existing instant nexusImportCompounds() path — no row
 * here for that resource.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nexus_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->unsignedInteger('resource_id');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('deliver_at_tick');

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->index(['deliver_at_tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_imports');
    }
};
