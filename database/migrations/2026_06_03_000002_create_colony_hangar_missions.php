<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the colony_hangar_missions table.
 *
 * Each row represents one dispatch mission launched from a specific hangar
 * bay. A hangar can accumulate mission history, so there is no UNIQUE
 * constraint on (colony_id, instance_id, ship_id).
 *
 * Columns:
 *   id             — surrogate PK
 *   colony_id      — owning colony (FK glx_colonies.id)
 *   instance_id    — hangar bay number (mirrors colony_buildings.instance_id
 *                    where building_id = 44)
 *   ship_id        — ship that was dispatched (FK ships.id)
 *   destination    — player-entered free-text name of the target location
 *   sol_distance   — player-estimated travel time in Sols (game ticks)
 *   dispatch_tick  — game tick when the mission was launched
 *   recall_tick    — game tick when recall was ordered (NULL = not recalled)
 *   state          — mission lifecycle: active | recalled | completed
 *   created_at     — wall-clock timestamp when the row was inserted
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_hangar_missions', function (Blueprint $table) {
            $table->id();
            $table->integer('colony_id');
            $table->integer('instance_id');
            $table->integer('ship_id');
            $table->text('destination');
            $table->integer('sol_distance');
            $table->integer('dispatch_tick');
            $table->integer('recall_tick')->nullable();
            $table->string('state', 20)->default('active');
            $table->dateTime('created_at')->useCurrent();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->foreign('ship_id')->references('id')->on('ships');

            $table->index(['colony_id', 'instance_id']);
            $table->index(['colony_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_hangar_missions');
    }
};
