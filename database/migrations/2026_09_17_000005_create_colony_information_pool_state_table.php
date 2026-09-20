<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the colony_information_pool_state table (Deva & Lenn Vier-Ausgänge-
 * Pool, GDD §12 "Deva & Lenn — taktische Information", A42).
 *
 * One row per colony, created lazily on first interaction. Tracks two kinds
 * of per-run state for the information pool:
 *   - the *_used flags: whether a given MECHANICAL outcome has already been
 *     drawn this run — each mechanical outcome (Deva's drill buffer, Deva's
 *     knowledge boost, Lenn's navigation discount, Lenn's knowledge boost) is
 *     usable at most once per run (BarService::rollInformationOutcome()); the
 *     narrative-only outcomes stay unlimited and need no flag here.
 *   - the active_* flags: a granted-but-not-yet-consumed buff, consumed by
 *     the next matching trigger elsewhere in the codebase (active_drill_buffer
 *     by GameTick::rollInstability()/rollPlague(), active_nav_voucher by
 *     ColonyTileService::exploreTile()).
 *
 * Columns:
 *   colony_id              — primary key, one row per colony
 *   deva_buff_used         — Deva's "Drill-Reaktionspuffer" outcome drawn this run
 *   deva_knowledge_used    — Deva's knowledge-boost outcome drawn this run
 *   lenn_nav_used          — Lenn's navigation-discount outcome drawn this run
 *   lenn_knowledge_used    — Lenn's knowledge-boost outcome drawn this run
 *   active_drill_buffer    — granted, unconsumed Drill-Reaktionspuffer
 *   active_nav_voucher     — granted, unconsumed 100% navigation-AP discount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_information_pool_state', function (Blueprint $table) {
            $table->unsignedInteger('colony_id')->primary();
            $table->boolean('deva_buff_used')->default(false);
            $table->boolean('deva_knowledge_used')->default(false);
            $table->boolean('lenn_nav_used')->default(false);
            $table->boolean('lenn_knowledge_used')->default(false);
            $table->boolean('active_drill_buffer')->default(false);
            $table->boolean('active_nav_voucher')->default(false);

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_information_pool_state');
    }
};
