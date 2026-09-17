<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the colony_building_discount_vouchers table (A41 Cantina-Anliegen
 * Vesper/information_broker + Aldra/founder — Baukosten-Rabatt-Gutschein).
 *
 * A voucher additively discounts the AP threshold of the next building
 * level-up the colony completes (ProjectBonusService::effectiveApForLevelup()
 * folds any active, unconsumed, unexpired voucher into the discount percent —
 * covers every read site, not just the invest action). It is consumed
 * (consumed_tick set) the moment a building level-up actually completes
 * while the voucher is active.
 *
 * Columns:
 *   colony_id       — the colony this voucher belongs to
 *   discount_pct     — additive AP-cost discount percent
 *   source           — which concern granted it ('information_broker' | 'founder')
 *   granted_tick     — tick the voucher was granted
 *   expires_tick     — nullable; null = no time limit (information_broker),
 *                      set = expires after N Sol unused (founder)
 *   consumed_tick    — nullable; set once a level-up consumes the voucher
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colony_building_discount_vouchers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->unsignedInteger('discount_pct');
            $table->string('source', 32);
            $table->unsignedInteger('granted_tick');
            $table->unsignedInteger('expires_tick')->nullable();
            $table->unsignedInteger('consumed_tick')->nullable();
            $table->timestamps();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
            $table->index(['colony_id', 'consumed_tick']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colony_building_discount_vouchers');
    }
};
