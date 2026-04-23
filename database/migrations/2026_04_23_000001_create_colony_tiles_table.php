<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the colony_tiles table for the hex-grid colony surface map.
 *
 * Each tile represents one hex cell on the colony surface, identified by
 * axial coordinates (q, r). Ring 0 is the CC tile; rings 1–4 are unlocked
 * progressively as the Command Center levels up.
 *
 * tile_type describes the surface terrain / resource node.
 * event_type is an overlay revealed only after a deep scan (may be NULL).
 *
 * Coordinate system: axial (pointy-top), as decided in DS-4.
 * Design context: GDD §5 (Colony Surface), DS-4 (Tile Catalogue).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_tiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('colony_id');
            $table->integer('q');
            $table->integer('r');
            $table->integer('ring');
            $table->string('tile_type');
            $table->string('event_type')->nullable();
            $table->boolean('is_ring_unlocked')->default(false);
            $table->boolean('is_explored')->default(false);
            $table->boolean('is_deep_scanned')->default(false);
            $table->integer('resource_amount')->nullable();
            $table->integer('resource_max')->nullable();
            $table->timestamps();

            $table->unique(['colony_id', 'q', 'r'], 'colony_tiles_colony_q_r_unique');
            $table->foreign('colony_id')->references('id')->on('glx_colonies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_tiles');
    }
};
