<?php

namespace App\Events;

use App\Models\Run;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a new run is created (ADR 0003 — Pub/Sub groundwork for the
 * later Multiplayer Resolution Engine). No listeners yet.
 */
class RunStarted
{
    use Dispatchable;

    public function __construct(
        public readonly Run $run,
    ) {}
}
