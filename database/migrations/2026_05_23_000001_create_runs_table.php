<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the runs table.
 *
 * A run represents one Roguelike session for a player. It ties a user to a
 * colony, tracks the current Sol (tick) the run has progressed to, and records
 * whether the run is active, completed, or failed.
 *
 * runs:
 *   user_id       — FK → user.user_id (custom PK column name), CASCADE on delete
 *   colony_id     — FK → glx_colonies.id, CASCADE on delete
 *   current_tick  — Sol counter for this run (mirrors the game tick at last update)
 *   status        — lifecycle state: 'active' | 'completed' | 'failed'
 *   started_at    — when the run began (nullable, set on first tick)
 *   ended_at      — when the run ended (nullable, set on completion or failure)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('colony_id');
            $table->integer('current_tick')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('user')->onDelete('cascade');
            $table->foreign('colony_id')->references('id')->on('glx_colonies')->onDelete('cascade');

            // Fast lookup: find the active run for a given user
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('runs');
    }
};
