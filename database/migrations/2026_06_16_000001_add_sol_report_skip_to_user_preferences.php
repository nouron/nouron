<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds sol_report_skip to user_preferences.
 *
 * When true, the Sol-Report transition screen auto-dismisses after its
 * animation instead of waiting for the player to confirm. Major beats
 * (level-down, phase change, objective milestone, run end) override this
 * and force the report to stay visible — see SolReportService::buildReport().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->boolean('sol_report_skip')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('user_preferences', function (Blueprint $table) {
            $table->dropColumn('sol_report_skip');
        });
    }
};
