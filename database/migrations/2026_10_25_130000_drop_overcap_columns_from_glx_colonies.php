<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T43-G9 (housing loss, option A2): colonists are derived from housing, nothing is
 * stored any more — no over-capacity streak, no departed colonists.
 *
 * v_glx_colonies freezes the column list of `SELECT *` at creation time, so the view is
 * dropped before and recreated after the column change (see docs/deployment-db.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');

        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn(['overcap_streak', 'overcap_departed']);
        });

        $this->createView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');

        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->integer('overcap_streak')->default(0);
            $table->integer('overcap_departed')->default(0);
        });

        $this->createView();
    }

    private function createView(): void
    {
        $security = DB::connection()->getDriverName() === 'sqlite' ? '' : 'SQL SECURITY INVOKER ';
        DB::statement("CREATE {$security}VIEW v_glx_colonies AS SELECT * FROM glx_colonies");
    }
};
