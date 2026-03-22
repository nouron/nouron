<?php

namespace App\Services;

/**
 * TickService — manages the current game tick.
 *
 * The game advances in ticks (one per day). The tick number is calculated
 * from the Unix timestamp, anchored to the daily calculation window
 * (configured via config/game.php → tick.calculation.end).
 *
 * Migrated from Core\Service\Tick (Laminas).
 */
class TickService
{
    protected int $tick;
    protected array $config;

    public function __construct(?int $tick = null)
    {
        $this->config = config('game.tick');

        if ($tick !== null && $tick > 0) {
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
        if ($tick > 0) {
            $this->tick = $tick;
        }
    }

    /**
     * Returns true if the daily tick calculation is currently running
     * (i.e., we are between calculation.start and calculation.end).
     *
     * @TODO: Distinguish summer/winter time (DST) — not yet implemented.
     */
    public function calculationIsRunning(): bool
    {
        $time       = time();
        $calcBegin  = (int) $this->config['calculation']['start'];
        $calcEnd    = (int) $this->config['calculation']['end'];

        return $time >= mktime($calcBegin) && $time < mktime($calcEnd);
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
