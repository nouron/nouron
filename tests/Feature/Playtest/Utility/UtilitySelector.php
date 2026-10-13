<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/**
 * Spec 3.2: picks the best eligible candidate. Order: band ascending, milli-score
 * descending, name, key. Eligible = name not blocked and (unscored or net > min_net).
 * Pure: no DB access of its own.
 */
final class UtilitySelector
{
    public const DEFAULT_MIN_NET = 0.01;

    /**
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     */
    public static function select(array $candidates, array $blocked, float $minNet = self::DEFAULT_MIN_NET): ?Decision
    {
        $eligible = array_values(array_filter($candidates, fn (Candidate $c): bool => self::eligible($c, $blocked, $minNet)));
        if ($eligible === []) {
            return null;
        }
        usort($eligible, [self::class, 'compare']);

        $alternatives = array_map(fn (Candidate $c): array => [
            'name' => $c->name,
            'key' => $c->key,
            'band' => $c->band,
            'score' => $c->isScored() ? $c->milliScore() : null,
        ], array_slice($eligible, 1, 3));

        return new Decision($eligible[0], count($eligible), $alternatives, $candidates);
    }

    /**
     * Asks the sources band by band (stable for equal bands) and stops after the
     * first band that produced an eligible candidate — for legacy sources that is
     * exactly the first-match semantics, with the same `when` calls. Sources below
     * $minBand (the collector's cursor after a failure) and blocked sources are not
     * asked. $fullSearch asks every source (shadow mode); the choice is the same.
     *
     * @param  list<CandidateSource>  $sources
     * @param  list<string>  $blocked
     */
    public static function selectFromSources(array $sources, BotSession $b, BotContext $ctx, array $blocked, int $minBand = -1, float $minNet = self::DEFAULT_MIN_NET, bool $fullSearch = false): ?Decision
    {
        usort($sources, fn (CandidateSource $x, CandidateSource $y): int => $x->band() <=> $y->band());

        $collected = [];
        $bestBand = null;
        foreach ($sources as $source) {
            if ($bestBand !== null && ! $fullSearch && $source->band() > $bestBand) {
                break;
            }
            if ($source->band() < $minBand || in_array($source->name(), $blocked, true)) {
                continue;
            }
            foreach ($source->candidates($b, $ctx) as $candidate) {
                $collected[] = $candidate;
                if (self::eligible($candidate, $blocked, $minNet)) {
                    $bestBand = $bestBand === null ? $candidate->band : min($bestBand, $candidate->band);
                }
            }
        }

        $decision = self::select($collected, $blocked, $minNet);

        return $decision === null ? null
            : new Decision($decision->chosen, $decision->candidatesN, $decision->alternatives, $collected);
    }

    /** @param list<string> $blocked */
    private static function eligible(Candidate $c, array $blocked, float $minNet): bool
    {
        return ! in_array($c->name, $blocked, true) && ($c->net === null || $c->net > $minNet);
    }

    private static function compare(Candidate $a, Candidate $b): int
    {
        return [$a->band, -$a->milliScore(), $a->name, $a->key] <=> [$b->band, -$b->milliScore(), $b->name, $b->key];
    }
}
