<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Candidate;
use Tests\Feature\Playtest\Utility\CandidateSource;
use Tests\Feature\Playtest\Utility\UtilitySelector;

final class FakeSource implements CandidateSource
{
    public int $calls = 0;

    /** @param list<Candidate> $candidates */
    public function __construct(private readonly string $name, private readonly int $band, private readonly array $candidates) {}

    public function name(): string
    {
        return $this->name;
    }

    public function band(): int
    {
        return $this->band;
    }

    public function candidates(BotSession $b, BotContext $ctx): array
    {
        $this->calls++;

        return $this->candidates;
    }
}

/** Spec 4, "Selektor (Unit, rein)": property tests of the band/score selection. */
class UtilitySelectorTest extends TestCase
{
    private static function c(string $name, int $band, ?float $net = null, int $ap = 1, string $key = '', bool $mandatory = false): Candidate
    {
        return new Candidate(name: $name, key: $key !== '' ? $key : $name, band: $band, net: $net, apCost: $ap,
            do: fn () => ['ok' => true], mandatory: $mandatory);
    }

    private static function bot(): BotSession
    {
        return (new \ReflectionClass(BotSession::class))->newInstanceWithoutConstructor();
    }

    public function test_empty_list_yields_null(): void
    {
        $this->assertNull(UtilitySelector::select([], []));
    }

    public function test_lowest_band_wins_over_any_score(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 5, 100.0), self::c('b', 2)], [])->chosen->name);
    }

    public function test_within_a_band_the_highest_score_per_ap_wins(): void
    {
        $chosen = UtilitySelector::select([self::c('x', 3, 10.0, 10), self::c('y', 3, 4.0, 2)], [])->chosen;

        $this->assertSame('y', $chosen->name);
        $this->assertSame(2000, $chosen->milliScore());
    }

    public function test_order_of_the_input_does_not_change_the_choice(): void
    {
        $set = [self::c('a', 1, 3.0), self::c('b', 1, 5.0, 2), self::c('c', 1, 2.6), self::c('d', 2, 99.0)];
        $expected = UtilitySelector::select($set, [])->chosen->key;

        foreach ([[3, 2, 1, 0], [1, 3, 0, 2], [2, 0, 3, 1]] as $order) {
            $this->assertSame($expected, UtilitySelector::select(array_map(fn (int $i) => $set[$i], $order), [])->chosen->key);
        }
    }

    public function test_equal_scores_fall_back_to_name_then_key(): void
    {
        $chosen = UtilitySelector::select([self::c('b', 1, 2.0, 1, 'k1'), self::c('a', 1, 2.0, 1, 'k2'), self::c('a', 1, 2.0, 1, 'k0')], [])->chosen;

        $this->assertSame(['a', 'k0'], [$chosen->name, $chosen->key]);
    }

    public function test_milli_rounding_turns_near_equal_scores_into_ties(): void
    {
        $chosen = UtilitySelector::select([self::c('b', 1, 1.0000001), self::c('a', 1, 1.0)], [])->chosen;

        $this->assertSame('a', $chosen->name);
    }

    public function test_blocked_names_are_never_chosen(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 1), self::c('b', 2)], ['a'])->chosen->name);
        $this->assertNull(UtilitySelector::select([self::c('a', 1)], ['a']));
    }

    public function test_scored_candidates_at_or_below_min_net_are_not_eligible(): void
    {
        $this->assertSame('b', UtilitySelector::select([self::c('a', 1, 0.01), self::c('b', 2)], [], 0.01)->chosen->name);
        $this->assertNull(UtilitySelector::select([self::c('a', 1, -3.0)], []));
    }

    public function test_unscored_candidates_are_always_eligible(): void
    {
        $this->assertFalse(self::c('a', 1)->isScored());
        $this->assertSame('a', UtilitySelector::select([self::c('a', 1)], [])->chosen->name);
    }

    public function test_positive_scaling_of_all_utilities_keeps_the_choice(): void
    {
        $nets = ['a' => 3.0, 'b' => 7.5, 'c' => 4.2];
        $pick = fn (float $f) => UtilitySelector::select(array_map(
            fn (string $n) => self::c($n, 1, $nets[$n] * $f, $n === 'b' ? 3 : 1), array_keys($nets)), [])->chosen->name;

        $this->assertSame($pick(1.0), $pick(10.0));
    }

    public function test_decision_lists_up_to_three_alternatives_and_the_candidate_count(): void
    {
        $d = UtilitySelector::select([self::c('a', 1, 5.0), self::c('b', 1, 4.0), self::c('c', 1, 3.0), self::c('d', 1, 2.0), self::c('e', 1, 1.0)], []);

        $this->assertSame(5, $d->candidatesN);
        $this->assertSame(['b', 'c', 'd'], array_column($d->alternatives, 'name'));
        $this->assertSame(4000, $d->alternatives[0]['score']);
    }

    public function test_source_search_stops_after_the_first_band_with_an_eligible_candidate(): void
    {
        $empty = new FakeSource('empty', 0, []);
        $hit = new FakeSource('hit', 1, [self::c('hit', 1)]);
        $late = new FakeSource('late', 2, [self::c('late', 2)]);

        $d = UtilitySelector::selectFromSources([$late, $hit, $empty], self::bot(), new BotContext(sol: 0), []);

        $this->assertSame('hit', $d->chosen->name);
        $this->assertSame([1, 1, 0], [$empty->calls, $hit->calls, $late->calls]);
    }

    public function test_sources_of_the_same_band_are_all_asked_before_stopping(): void
    {
        $x = new FakeSource('x', 1, [self::c('x', 1, 1.0)]);
        $y = new FakeSource('y', 1, [self::c('y', 1, 9.0)]);

        $this->assertSame('y', UtilitySelector::selectFromSources([$x, $y], self::bot(), new BotContext(sol: 0), [])->chosen->name);
    }

    public function test_full_search_gives_the_same_choice_as_the_early_stop(): void
    {
        $sources = fn () => [new FakeSource('a', 0, []), new FakeSource('b', 1, [self::c('b', 1)]), new FakeSource('c', 2, [self::c('c', 2, 50.0)])];

        $early = UtilitySelector::selectFromSources($sources(), self::bot(), new BotContext(sol: 0), []);
        $full = UtilitySelector::selectFromSources($sources(), self::bot(), new BotContext(sol: 0), [], fullSearch: true);

        $this->assertSame($early->chosen->key, $full->chosen->key);
        $this->assertCount(2, $full->all);
    }

    public function test_sources_below_the_cursor_band_and_blocked_sources_are_not_asked(): void
    {
        $below = new FakeSource('below', 0, [self::c('below', 0)]);
        $blocked = new FakeSource('blocked', 1, [self::c('blocked', 1)]);
        $open = new FakeSource('open', 2, [self::c('open', 2)]);

        $d = UtilitySelector::selectFromSources([$below, $blocked, $open], self::bot(), new BotContext(sol: 0), ['blocked'], minBand: 1);

        $this->assertSame('open', $d->chosen->name);
        $this->assertSame([0, 0, 1], [$below->calls, $blocked->calls, $open->calls]);
    }
}
