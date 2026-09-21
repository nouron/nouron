<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Player-facing view of the Handelsvorteil (GDD §12 "Was der Angebotsdialog zeigt", A13/P2b).
 *
 * Pure presentation: it formats what TradeAdvantageService computed and never
 * re-derives a number (so a shown percentage is the applied one). Every ACTIVE
 * source becomes its own line with its qualifier (Konsul rank, Handelsposten
 * tier, trade level); inactive sources only appear as a quiet hint that says
 * what the missing source would bring. Values in hints come from the config.
 *
 * Sign: Cantina offers give MORE goods ("+X %"), the credit-priced channels
 * (merchant, nexus) give a price discount ("−X %").
 */
class TradeAdvantagePresenter
{
    private const MINUS = '−';

    private const ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];

    public function __construct(
        private readonly TradeAdvantageService $tradeAdvantageService,
        private readonly TradingPostService $tradingPostService,
    ) {}

    /**
     * @return array{
     *     channel: string, total_percent: int, total_text: string, sign: string,
     *     lines: list<array{key: string, label: string, short: string, percent: int, percent_text: string}>,
     *     sources_text: string, summary: string|null, hints: list<string>
     * }
     */
    public function forChannel(int $colonyId, string $channel): array
    {
        return $this->present($colonyId, $this->tradeAdvantageService->forChannel($colonyId, $channel));
    }

    /**
     * @param  array  $advantage  A TradeAdvantageService::forChannel() result (possibly with sources excluded).
     */
    public function present(int $colonyId, array $advantage): array
    {
        $channel = $advantage['channel'];
        $sign = $channel === TradeAdvantageService::CHANNEL_BAR ? '+' : self::MINUS;
        $sources = collect($advantage['sources'])->keyBy('key');

        $lines = [];
        foreach ($advantage['sources'] as $source) {
            if ($source['percent'] <= 0) {
                continue;
            }
            $lines[] = [
                'key' => $source['key'],
                'label' => $this->label($colonyId, $source['key']),
                'short' => $this->shortLabel($colonyId, $source['key']).' '.$sign.$source['percent'],
                'percent' => $source['percent'],
                'percent_text' => $sign.$source['percent'].' %',
            ];
        }

        $totalText = $sign.$advantage['total_percent'].' %';
        $sourcesText = implode(', ', array_column($lines, 'short'));
        $summaryKey = $channel === TradeAdvantageService::CHANNEL_BAR
            ? 'colony.trade_advantage_header'
            : 'colony.trade_price_advantage_line';

        return [
            'channel' => $channel,
            'total_percent' => $advantage['total_percent'],
            'total_text' => $totalText,
            'sign' => $sign,
            'lines' => $lines,
            'sources_text' => $sourcesText,
            'summary' => $advantage['total_percent'] > 0
                ? __($summaryKey, ['total' => $totalText, 'sources' => $sourcesText])
                : null,
            'hints' => $this->hints($colonyId, $channel, $sources),
        ];
    }

    /** "Konsul (Rang 2)" / "Handelsposten (Stufe I)" / "Kenntnis Handel (Lv 3)". */
    private function label(int $colonyId, string $key): string
    {
        return match ($key) {
            TradeAdvantageService::SOURCE_CONSUL => __('colony.trade_line_consul', [
                'rank' => $this->tradeAdvantageService->consulRank($colonyId),
            ]),
            TradeAdvantageService::SOURCE_TRADING_POST => __('colony.trade_line_trading_post', [
                'tier' => $this->roman($this->tradingPostService->level($colonyId)),
            ]),
            default => __('colony.trade_line_trade_knowledge', ['level' => $this->tradeKnowledgeLevel($colonyId)]),
        };
    }

    /** "Konsul Rang 2" / "Handelsposten" / "Handel Lv3" — for the one-line summary. */
    private function shortLabel(int $colonyId, string $key): string
    {
        return match ($key) {
            TradeAdvantageService::SOURCE_CONSUL => __('colony.trade_short_consul', [
                'rank' => $this->tradeAdvantageService->consulRank($colonyId),
            ]),
            TradeAdvantageService::SOURCE_TRADING_POST => __('colony.trade_short_trading_post'),
            default => __('colony.trade_short_trade_knowledge', ['level' => $this->tradeKnowledgeLevel($colonyId)]),
        };
    }

    /**
     * Quiet hints for a missing Konsul / Handelsposten. Only for sources that
     * belong to the channel AND are part of this advantage (a fixed-price lot
     * excludes all sources, so it gets no hints).
     *
     * @param  Collection<string, array>  $sources
     * @return list<string>
     */
    private function hints(int $colonyId, string $channel, $sources): array
    {
        $hints = [];

        if ($sources->has(TradeAdvantageService::SOURCE_CONSUL)
            && $this->tradeAdvantageService->consulRank($colonyId) === 0) {
            $hints[] = __('colony.trade_hint_no_consul', [
                'rank' => __('advisors.dialog_rank_junior'),
                'percent' => (int) round(((float) config('game.bar.trader_discount.1', 0.0)) * 100),
            ]);
        }

        $post = $sources->get(TradeAdvantageService::SOURCE_TRADING_POST);
        if ($post !== null && $post['percent'] <= 0) {
            $percent = (int) round(((float) config('buildings.tradingPost.merchant_price_bonus', 0.0)) * 100);
            $tier = $this->tradingPostService->requiredTier($channel) ?? 1;

            if ($channel === TradeAdvantageService::CHANNEL_BAR) {
                $cc = DB::table('buildings')
                    ->where('id', BuildingId::TradingPost->value)
                    ->value('required_building_level');
                if ($cc !== null) {
                    $hints[] = __('colony.trade_hint_trading_post_bar', ['cc' => (int) $cc, 'percent' => $percent]);
                }
            } else {
                $hints[] = __('colony.trade_hint_trading_post_tier', ['tier' => $this->roman($tier), 'percent' => $percent]);
            }
        }

        return $hints;
    }

    private function tradeKnowledgeLevel(int $colonyId): int
    {
        return (int) DB::table('colony_researches')
            ->where('colony_id', $colonyId)
            ->where('research_id', (int) config('knowledge.trade.id'))
            ->value('level');
    }

    private function roman(int $level): string
    {
        return self::ROMAN[$level] ?? (string) $level;
    }
}
