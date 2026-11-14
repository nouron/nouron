<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * T59: the trust sources behind the stored trust value (written at each Sol change by
 * TrustService::calculateAndStore()) move from the cache into the colony row, so they
 * survive a cache flush and do not collide between databases. Null = unknown.
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
            $table->json('trust_breakdown')->nullable();
        });

        $this->createView();
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_glx_colonies');

        Schema::table('glx_colonies', function (Blueprint $table) {
            $table->dropColumn('trust_breakdown');
        });

        $this->createView();
    }

    private function createView(): void
    {
        DB::statement('CREATE SQL SECURITY INVOKER VIEW v_glx_colonies AS SELECT * FROM glx_colonies');
    }
};
