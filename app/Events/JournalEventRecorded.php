<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A journal line (colony_log) was just stored. T38 stage 1: lets observers such
 * as achievements react at the moment of the trigger instead of at tick time.
 * Listeners must only observe/store — never alter game state that the tick reads.
 */
class JournalEventRecorded
{
    use Dispatchable;

    public function __construct(
        public readonly int $logId,
        public readonly int $userId,
        public readonly int $tick,
        public readonly string $event,
        public readonly string $area,
        public readonly string $parameters,
    ) {}
}
