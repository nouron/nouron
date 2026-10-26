<?php

namespace App\Support;

/**
 * Player-facing text for a mission reward, derived from the reward definition in
 * config/missions.php — so the amount on the mission card can never drift from the amount
 * that is actually paid out. Texts live in lang/*\/missions.php (reward_* keys).
 */
final class MissionReward
{
    /** reward key => lang key of its amount template (":amount" is replaced) */
    private const AMOUNT_PARTS = [
        'credits' => 'reward_credits',
        'regolith' => 'reward_regolith',
        'compounds' => 'reward_compounds',
        'organics' => 'reward_organics',
        'research_ap' => 'reward_research_ap',
        'reveal_tiles' => 'reward_reveal_tiles',
    ];

    /** reward key => lang key of its fixed text */
    private const FIXED_PARTS = [
        'harvester_instance' => 'reward_harvester_instance',
        'find_salvage' => 'reward_find_salvage',
    ];

    /** True when the family yield factor changes the payout of this reward (amount parts, also inside a loot table). */
    public static function scales(array $reward): bool
    {
        foreach ($reward['loot_table'] ?? [] as $option) {
            if (self::scales($option)) {
                return true;
            }
        }

        return array_intersect_key($reward, self::AMOUNT_PARTS) !== [];
    }

    /** @param array<string, mixed> $reward one reward definition from config/missions.php */
    public static function label(array $reward, float $factor = 1.0): string
    {
        if (isset($reward['loot_table'])) {
            $options = array_map(fn (array $option) => self::label($option, $factor), $reward['loot_table']);

            return __('missions.reward_loot_table', ['options' => implode(' / ', $options)]);
        }

        $parts = [];
        foreach (self::AMOUNT_PARTS as $key => $langKey) {
            if (array_key_exists($key, $reward)) {
                $parts[] = __("missions.{$langKey}", ['amount' => self::amount($reward[$key], $factor)]);
            }
        }
        foreach (self::FIXED_PARTS as $key => $langKey) {
            if (! empty($reward[$key])) {
                $parts[] = __("missions.{$langKey}");
            }
        }
        if (isset($reward['trust_event'])) {
            $parts[] = __('missions.reward_trust');
        }

        return implode(' + ', $parts);
    }

    /**
     * @param  int|array{0: int, 1: int}  $amount  fixed amount or [min, max]
     * @param  float  $factor  path-depth yield factor, rounded like the payout (GameTick::payMissionRewards)
     */
    private static function amount(int|array $amount, float $factor): string
    {
        $scale = fn (int $value): int => (int) round($value * $factor);

        return is_array($amount) ? $scale($amount[0]).'–'.$scale($amount[1]) : (string) $scale($amount);
    }
}
