<?php

namespace Tests\Feature\Playtest\Utility;

/** Pure building blocks of the utility model (spec 2.1-2.4). */
final class Valuation
{
    /** D(H) = Σ_{t=1..H} γ^t = γ(1 − γ^H)/(1 − γ); 0 for H <= 0, H for γ = 1. */
    public static function discountSum(float $gamma, int $h): float
    {
        if ($h <= 0) {
            return 0.0;
        }
        if ($gamma === 1.0) {
            return (float) $h;
        }

        return $gamma * (1 - $gamma ** $h) / (1 - $gamma);
    }

    /** A yield of $perSol per Sol starting after $latency Sols: x · γ^L · D(H − L). */
    public static function stream(float $perSol, int $latency, float $gamma, int $h): float
    {
        return $perSol * $gamma ** $latency * self::discountSum($gamma, $h - $latency);
    }

    public static function horizon(int $sol, int $tickLimit, int $max): int
    {
        return max(0, min($tickLimit - $sol, $max));
    }

    public static function scarcity(float $demand, float $stock, float $lo, float $hi): float
    {
        return max($lo, min($hi, $demand / max(1.0, $stock)));
    }

    public static function lambdaEma(float $prev, float $marginal, float $alpha): float
    {
        return (1 - $alpha) * $prev + $alpha * $marginal;
    }

    /** 1 up to $fromSol, then +$step per Sol (spec 2.2 Phasenziel). */
    public static function urgency(int $sol, int $fromSol, float $step): float
    {
        return $sol <= $fromSol ? 1.0 : 1.0 + $step * ($sol - $fromSol);
    }
}
