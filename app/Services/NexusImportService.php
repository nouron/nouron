<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Nexus direct import prices (GDD §3 "Uplink-Station" direct import, §4
 * "Handelsposten" tier III, §12 "Handelsvorteil"; A13/P4).
 *
 * The ONE place that turns a base price from `config('game.economy.*')` into the
 * price a colony actually pays. The credit check, the debit, the log event and
 * the Kommandozentrale preview all read it from here, so the shown price is the
 * charged price. The Handelsvorteil of the 'nexus' channel (Handelsposten tier 3
 * + trade knowledge, no Konsul) lowers the PRICE only — the delivery time stays
 * an exclusive Uplink-Station-level lever (Owner-Entscheidung F5).
 *
 * Rounding: the discounted price is rounded to whole Credits PER UNIT
 * (TradeAdvantageService::applyToPrice(), min. 1); the order total is
 * amount x unit price, so "N Cr/Einheit" on screen is exact for every amount.
 */
class NexusImportService
{
    public const RES_REGOLITH = 3;

    public const RES_COMPOUNDS = 4;

    public const RES_ORGANICS = 5;

    public function __construct(private readonly TradeAdvantageService $tradeAdvantageService) {}

    /**
     * Importable resource ids in display order.
     *
     * @return list<int>
     */
    public function resourceIds(): array
    {
        return [self::RES_REGOLITH, self::RES_COMPOUNDS, self::RES_ORGANICS];
    }

    /** Undiscounted Credits price per unit, null for a resource that cannot be imported. */
    public function basePrice(int $resourceId): ?int
    {
        return match ($resourceId) {
            self::RES_COMPOUNDS => (int) config('game.economy.compound_import_price', 165),
            self::RES_REGOLITH, self::RES_ORGANICS => $this->delayedBasePrice($resourceId),
            default => null,
        };
    }

    /** Credits per unit after the Handelsvorteil of the 'nexus' channel. Throws for a non-importable resource. */
    public function unitPrice(int $colonyId, int $resourceId): int
    {
        $base = $this->basePrice($resourceId)
            ?? throw new InvalidArgumentException("Resource {$resourceId} cannot be imported from the Nexus.");
        $advantage = $this->tradeAdvantageService->forChannel($colonyId, TradeAdvantageService::CHANNEL_NEXUS);

        return $this->tradeAdvantageService->applyToPrice($base, $advantage);
    }

    public function totalCost(int $colonyId, int $resourceId, int $amount): int
    {
        return $amount * $this->unitPrice($colonyId, $resourceId);
    }

    /**
     * Everything the Kommandozentrale preview needs, from one advantage lookup.
     *
     * @return array{
     *     resources: array<int, array{base: int, price: int}>,
     *     advantage: array{channel: string, sources: list<array>, total: float, total_percent: int, capped: bool}
     * }
     */
    public function quote(int $colonyId): array
    {
        $advantage = $this->tradeAdvantageService->forChannel($colonyId, TradeAdvantageService::CHANNEL_NEXUS);

        $resources = [];
        foreach ($this->resourceIds() as $resourceId) {
            $base = $this->basePrice($resourceId);
            if ($base === null) {
                continue;
            }
            $resources[$resourceId] = [
                'base' => $base,
                'price' => $this->tradeAdvantageService->applyToPrice($base, $advantage),
            ];
        }

        return ['resources' => $resources, 'advantage' => $advantage];
    }

    private function delayedBasePrice(int $resourceId): ?int
    {
        $price = config("game.economy.delayed_import_price.{$resourceId}");

        return $price === null ? null : (int) $price;
    }
}
