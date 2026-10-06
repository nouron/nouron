<?php

namespace Tests\Feature\Playtest;

/**
 * Tunable bot playstyle dials. Continuous float knobs (not a fixed enum) so
 * future dimensions (risk tolerance, exploration eagerness, ...) slot in
 * without restructuring — named presets are just convenience constructors
 * over the same typed shape. Add a new property + a case in named() when a
 * new dimension is needed; existing profiles keep their old default (0.0)
 * for it automatically, so nothing else needs to change.
 *
 * Besides the float dials there is one discrete, orthogonal dimension: $opening
 * (which path building the bot builds first, see OPENINGS). 'auto' = today's behaviour.
 */
final class BotProfile
{
    public const OPENINGS = ['auto', 'labor', 'hangar', 'cantina'];

    public function __construct(
        public readonly string $name = 'default',
        // 0.0 = today's behaviour (spend whenever affordable, no reserve
        // awareness). 1.0 = maximum thrift: hold back discretionary spends
        // once task_credit_reserve is drawn and not yet complete.
        public readonly float $savingsAggressiveness = 0.0,
        // 0.0 = generalist (today's behaviour). > 0.0 = plays towards the drawn,
        // still-open Phase-2 objectives (A45 focus rules in BotStrategy) — the
        // "targeted play" half of the GDD §15 calibration rule.
        public readonly float $objectiveFocus = 0.0,
        // Discrete dimension, orthogonal to the float dials above: which path building the
        // bot builds FIRST (labor = sciencelab 31, hangar 44, cantina 52). 'auto' keeps
        // today's behaviour (sciencelab-first by menu order). Only affects Sol 1-~15.
        public readonly string $opening = 'auto',
    ) {
        if (! in_array($opening, self::OPENINGS, true)) {
            throw new \InvalidArgumentException("Unknown bot opening: {$opening}");
        }
    }

    /** NOTE: when adding a new dial to the constructor, copy it here too. */
    public function withOpening(string $opening): self
    {
        return new self($this->name, $this->savingsAggressiveness, $this->objectiveFocus, $opening);
    }

    public static function named(string $name): self
    {
        return match ($name) {
            'default' => new self('default'),
            'thrifty' => new self('thrifty', savingsAggressiveness: 1.0),
            'focus' => new self('focus', savingsAggressiveness: 1.0, objectiveFocus: 1.0),
            // The one combination of the two existing dials 'default'/'thrifty'/'focus'
            // didn't cover: targets the drawn, still-open objectives (like 'focus') but
            // without the credit-reserve brake (like 'default') — plays fast and loose
            // towards the goal instead of hoarding along the way.
            'eager' => new self('eager', savingsAggressiveness: 0.0, objectiveFocus: 1.0),
            default => throw new \InvalidArgumentException("Unknown bot profile: {$name}"),
        };
    }
}
