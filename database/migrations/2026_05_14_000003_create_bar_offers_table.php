<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the bar_offers table.
 *
 * NPC traders leave 0–2 offers per tick at the Bar/Cantina building.
 * Each offer is valid for 1–2 ticks. The player can accept or decline.
 *
 * Columns:
 *   colony_id        — the colony whose bar holds this offer
 *   give_resource_id — resource the player pays (1=credits, 3=regolith, 4=compounds, 5=organics)
 *   give_amount      — amount the player pays
 *   get_resource_id  — resource the player receives
 *   get_amount       — amount the player receives
 *   expires_tick     — tick at which the offer expires (exclusive)
 *   is_accepted      — whether the player accepted the offer
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->unsignedInteger('give_resource_id');
            $table->unsignedInteger('give_amount');
            $table->unsignedInteger('get_resource_id');
            $table->unsignedInteger('get_amount');
            $table->unsignedInteger('expires_tick');
            $table->boolean('is_accepted')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_offers');
    }
};
