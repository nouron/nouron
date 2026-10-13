<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\DecisionLog;
use Tests\Feature\Playtest\Utility\UtilitySelector;

class DecisionLogTest extends TestCase
{
    private static function c(string $name, ?float $net, bool $mandatory = false): Candidate
    {
        return new Candidate(name: $name, key: "k:{$name}", band: 1, net: $net, apCost: 2, do: fn () => ['ok' => true],
            terms: $net === null ? [] : ['gain' => $net + 1.23456, 'cost_rg' => -1.23456], mandatory: $mandatory);
    }

    public function test_utility_entry_carries_score_net_and_rounded_terms(): void
    {
        $d = UtilitySelector::select([self::c('a', 4.0), self::c('b', 2.0)], []);

        $entry = DecisionLog::entry(3, $d, true);

        $this->assertSame(['sol' => 3, 'source' => 'utility', 'name' => 'a', 'key' => 'k:a', 'band' => 1, 'score' => 2000, 'net' => 4.0],
            array_intersect_key($entry, array_flip(['sol', 'source', 'name', 'key', 'band', 'score', 'net'])));
        $this->assertSame(['gain' => 5.235, 'cost_rg' => -1.235], $entry['terms']);
        $this->assertSame(['b'], array_column($entry['alternatives'], 'name'));
        $this->assertSame(2, $entry['candidates_n']);
        $this->assertTrue($entry['ok']);
    }

    public function test_legacy_and_mandatory_sources(): void
    {
        $this->assertSame('legacy', DecisionLog::entry(0, UtilitySelector::select([self::c('a', null)], []), false)['source']);
        $this->assertNull(DecisionLog::entry(0, UtilitySelector::select([self::c('a', null)], []), false)['score']);
        $this->assertSame('mandatory', DecisionLog::entry(0, UtilitySelector::select([self::c('feed', null, true)], []), true)['source']);
    }
}
