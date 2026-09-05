<?php

namespace App\Services\Techtree;

/**
 * Translates a knowledge's per-level effect CURVE value into a short readable
 * line — Owner-Playtest-Fund 2026-08-31 (follow-up to BuildingUnlockService,
 * PR #297): buildings show what the NEXT level unlocks via a pure data
 * lookup; knowledge levels have no discrete unlock to look up the same way —
 * their effect is a continuous curve (config/knowledge.php + config/game.php
 * `*_per_lv`/`*_per_level` arrays, from the Kenntnis-Effekte-Redesign
 * series). This is the mapping (curve → label/unit/direction) that content
 * would otherwise have to spell out per level — the numbers themselves stay
 * in config, only the small, stable mapping (7 knowledges, 1-3 effects
 * each) lives here.
 *
 * A level whose curve entry is 0 (no change at that level, e.g. trade's
 * bar_offer_boost_per_lv has zeros at Lv1/4/5) produces no line — matching
 * BuildingUnlockService's "nothing to show if nothing changes" behaviour.
 */
class KnowledgeEffectDescriptionService
{
    private const DIRECTION_REDUCTION = 'reduction';

    private const DIRECTION_INCREASE = 'increase';

    /**
     * @var array<string, list<array{config: string, label: string, unit: string, direction: string}>>
     */
    private const EFFECTS = [
        'construction' => [
            ['config' => 'knowledge.construction.ap_cost_reduction_per_lv', 'label' => 'AP-Kosten', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
        ],
        'cartography' => [
            ['config' => 'knowledge.cartography.nav_ap_reduction_per_lv', 'label' => 'AP-Kosten', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
        ],
        'geology' => [
            ['config' => 'game.geology_harvester_bonus_per_level', 'label' => 'Harvester-Ertrag', 'unit' => 'Rg/Sol', 'direction' => self::DIRECTION_INCREASE, 'resAbbr' => 'RG', 'resCls' => 'Rg'],
            ['config' => 'game.geology_instability_risk_reduction_per_lv', 'label' => 'Instabilitäts-Risiko', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
        ],
        'agronomy' => [
            ['config' => 'game.agronomy_agrardom_bonus_per_level', 'label' => 'Agrardom-Ertrag', 'unit' => 'Or/Sol', 'direction' => self::DIRECTION_INCREASE, 'resAbbr' => 'OR', 'resCls' => 'Or'],
        ],
        'health' => [
            ['config' => 'game.health_plague_risk_reduction_per_lv', 'label' => 'Seuchenausbruch-Risiko', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
        ],
        'trade' => [
            ['config' => 'knowledge.trade.ap_cost_reduction_per_lv', 'label' => 'AP-Kosten', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
            ['config' => 'knowledge.trade.bar_offer_boost_per_lv', 'label' => 'Bar-Angebotsslot', 'unit' => '', 'direction' => self::DIRECTION_INCREASE],
            ['config' => 'knowledge.trade.trade_price_bonus_per_lv', 'label' => 'Handelspreis-Bonus', 'unit' => '%', 'direction' => self::DIRECTION_INCREASE],
        ],
        'defense' => [
            ['config' => 'game.defense_storm_risk_reduction_per_lv', 'label' => 'Sturm-Risiko', 'unit' => '%', 'direction' => self::DIRECTION_REDUCTION],
        ],
    ];

    /** @return list<array{text: string, chip: null|array{abbr: string, value: string, cls: string}}> */
    public function effectsAtLevel(string $knowledgeKey, int $level): array
    {
        if ($level < 1 || ! isset(self::EFFECTS[$knowledgeKey])) {
            return [];
        }

        $lines = [];
        foreach (self::EFFECTS[$knowledgeKey] as $effect) {
            $curve = config($effect['config'], []);
            $delta = (int) ($curve[$level] ?? 0);
            if ($delta === 0) {
                continue;
            }

            $lines[] = $this->formatLine($delta, $effect);
        }

        return $lines;
    }

    /**
     * @param  array{config: string, label: string, unit: string, direction: string, resAbbr?: string, resCls?: string}  $effect
     * @return array{text: string, chip: null|array{abbr: string, value: string, cls: string}}
     */
    private function formatLine(int $delta, array $effect): array
    {
        $sign = $effect['direction'] === self::DIRECTION_REDUCTION ? '-' : '+';
        $unit = $effect['unit'];

        // Effects tied to an actual resource (Rg, Or) render as a resourcebar
        // chip in the sidebar — the label alone is the text, the number/unit
        // moves into the chip. Everything else (%, slot counts) has no
        // matching resource, so it stays a single formatted text line.
        if (isset($effect['resAbbr'], $effect['resCls'])) {
            return [
                'text' => $effect['label'],
                'chip' => ['abbr' => $effect['resAbbr'], 'value' => "{$sign}{$delta}/Sol", 'cls' => $effect['resCls']],
            ];
        }

        if ($unit === '%') {
            return ['text' => "{$sign}{$delta}% {$effect['label']}", 'chip' => null];
        }

        if ($unit === '') {
            // Singular/plural label chosen by the caller-side config entry
            // (only trade's bar-slot effect uses this — always ±1 in practice).
            return ['text' => "{$sign}{$delta} {$effect['label']}", 'chip' => null];
        }

        return ['text' => "{$sign}{$delta} {$unit} {$effect['label']}", 'chip' => null];
    }
}
