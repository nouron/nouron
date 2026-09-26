<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

/**
 * Baseline 2026-09-26 (B1): MAX_ACTIONS_PER_SOL = 50 aborted a legitimate run
 * (seed 7, Sol 74: 63 successful actions on ~33 AP plus bar/merchant bonus AP).
 * The cap must leave room for such Sols but still catch a rule that keeps
 * "succeeding" without changing anything.
 */
class PlaysSolLoopActionCapTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_a_busy_but_finite_sol_is_not_aborted(): void
    {
        $bot = BotSession::boot($this, 1);
        $fired = 0;
        $rules = [[
            'name' => 'busy',
            'when' => function () use (&$fired) {
                return $fired < 120;
            },
            'do' => function () use (&$fired) {
                $fired++;

                return ['ok' => true];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(120, $fired);
    }

    public function test_an_endless_rule_is_still_caught(): void
    {
        $bot = BotSession::boot($this, 1);
        $fired = 0;
        $rules = [[
            'name' => 'endless',
            'when' => fn () => true,
            'do' => function () use (&$fired) {
                $fired++;

                return ['ok' => true];
            },
        ]];

        try {
            $this->playOneSol($bot, $rules);
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('MAX_ACTIONS_PER_SOL exceeded', $e->getMessage());
            $this->assertLessThanOrEqual(1000, $fired);

            return;
        }

        $this->fail('An endlessly firing rule must abort the Sol');
    }
}
