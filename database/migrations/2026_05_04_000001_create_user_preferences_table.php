<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the user_preferences table for per-user persistent settings.
 *
 * One row per user. Settings here survive across game runs.
 * onboarding_hints controls whether onboarding hint overlays are shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unique();
            $table->boolean('onboarding_hints')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('user')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
