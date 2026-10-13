<?php

namespace Tests\Feature\Playtest\Utility;

/** Spec 2.10: one compact, explainable log entry per executed decision. */
final class DecisionLog
{
    public static function entry(int $sol, Decision $d, bool $ok): array
    {
        $c = $d->chosen;

        return [
            'sol' => $sol,
            'source' => $c->mandatory ? 'mandatory' : ($c->isScored() ? 'utility' : 'legacy'),
            'name' => $c->name,
            'key' => $c->key,
            'band' => $c->band,
            'score' => $c->isScored() ? $c->milliScore() : null,
            'net' => $c->net === null ? null : round($c->net, 3),
            'terms' => self::terms($c),
            'alternatives' => $d->alternatives,
            'candidates_n' => $d->candidatesN,
            'shadow' => self::shadow($d),
            'ok' => $ok,
        ];
    }

    /**
     * Shadow mode only: the band-faithful pick (K22) with net and terms, the
     * cross-band pick (`free`), the utility top 3 and the legacy choice's own score.
     */
    private static function shadow(Decision $d): ?array
    {
        if ($d->shadow === null) {
            return null;
        }
        $s = $d->shadow;

        return [
            'name' => $s->name,
            'key' => $s->key,
            'band' => $s->band,
            'score' => $s->isScored() ? $s->milliScore() : null,
            'net' => $s->net === null ? null : round($s->net, 3),
            'terms' => self::terms($s),
            'free' => $d->shadowFree === null ? null
                : ['name' => $d->shadowFree->name, 'key' => $d->shadowFree->key, 'score' => $d->shadowFree->milliScore()],
            'top3' => array_map(fn (Candidate $c): array => [
                'name' => $c->name,
                'key' => $c->key,
                'band' => $c->band,
                'score' => $c->milliScore(),
                'net' => round((float) $c->net, 3),
                'terms' => self::terms($c),
            ], $d->shadowTop3),
            'chosen_score' => $d->chosenEstimate?->milliScore(),
        ];
    }

    /** @return array<string, float> */
    private static function terms(Candidate $c): array
    {
        return array_map(fn (float $v): float => round($v, 3), $c->terms);
    }
}
