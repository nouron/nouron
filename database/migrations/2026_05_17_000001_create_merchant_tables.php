<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the merchant_visits and merchant_items tables.
 *
 * The Traveling Merchant is a system event that appears randomly, separate from
 * the Bar/Cantina. It offers a small selection of special items for Credits.
 *
 * merchant_visits:
 *   colony_id    — which colony this visit belongs to (one player = one colony)
 *   tick_start   — Sol when the merchant arrived
 *   tick_end     — Sol when the merchant leaves (= tick_start + duration - 1, inclusive)
 *   was_visited  — player opened the merchant modal at least once this visit
 *
 * merchant_items:
 *   visit_id     — FK to merchant_visits
 *   item_type    — category key (ap_flex, ap_targeted, information, repair_kit, trust_boost, credit_loan)
 *   label        — display name shown in the UI
 *   cost_credits — Credits price
 *   payload      — JSON blob with item-specific data (e.g. ap amount, building_id, sp_amount)
 *   sold         — whether the player already purchased this item
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('colony_id');
            $table->unsignedInteger('tick_start');
            $table->unsignedInteger('tick_end');
            $table->boolean('was_visited')->default(false);
            $table->timestamps();

            $table->foreign('colony_id')->references('id')->on('glx_colonies');
        });

        Schema::create('merchant_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->string('item_type', 50);
            $table->string('label', 255);
            $table->unsignedInteger('cost_credits');
            $table->text('payload')->nullable();
            $table->boolean('sold')->default(false);
            $table->timestamps();

            $table->foreign('visit_id')->references('id')->on('merchant_visits')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_items');
        Schema::dropIfExists('merchant_visits');
    }
};
