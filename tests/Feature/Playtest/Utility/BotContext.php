<?php

namespace Tests\Feature\Playtest\Utility;

/** State shared by all sources for one decision (spec 3.1). Extended in S2 (Task 11). */
final class BotContext
{
    /** @param array<string, mixed> $weights */
    public function __construct(
        public readonly int $sol,
        public readonly array $weights = [],
    ) {}
}
