<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Concerns\TestRunPlan;

/**
 * T58d (owner 2026-11-20): the bot test runner shows progress, can be interrupted and resumed
 * without losing finished work, and starts the slowest units first so one long file does not
 * decide the end of the run alone.
 */
class TestRunPlanTest extends TestCase
{
    public function test_units_are_ordered_longest_first_by_last_timings_unknown_first(): void
    {
        $plan = new TestRunPlan(['a.php', 'b.php', 'c.php'], ['a.php' => 2.0, 'b.php' => 30.0]);

        $this->assertSame(['c.php', 'b.php', 'a.php'], $plan->pending());
    }

    public function test_resume_skips_units_that_passed(): void
    {
        $plan = new TestRunPlan(['a.php', 'b.php', 'c.php'], [], ['a.php' => 'passed', 'b.php' => 'failed']);

        $this->assertSame(['b.php', 'c.php'], $plan->pending());
        $this->assertSame(1, $plan->doneCount());
        $this->assertSame(3, $plan->total());
    }

    public function test_remaining_time_spreads_the_expected_seconds_over_the_workers(): void
    {
        $plan = new TestRunPlan(['a.php', 'b.php', 'c.php', 'd.php'], ['a.php' => 40.0, 'b.php' => 20.0, 'c.php' => 20.0, 'd.php' => 10.0]);

        // 90 s of work on 3 workers, but never less than the longest unit still open.
        $this->assertEqualsWithDelta(40.0, $plan->remainingSeconds(['a.php', 'b.php', 'c.php', 'd.php'], 3), 0.01);
        $this->assertEqualsWithDelta(20.0, $plan->remainingSeconds(['b.php', 'c.php', 'd.php'], 4), 0.01); // 50 s / 4 < longest 20 s
        $this->assertEqualsWithDelta(25.0, $plan->remainingSeconds(['b.php', 'c.php', 'd.php'], 2), 0.01);
    }

    public function test_unknown_units_count_with_the_average_known_duration(): void
    {
        $plan = new TestRunPlan(['a.php', 'b.php', 'x.php'], ['a.php' => 10.0, 'b.php' => 30.0]);

        $this->assertEqualsWithDelta(20.0, $plan->expectedSeconds('x.php'), 0.01);
    }

    public function test_progress_line_reads_like_a_sentence(): void
    {
        $this->assertSame(
            '[17/62] ok   BotStrategyRepairTest (3.1 s) · Fehler bisher: 2 · vergangen 1:12 · noch ca. 0:40',
            TestRunPlan::progressLine(17, 62, 'tests/Feature/Playtest/BotStrategyRepairTest.php', true, 3.14, 2, 72, 40),
        );
    }
}
