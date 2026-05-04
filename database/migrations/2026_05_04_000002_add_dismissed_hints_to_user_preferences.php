<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds dismissed_hints to user_preferences.
 *
 * Stores a JSON-encoded list of hint keys the player has dismissed,
 * e.g. '["hint_1","hint_3"]'. NULL means nothing has been dismissed yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->text('dismissed_hints')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('dismissed_hints');
        });
    }
};
