<?php

namespace Tests\Feature\Playtest;

/**
 * Tunable bot playstyle dials. Continuous float knobs (not a fixed enum) so
 * future dimensions (risk tolerance, exploration eagerness, ...) slot in
 * without restructuring — named presets are just convenience constructors
 * over the same typed shape. Add a new property + a case in named() when a
 * new dimension is needed; existing profiles keep their old default (0.0)
 * for it automatically, so nothing else needs to change.
 */
final class BotProfile
{
    public function __construct(
        public readonly string $name = 'default',
        // 0.0 = today's behaviour (spend whenever affordable, no reserve
        // awareness). 1.0 = maximum thrift: hold back discretionary spends
        // once task_credit_reserve is drawn and not yet complete.
        public readonly float $savingsAggressiveness = 0.0,
    ) {}

    public static function named(string $name): self
    {
        return match ($name) {
            'default' => new self('default'),
            'thrifty' => new self('thrifty', savingsAggressiveness: 1.0),
            default => throw new \InvalidArgumentException("Unknown bot profile: {$name}"),
        };
    }
}
