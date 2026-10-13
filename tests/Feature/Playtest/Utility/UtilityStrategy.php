<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;

/** Builds the rule list (old format) of the utility engines. */
final class UtilityStrategy
{
    /** Spec 2.8: stays rule-like (unscored, by band) until S8. */
    public const MANDATORY = ['repair_critical', 'place_agrardom', 'feed_colony'];

    /** Engine "utility". S0-S2: every rule is still a legacy source. */
    public static function rules(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'utility', $profile);
    }

    /** Golden-test reference: every legacy rule behind the selector, nothing migrated. */
    public static function adapterOnly(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'adapter', $profile);
    }

    /** @param list<array{name:string, when:callable, do:callable}> $rules */
    public static function fromRules(array $rules, string $mode, BotProfile $profile): array
    {
        return [(new Collector(self::legacySourcesFrom($rules), $mode, $profile))->rule()];
    }

    /**
     * @param  list<array{name:string, when:callable, do:callable}>  $rules
     * @return list<LegacyRuleSource>
     */
    public static function legacySourcesFrom(array $rules): array
    {
        $sources = [];
        foreach (array_values($rules) as $band => $rule) {
            $sources[] = new LegacyRuleSource($rule, $band, in_array($rule['name'], self::MANDATORY, true));
        }

        return $sources;
    }
}
