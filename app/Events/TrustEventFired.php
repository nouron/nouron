<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A known trust event (trust_events row) was just stored (T38 stage 1).
 * Same observer-only rule as JournalEventRecorded.
 */
class TrustEventFired
{
    use Dispatchable;

    public function __construct(
        public readonly int $colonyId,
        public readonly string $eventType,
        public readonly int $tick,
    ) {}
}
