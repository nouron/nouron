<?php

namespace Tests\Feature\Console;

use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Baseline 2026-09-26 (B1): a seed that failed inside PHPUnit showed up as
 * "failed to run:" followed by nothing — the command printed only
 * errorOutput(), but PHPUnit reports failures on stdout.
 */
class PlaytestCommandFailureOutputTest extends TestCase
{
    public function test_failed_run_shows_the_phpunit_stdout(): void
    {
        Process::fake([
            '*' => Process::result(
                output: "FAILURES!\nMAX_ACTIONS_PER_SOL exceeded on Sol 74",
                errorOutput: '',
                exitCode: 1,
            ),
        ]);

        $this->artisan('game:playtest', ['--seeds' => '7', '--concurrency' => 1])
            ->expectsOutputToContain('failed to run')
            ->expectsOutputToContain('MAX_ACTIONS_PER_SOL exceeded on Sol 74')
            ->assertSuccessful();
    }

    public function test_long_stdout_is_truncated_to_its_tail(): void
    {
        $noise = str_repeat("progress line\n", 2000);
        Process::fake([
            '*' => Process::result(
                output: "HEAD-MARKER\n".$noise.'TAIL-MARKER',
                errorOutput: '',
                exitCode: 1,
            ),
        ]);

        $this->artisan('game:playtest', ['--seeds' => '7', '--concurrency' => 1])
            ->expectsOutputToContain('TAIL-MARKER')
            ->doesntExpectOutputToContain('HEAD-MARKER')
            ->assertSuccessful();
    }
}
