<?php

namespace App\Events;

use App\Models\Run;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a run finishes, win or loss (ADR 0003 — Pub/Sub groundwork for
 * the later Multiplayer Resolution Engine). No listeners yet.
 */
class RunEnded
{
    use Dispatchable;

    public function __construct(
        public readonly Run $run,
        public readonly string $status,
        public readonly ?string $failReason,
    ) {}
}
