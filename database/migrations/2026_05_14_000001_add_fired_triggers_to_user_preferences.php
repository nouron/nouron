<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fired_triggers to user_preferences.
 *
 * Stores a JSON-encoded list of one-time onboarding trigger keys that have
 * already fired for a user, e.g. '["onboarding_decay","supply_cap_full"]'.
 * NULL means no trigger has fired yet.
 *
 * Known trigger keys:
 *   'onboarding_decay'      — building SP dropped below 80% for the first time
 *   'supply_cap_full'       — supply cap is full (UI flag only, no INNN event)
 *   'onboarding_trust'      — trust went negative for the first time
 *   'ap_limit_shown'        — AP limit reached (client-side only)
 *   'harvester_move_shown'  — harvester relocation hint (client-side only)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->text('fired_triggers')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('fired_triggers');
        });
    }
};
