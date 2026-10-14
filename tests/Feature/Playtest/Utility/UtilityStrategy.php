<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;

/** Builds the rule list (old format) of the utility engines. */
final class UtilityStrategy
{
    /** Spec 2.8: stays rule-like (unscored, by band) until S8. */
    public const MANDATORY = ['repair_critical', 'place_agrardom', 'feed_colony'];

    /** Engine "utility" (S3): legacy rules with the migrated sources in their bands. */
    public static function rules(BotProfile $profile): array
    {
        return [(new Collector(self::sources($profile), 'utility', $profile))->rule()];
    }

    /** @return list<CandidateSource> */
    public static function sources(BotProfile $profile): array
    {
        $sources = [];
        foreach (array_values(BotStrategy::default($profile)) as $band => $rule) {
            $replacement = match ($rule['name']) {
                'invest_cc' => [new CcSource($band)],
                'invest_production' => [new UpgradeSource($band)],
                'research_knowledge' => [new ResearchSource($band)],
                'focus_engineering_levelup' => [],
                'request_ship' => [new FreighterSource($band), new LegacyRuleSource($rule, $band)],
                default => [new LegacyRuleSource($rule, $band, in_array($rule['name'], self::MANDATORY, true))],
            };
            array_push($sources, ...$replacement);
        }

        return $sources;
    }

    /** Engine "shadow" (S2): legacy decides, the utility pick is only logged (K22). */
    public static function shadow(BotProfile $profile): array
    {
        return self::fromRules(BotStrategy::default($profile), 'shadow', $profile);
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
