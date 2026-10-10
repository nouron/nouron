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

    /**
     * CI 2026-10-09: research_knowledge "succeeded" 200 times with an identical
     * response and no AP/Regolith/Organics change. Such a no-op repeat must
     * abort early with a message naming the rule — not be blocked silently
     * (that would hide the bug) and not burn the whole action cap.
     */
    public function test_a_repeated_no_op_success_aborts_early_naming_the_rule(): void
    {
        $bot = BotSession::boot($this, 1);
        $fired = 0;
        $rules = [[
            'name' => 'noop_rule',
            'when' => fn () => true,
            'do' => function (BotSession $b) use (&$fired) {
                $fired++;
                $b->log[] = ['sol' => $b->sol, 'rule' => 'noop_rule', 'url' => '/x', 'status' => 200, 'ok' => true, 'error' => null,
                    'ap_before' => 5, 'ap_after' => 5, 'regolith_before' => 9, 'regolith_after' => 9,
                    'organics_before' => 3, 'organics_after' => 3];

                return ['ok' => true, 'status' => 200, 'error' => null, 'body' => ['success' => true, 'same' => 1]];
            },
        ]];

        try {
            $this->playOneSol($bot, $rules);
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('no-op', $e->getMessage());
            $this->assertStringContainsString('noop_rule', $e->getMessage());
            $this->assertLessThanOrEqual(10, $fired);

            return;
        }

        $this->fail('A rule repeating an identical no-op success must abort the Sol');
    }

    public function test_repeated_successes_that_change_state_are_not_treated_as_no_ops(): void
    {
        $bot = BotSession::boot($this, 1);
        $fired = 0;
        $rules = [[
            'name' => 'progress_rule',
            'when' => function () use (&$fired) {
                return $fired < 30;
            },
            'do' => function (BotSession $b) use (&$fired) {
                $fired++;
                $b->log[] = ['sol' => $b->sol, 'rule' => 'progress_rule', 'url' => '/x', 'status' => 200, 'ok' => true, 'error' => null,
                    'ap_before' => 5, 'ap_after' => 5, 'regolith_before' => 9, 'regolith_after' => 9,
                    'organics_before' => 3, 'organics_after' => 3];

                // Same URL, no tracked-resource delta, but the response shows progress.
                return ['ok' => true, 'status' => 200, 'error' => null, 'body' => ['success' => true, 'ap_spend' => $fired]];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(30, $fired);
    }
}
