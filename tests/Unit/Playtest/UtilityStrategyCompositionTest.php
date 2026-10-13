<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\LegacyRuleSource;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\Feature\Playtest\Utility\UtilityStrategy;

class UtilityStrategyCompositionTest extends TestCase
{
    /** @return array<string, int> rule name => legacy index */
    private static function bands(BotProfile $profile): array
    {
        return array_flip(array_column(BotStrategy::default($profile), 'name'));
    }

    public function test_migrated_sources_take_the_band_of_the_rule_they_replace(): void
    {
        $profile = BotProfile::named('default');
        $bands = self::bands($profile);
        $byClass = [];
        foreach (UtilityStrategy::sources($profile) as $source) {
            $byClass[$source::class][] = $source->band();
        }

        $this->assertSame([$bands['invest_cc']], $byClass[CcSource::class]);
        $this->assertSame([$bands['invest_production']], $byClass[UpgradeSource::class]);
        $this->assertSame([$bands['research_knowledge']], $byClass[ResearchSource::class]);
        $this->assertSame([$bands['request_ship']], $byClass[FreighterSource::class]);
    }

    public function test_all_other_rules_stay_legacy_sources(): void
    {
        $profile = BotProfile::named('focus');
        $legacy = array_values(array_map(fn (LegacyRuleSource $s) => $s->name(), array_filter(UtilityStrategy::sources($profile), fn ($s) => $s instanceof LegacyRuleSource)));
        $expected = array_values(array_diff(array_column(BotStrategy::default($profile), 'name'), ['invest_cc', 'invest_production', 'research_knowledge', 'focus_engineering_levelup']));

        $this->assertSame($expected, $legacy);
    }
}
