<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the character_codex_entries table (Charakter-Kodex, GDD §12, A42).
 *
 * Pure lore/meta progression — no gameplay effect. Persists across runs
 * (account/user-bound, not colony-/run-bound), unlike everything else in the
 * Cantina system. CharacterCodexService::recordProgress() unlocks the next
 * un-unlocked entry_number for a given user + character_slug, up to
 * config('game.character_codex.entries_per_character').
 *
 * Columns:
 *   user_id           — owning user (survives across runs)
 *   character_slug    — config/characters.php key of the figure
 *   entry_number      — 1-based sequential unlock order (1..entries_per_character)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_codex_entries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('character_slug', 32);
            $table->unsignedTinyInteger('entry_number');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
            $table->unique(['user_id', 'character_slug', 'entry_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_codex_entries');
    }
};
