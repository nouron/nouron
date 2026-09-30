<?php

namespace App\Services;

/**
 * TickService — manages the current game tick.
 *
 * In an active run the canonical clock is the run's current_tick (advanced only
 * by the player via "Sol beenden"). The service is request-scoped and bound in
 * AppServiceProvider to the authenticated user's active run. Outside a run
 * (lobby, console fallback) it falls back to a time-based tick derived from the
 * Unix timestamp, anchored to the daily calculation window
 * (configured via config/game.php → tick.calculation.end).
 */
class TickService
{
    protected int $tick;

    protected array $config;

    public function __construct(?int $tick = null)
    {
        $this->config = config('game.tick');

        if ($tick !== null && $tick >= 0) {
            $this->tick = $tick;
        } else {
            $this->tick = $this->calculateTickFromTimestamp(time());
        }
    }

    /**
     * Returns the current tick number.
     */
    public function getTickCount(): int
    {
        return $this->tick;
    }

    /**
     * Override the tick (useful for testing or manual calculation runs).
     */
    public function setTickCount(int $tick): void
    {
        if ($tick >= 0) {
            $this->tick = $tick;
        }
    }

    /**
     * Derive tick count from a Unix timestamp.
     *
     * Formula: (timestamp − calc_end_hours) / 86400 = days since epoch = tick
     */
    public function calculateTickFromTimestamp(int $time): int
    {
        $calcEnd = (int) $this->config['calculation']['end'];

        return (int) floor(($time - 3600 * $calcEnd) / 86400);
    }

    public function __toString(): string
    {
        return (string) $this->tick;
    }
}
