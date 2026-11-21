<?php

namespace Tests\Concerns;

/**
 * T58d: planning part of tools/bot-tests.php (pure, unit-tested): which test units are still
 * open, in which order (slowest first by the last run's timings, unknown ones first), how long
 * the rest will take, and the progress line.
 */
final class TestRunPlan
{
    /**
     * @param  list<string>  $units  test files (or file::filter units)
     * @param  array<string, float>  $timings  seconds per unit from earlier runs
     * @param  array<string, string>  $results  status per unit from an interrupted run ('passed'/'failed')
     */
    public function __construct(
        private readonly array $units,
        private readonly array $timings = [],
        private readonly array $results = [],
    ) {}

    /** @return list<string> */
    public function pending(): array
    {
        $open = array_values(array_filter($this->units, fn (string $u): bool => ($this->results[$u] ?? null) !== 'passed'));
        usort($open, fn (string $a, string $b): int => [isset($this->timings[$a]), -($this->timings[$a] ?? 0), $a]
            <=> [isset($this->timings[$b]), -($this->timings[$b] ?? 0), $b]);

        return $open;
    }

    public function doneCount(): int
    {
        return count($this->units) - count($this->pending());
    }

    public function total(): int
    {
        return count($this->units);
    }

    public function expectedSeconds(string $unit): float
    {
        if (isset($this->timings[$unit])) {
            return $this->timings[$unit];
        }

        return $this->timings === [] ? 5.0 : array_sum($this->timings) / count($this->timings);
    }

    /** @param  list<string>  $open */
    public function remainingSeconds(array $open, int $workers): float
    {
        if ($open === []) {
            return 0.0;
        }
        $times = array_map(fn (string $u): float => $this->expectedSeconds($u), $open);

        return max(max($times), array_sum($times) / max(1, $workers));
    }

    public static function progressLine(int $done, int $total, string $unit, bool $ok, float $seconds, int $failures, int $elapsed, int $remaining): string
    {
        return sprintf(
            '[%d/%d] %s %s (%.1f s) · Fehler bisher: %d · vergangen %s · noch ca. %s',
            $done, $total, $ok ? 'ok  ' : 'FEHL', preg_replace('/\.php$/', '', basename(str_replace('::', '/', $unit))),
            $seconds, $failures, self::clock($elapsed), self::clock($remaining),
        );
    }

    private static function clock(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
