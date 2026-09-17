<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the bar_concerns table (Charakter-Anliegen, GDD §12 Kanal 1, A41).
 *
 * A second, independent Cantina special-event slot — separate from
 * bar_encounters (A35) — rolled only when the encounter roll did NOT fire in
 * the same tick (at most one Cantina special event per tick). A row is
 * created when a concern spawns and shown to the player; resolveConcern()
 * later resolves it (AP spend + success roll + reward/penalty).
 *
 * created_tick doubles as the source for the per-character cooldown check
 * (BarService::generateConcernForColony() looks at the most recent
 * created_tick for a given colony_id + character_slug) — no separate
 * cooldown-state table is needed.
 *
 * Columns:
 *   colony_id        — the colony whose bar holds this concern
 *   character_slug    — config/characters.php key of the figure with the concern
 *   created_tick      — tick the concern was rolled (cooldown lookups)
 *   expires_tick      — tick at which an unresolved concern disappears
 *   is_resolved       — whether resolveConcern() has run for this row
 *   success           — outcome of the success roll, null until resolved
 *   outcome           — JSON blob with resolved reward/penalty details (logging)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bar_concerns', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->string('character_slug', 32);
            $table->unsignedInteger('created_tick');
            $table->unsignedInteger('expires_tick');
            $table->boolean('is_resolved')->default(false);
            $table->boolean('success')->nullable();
            $table->json('outcome')->nullable();
            $table->timestamps();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');

            // Covers the per-character cooldown lookup (MAX(created_tick)
            // WHERE colony_id = ? AND character_slug = ?) without a table scan.
            $table->index(['colony_id', 'character_slug', 'created_tick']);

            // Covers the "expire old concerns" / "has open concern" queries
            // (WHERE colony_id = ? AND is_resolved = ? AND expires_tick ...).
            $table->index(['colony_id', 'is_resolved', 'expires_tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bar_concerns');
    }
};
