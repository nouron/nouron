<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds bar_offers.visit_id — GDD §12 Kanal 1 "Corvan wird die zentrale
 * Handelsfigur der Cantina" (Freigegeben 2026-08-05).
 *
 * Corvan's commodity offers (Alltagsgeschäft: buy + Organika-Verkauf, see
 * MerchantService) are persisted as bar_offers rows tied to the merchant_visits
 * row that spawned them, reusing the existing accept/negotiate/AP-charge
 * pipeline. NULL (the default, all pre-existing rows) means a generic guest
 * offer from the anonymous Cantina rotation — no backfill needed, existing
 * rows keep their current meaning unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bar_offers', function (Blueprint $table): void {
            $table->unsignedBigInteger('visit_id')->nullable()->after('colony_id');
            $table->foreign('visit_id')->references('id')->on('merchant_visits')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('bar_offers', function (Blueprint $table): void {
            $table->dropForeign(['visit_id']);
            $table->dropColumn('visit_id');
        });
    }
};
