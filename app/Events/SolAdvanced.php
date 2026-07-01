<?php

namespace App\Events;

use App\Models\Run;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired at the end of a fully processed game tick (ADR 0003 — Pub/Sub
 * groundwork for the later Multiplayer Resolution Engine). No listeners yet.
 */
class SolAdvanced
{
    use Dispatchable;

    public function __construct(
        public readonly Run $run,
        public readonly int $tick,
    ) {}
}
