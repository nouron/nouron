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

    /**
     * What the S3 utility engine would pick (spec S2 shadow, §9 Präzisierungen):
     * band first, the score decides only within a band — exactly select(). null
     * when no candidate is scored at all (the utility has no opinion).
     *
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     */
    public static function shadowPick(array $candidates, array $blocked, float $minNet, int $minBand = -1): ?Candidate
    {
        if (! self::anyScored($candidates)) {
            return null;
        }

        return self::select(array_values(array_filter($candidates, fn (Candidate $c): bool => $c->band >= $minBand)), $blocked, $minNet)?->chosen;
    }

    /**
     * Cross-band pick (informational only, not K22): a mandatory candidate if
     * any (by band), else the best milli-score across all bands; null when no
     * candidate is scored.
     *
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     */
    public static function freePick(array $candidates, array $blocked, float $minNet, int $minBand = -1): ?Candidate
    {
        $eligible = array_values(array_filter($candidates, fn (Candidate $c): bool => $c->band >= $minBand && self::eligible($c, $blocked, $minNet)));

        $mandatory = array_values(array_filter($eligible, fn (Candidate $c): bool => $c->mandatory));
        if ($mandatory !== []) {
            usort($mandatory, [self::class, 'compare']);

            return $mandatory[0];
        }

        return self::shadowTop3($candidates, $blocked, $minNet, $minBand)[0] ?? null;
    }

    /**
     * The best three scored, eligible candidates by milli-score across all bands
     * (spec S2: read the top 3 per Sol, check whether one term dominates).
     *
     * @param  list<Candidate>  $candidates
     * @param  list<string>  $blocked
     * @return list<Candidate>
     */
    public static function shadowTop3(array $candidates, array $blocked, float $minNet, int $minBand = -1): array
    {
        $scored = array_values(array_filter($candidates, fn (Candidate $c): bool => $c->isScored() && $c->band >= $minBand && self::eligible($c, $blocked, $minNet)));
        usort($scored, fn (Candidate $x, Candidate $y): int => [-$x->milliScore(), $x->name, $x->key] <=> [-$y->milliScore(), $y->name, $y->key]);

        return array_slice($scored, 0, 3);
    }

    /** @param list<Candidate> $candidates */
    private static function anyScored(array $candidates): bool
    {
        foreach ($candidates as $c) {
            if ($c->isScored()) {
                return true;
            }
        }

        return false;
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
