<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the bar_encounters table (Cantina-Begegnungspool, GDD §12 Kanal 1).
 *
 * A shared event slot with three possible outcomes — wager (Zara), auction
 * (Voss), short-term contract (generic). Unlike bar_offers, an accepted
 * encounter can resolve immediately (wager/auction) or run for several ticks
 * before resolving (contract).
 *
 * Columns:
 *   colony_id        — the colony whose bar holds this encounter
 *   type              — 'wager' | 'auction' | 'contract'
 *   give_resource_id  — resource the player stakes/sells (wager/auction only)
 *   give_amount       — amount staked/sold (wager/auction only)
 *   win_chance        — wager only, 0.0–1.0
 *   credits_amount    — wager win payout / auction payout / contract Cr per tick
 *   duration_ticks    — contract only, total length once accepted
 *   expires_tick      — tick at which an unaccepted encounter disappears
 *   is_accepted       — whether the player accepted the encounter
 *   resolved          — true once the outcome is final (immediate for wager/
 *                       auction on accept; for a contract, once its run ends)
 *   won               — wager only, true/false once resolved
 *   ends_tick         — contract only, tick at which payouts stop
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->string('type', 16);
            $table->unsignedInteger('give_resource_id')->nullable();
            $table->unsignedInteger('give_amount')->nullable();
            $table->float('win_chance')->nullable();
            $table->unsignedInteger('credits_amount')->nullable();
            $table->unsignedInteger('duration_ticks')->nullable();
            $table->unsignedInteger('expires_tick');
            $table->boolean('is_accepted')->default(false);
            $table->boolean('resolved')->default(false);
            $table->boolean('won')->nullable();
            $table->unsignedInteger('ends_tick')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_encounters');
    }
};
