<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Handelsposten (tradingPost) — Kanal-Rabatt-Freischaltung (Design-Spec
 * 2026-08-23, Abschnitt "Handelsposten"). Jede Ausbaustufe schaltet einen
 * zusätzlichen Handelskanal für den (bisher toten) merchant_price_bonus frei:
 * Stufe 1 = Cantina-Zufallsangebote, Stufe 2 = + Reisender Händler,
 * Stufe 3 = + Nexus/Corporate Contact. Kumulativ, nicht exklusiv — Stufe 3
 * gewährt den Rabatt auf allen drei Kanälen gleichzeitig.
 */
class TradingPostService
{
    /** @var array<string, int> Handelskanal => benötigte Handelsposten-Stufe */
    private const CHANNEL_THRESHOLDS = [
        'bar' => 1,
        'merchant' => 2,
        'corporate_contact' => 3,
    ];

    public function discountFor(int $colonyId, string $channel): float
    {
        $threshold = self::CHANNEL_THRESHOLDS[$channel] ?? null;
        if ($threshold === null) {
            return 0.0;
        }

        $level = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::TradingPost->value)
            ->value('level') ?? 0);

        if ($level < $threshold) {
            return 0.0;
        }

        return (float) config('buildings.tradingPost.merchant_price_bonus', 0.0);
    }
}
