<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Handelsvorteil (GDD §12, A13): the single, additive trade advantage per
 * trade channel. It is the ONLY place that knows which sources exist — both
 * the execution paths (BarService::acceptOffer(), MerchantService::buyItem(),
 * CorporateContactService::buyHarvesterOffer()) and every display path
 * (Cantina offer list, Corvan's special inventory, Orin's dialog) read it from
 * here, so a shown amount/price can never differ from the executed one.
 *
 * Channels and their sources (all additive, never multiplied):
 *   - bar      Konsul rank + Handelsposten (tier >= 1) + trade knowledge
 *   - merchant Handelsposten (tier >= 2) + trade knowledge   (no Konsul)
 *   - nexus    Handelsposten (tier 3)    + trade knowledge   (no Konsul)
 *
 * Application: on the Cantina channel the advantage multiplies the Get side
 * (more goods, price stays — applyToAmount()); on the credit-priced channels it
 * lowers the price (applyToPrice()). Rounding: half up on integers (basis
 * points), so a small amount may show no visible gain while its source line is
 * still listed.
 */
class TradeAdvantageService
{
    public const CHANNEL_BAR = 'bar';

    public const CHANNEL_MERCHANT = 'merchant';

    public const CHANNEL_NEXUS = 'nexus';

    public const SOURCE_CONSUL = 'consul';

    public const SOURCE_TRADING_POST = 'trading_post';

    public const SOURCE_TRADE_KNOWLEDGE = 'trade_knowledge';

    /** Channel => ordered list of source keys that apply to it. */
    private const CHANNEL_SOURCES = [
        self::CHANNEL_BAR => [self::SOURCE_CONSUL, self::SOURCE_TRADING_POST, self::SOURCE_TRADE_KNOWLEDGE],
        self::CHANNEL_MERCHANT => [self::SOURCE_TRADING_POST, self::SOURCE_TRADE_KNOWLEDGE],
        self::CHANNEL_NEXUS => [self::SOURCE_TRADING_POST, self::SOURCE_TRADE_KNOWLEDGE],
    ];

    private const SOURCE_LABEL_KEYS = [
        self::SOURCE_CONSUL => 'colony.trade_source_consul',
        self::SOURCE_TRADING_POST => 'colony.trade_source_trading_post',
        self::SOURCE_TRADE_KNOWLEDGE => 'colony.trade_source_trade_knowledge',
    ];

    public function __construct(
        private readonly TradingPostService $tradingPostService,
        private readonly ProjectBonusService $projectBonusService,
    ) {}

    /**
     * The advantage for a colony on one channel.
     *
     * Every source applicable to the channel is listed, including inactive ones
     * (value 0) — the offer dialog decides what to show and can hint at what a
     * missing Konsul or Handelsposten would bring.
     *
     * @param  string[]  $excludeSources  Source keys to leave out (provisional: the
     *                                    negotiated-offer Handelsposten exclusion, P3 removes it).
     * @return array{
     *     channel: string,
     *     sources: list<array{key: string, label_key: string, value: float, percent: int}>,
     *     total: float,
     *     total_percent: int,
     *     capped: bool
     * }
     */
    public function forChannel(int $colonyId, string $channel, array $excludeSources = []): array
    {
        if (! isset(self::CHANNEL_SOURCES[$channel])) {
            throw new InvalidArgumentException("Unknown trade channel '{$channel}'.");
        }

        $sources = [];
        $sumBp = 0;

        foreach (self::CHANNEL_SOURCES[$channel] as $key) {
            if (in_array($key, $excludeSources, true)) {
                continue;
            }

            $bp = $this->sourceBasisPoints($colonyId, $channel, $key);
            $sumBp += $bp;
            $sources[] = [
                'key' => $key,
                'label_key' => self::SOURCE_LABEL_KEYS[$key],
                'value' => $bp / 10000,
                'percent' => intdiv($bp, 100),
            ];
        }

        $capBp = (int) round(((float) config("game.bar.trade_terms.silent_cap.{$channel}", 1.0)) * 10000);
        $capped = $sumBp > $capBp;
        $totalBp = $capped ? $capBp : $sumBp;

        return [
            'channel' => $channel,
            'sources' => $sources,
            'total' => $totalBp / 10000,
            'total_percent' => intdiv($totalBp, 100),
            'capped' => $capped,
        ];
    }

    /**
     * Get-side application: amount x (1 + advantage), rounded half up. Never
     * below the base amount. "+X %" means exactly "X % more goods".
     */
    public function applyToAmount(int $amount, array $advantage): int
    {
        $bp = $this->totalBasisPoints($advantage);

        return intdiv($amount * (10000 + $bp) + 5000, 10000);
    }

    /**
     * Price-side application: price x (1 - advantage), rounded to the nearest
     * Credit, never below 1 (a free item stays free).
     */
    public function applyToPrice(int $price, array $advantage): int
    {
        if ($price <= 0) {
            return $price;
        }

        $bp = $this->totalBasisPoints($advantage);

        return max(1, intdiv($price * (10000 - $bp) + 5000, 10000));
    }

    /** Konsul (trader advisor) rank — 0 if none assigned or unavailable (e.g. on a mission). */
    public function consulRank(int $colonyId): int
    {
        return (int) (DB::table('advisors')
            ->where('colony_id', $colonyId)
            ->where('personell_id', AdvisorService::idFor('trader'))
            ->whereNull('unavailable_until_tick')
            ->value('rank') ?? 0);
    }

    private function totalBasisPoints(array $advantage): int
    {
        return (int) round(((float) $advantage['total']) * 10000);
    }

    /** One source's contribution in basis points (1 bp = 0.01 %) — integer math avoids float drift in sums. */
    private function sourceBasisPoints(int $colonyId, string $channel, string $key): int
    {
        return match ($key) {
            self::SOURCE_CONSUL => (int) round(
                ((float) config('game.bar.trader_discount.'.$this->consulRank($colonyId), 0.0)) * 10000
            ),
            self::SOURCE_TRADING_POST => (int) round(
                $this->tradingPostService->discountFor($colonyId, $channel) * 10000
            ),
            self::SOURCE_TRADE_KNOWLEDGE => $this->projectBonusService->tradePriceBonusPercent($colonyId) * 100,
            default => throw new InvalidArgumentException("Unknown trade advantage source '{$key}'."),
        };
    }
}
