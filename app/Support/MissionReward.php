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
        'deep_scan' => 'reward_deep_scan',
        'harvester_instance' => 'reward_harvester_instance',
    ];

    /** @param array<string, mixed> $reward one reward definition from config/missions.php */
    public static function label(array $reward): string
    {
        if (isset($reward['loot_table'])) {
            $options = array_map(fn (array $option) => self::label($option), $reward['loot_table']);

            return __('missions.reward_loot_table', ['options' => implode(' / ', $options)]);
        }

        $parts = [];
        foreach (self::AMOUNT_PARTS as $key => $langKey) {
            if (array_key_exists($key, $reward)) {
                $parts[] = __("missions.{$langKey}", ['amount' => self::amount($reward[$key])]);
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

    /** @param int|array{0: int, 1: int} $amount fixed amount or [min, max] */
    private static function amount(int|array $amount): string
    {
        return is_array($amount) ? $amount[0].'–'.$amount[1] : (string) $amount;
    }
}
