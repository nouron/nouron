<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the bar_information_encounters table (Deva & Lenn Vier-Ausgänge-Pool,
 * GDD §12 "Deva & Lenn — taktische Information", A42).
 *
 * A third, independent Cantina special-event slot/channel — deliberately NOT
 * gated by the shared "at most one Cantina special event per tick" rule that
 * governs bar_encounters (A35) and bar_concerns (A41); this pool rolls on its
 * own, every tick, in parallel.
 *
 * The outcome is picked at spawn time (BarService::generateInformationEncounterForColony())
 * — character_slug and outcome_key are already fixed when the row is created.
 * resolveInformationEncounter() only executes the already-chosen outcome's
 * effect (and, for Deva's knowledge-boost outcome, needs the player's chosen
 * knowledge target — passed as a parameter at resolve time, not stored here).
 *
 * Columns:
 *   colony_id      — the colony whose Cantina holds this encounter
 *   character_slug — 'veteran' (Deva) or 'ai_researcher' (Lenn)
 *   outcome_key    — which of the 4 possible outcomes was drawn
 *   created_tick   — tick the encounter was rolled
 *   expires_tick   — tick at which an unresolved encounter disappears
 *   is_resolved    — whether resolveInformationEncounter() has run for this row
 *   outcome        — JSON blob with resolved effect details (logging)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_information_encounters', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->string('character_slug', 32);
            $table->string('outcome_key', 32);
            $table->unsignedInteger('created_tick');
            $table->unsignedInteger('expires_tick');
            $table->boolean('is_resolved')->default(false);
            $table->json('outcome')->nullable();
            $table->timestamps();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');

            // Covers the "expire old encounters" / "has open encounter" queries.
            $table->index(['colony_id', 'is_resolved', 'expires_tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_information_encounters');
    }
};
