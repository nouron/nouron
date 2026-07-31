<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds bar_offers.is_negotiated — Cantina-Verhandlung (GDD §12 Kanal 1) is now a
 * two-step flow: a successful negotiation improves the offer's terms in place
 * (give_amount/get_amount updated) and flags it as negotiated, but does not
 * execute the trade — the player still has to click "Annehmen" to finalize.
 * acceptOffer() waives the AP cost for an already-negotiated offer (already paid
 * during the negotiation step).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bar_offers', function (Blueprint $table): void {
            $table->boolean('is_negotiated')->default(false)->after('is_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('bar_offers', function (Blueprint $table): void {
            $table->dropColumn('is_negotiated');
        });
    }
};
