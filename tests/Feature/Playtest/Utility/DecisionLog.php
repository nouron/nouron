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
            'terms' => array_map(fn (float $v): float => round($v, 3), $c->terms),
            'alternatives' => $d->alternatives,
            'candidates_n' => $d->candidatesN,
            'ok' => $ok,
        ];
    }
}
