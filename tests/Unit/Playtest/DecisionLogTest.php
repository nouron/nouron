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

    public function test_shadow_entry_carries_pick_terms_free_pick_and_top3(): void
    {
        $legacy = new Candidate(name: 'explore', key: 'explore', band: 0, net: null, apCost: 1, do: fn () => ['ok' => true]);
        $chosenScored = new Candidate(name: 'explore', key: 'explore', band: 0, net: 0.5, apCost: 1, do: fn () => ['ok' => true]);
        $d = UtilitySelector::select([$legacy], [])
            ->withShadow(self::c('a', 4.0), self::c('b', 6.0), [self::c('b', 6.0), self::c('a', 4.0)], $chosenScored);

        $entry = DecisionLog::entry(5, $d, true);

        // the legacy part of the entry keeps its shape
        $this->assertSame(['legacy', 'explore', null], [$entry['source'], $entry['name'], $entry['score']]);
        $this->assertSame(['name' => 'a', 'key' => 'k:a', 'band' => 1, 'score' => 2000, 'net' => 4.0,
            'terms' => ['gain' => 5.235, 'cost_rg' => -1.235]], array_diff_key($entry['shadow'], array_flip(['free', 'top3', 'chosen_score'])));
        $this->assertSame(['name' => 'b', 'key' => 'k:b', 'score' => 3000], $entry['shadow']['free']);
        $this->assertSame(['b', 'a'], array_column($entry['shadow']['top3'], 'name'));
        $this->assertSame(['gain' => 7.235, 'cost_rg' => -1.235], $entry['shadow']['top3'][0]['terms']);
        $this->assertSame(500, $entry['shadow']['chosen_score']);
    }

    public function test_entry_without_shadow_has_a_null_shadow(): void
    {
        $this->assertNull(DecisionLog::entry(0, UtilitySelector::select([self::c('a', 1.0)], []), true)['shadow']);
    }
}
