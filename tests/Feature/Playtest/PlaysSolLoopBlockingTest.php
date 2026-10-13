<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spec 3.3: a failed action blocks the name it was logged under (or an explicit
 * `block`), not the rule — otherwise one rejected candidate would block the whole
 * utility collector for the Sol. Legacy rules log under their own name, so for
 * them nothing changes.
 */
class PlaysSolLoopBlockingTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private static function log(BotSession $b, string $rule, bool $ok): void
    {
        $b->log[] = ['sol' => $b->sol, 'rule' => $rule, 'url' => '/x/'.$rule.'/'.count($b->log), 'status' => $ok ? 200 : 422,
            'ok' => $ok, 'error' => $ok ? null : 'rejected', 'ap_before' => 5, 'ap_after' => $ok ? 4 : 5,
            'regolith_before' => 9, 'regolith_after' => 9, 'organics_before' => 3, 'organics_after' => 3];
    }

    public function test_a_failed_decision_blocks_its_logged_name_and_the_same_rule_is_asked_again(): void
    {
        $bot = BotSession::boot($this, 1);
        $done = false;
        $rules = [[
            'name' => 'collector',
            'when' => function (BotSession $b) use (&$done) {
                if ($done) {
                    return null;
                }

                return in_array('a', $b->blockedThisSol, true) ? 'b' : 'a';
            },
            'do' => function (BotSession $b, string $pick) use (&$done) {
                self::log($b, $pick, $pick === 'b');
                $done = $pick === 'b';

                return ['ok' => $pick === 'b'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['a', 'b'], array_column($bot->log, 'rule'));
        $this->assertSame(['a'], $bot->blockedThisSol);
    }

    public function test_an_explicit_block_name_wins_over_the_log_entry(): void
    {
        $bot = BotSession::boot($this, 1);
        $rules = [[
            'name' => 'collector',
            'when' => fn (BotSession $b) => in_array('y', $b->blockedThisSol, true) ? null : 'go',
            'do' => function (BotSession $b) {
                self::log($b, 'x', false);

                return ['ok' => false, 'block' => 'y'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['y'], $bot->blockedThisSol);
    }

    public function test_legacy_rules_continue_with_the_next_rule_after_a_failure(): void
    {
        $bot = BotSession::boot($this, 1);
        $trace = [];
        $fired = 0;
        $rules = [
            ['name' => 'r1',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:r1';

                    return true;
                },
                'do' => function (BotSession $b) use (&$trace) {
                    $trace[] = 'do:r1';
                    self::log($b, 'r1', false);

                    return ['ok' => false];
                }],
            ['name' => 'r2',
                'when' => function () use (&$trace, &$fired) {
                    $trace[] = 'when:r2';

                    return $fired < 1;
                },
                'do' => function (BotSession $b) use (&$trace, &$fired) {
                    $trace[] = 'do:r2';
                    $fired++;
                    self::log($b, 'r2', true);

                    return ['ok' => true];
                }],
        ];

        $this->playOneSol($bot, $rules);

        $this->assertSame(['when:r1', 'do:r1', 'when:r2', 'do:r2', 'when:r2'], $trace);
        $this->assertSame(['r1'], $bot->blockedThisSol);
    }

    public function test_a_failure_without_a_log_entry_blocks_the_rule_name(): void
    {
        $bot = BotSession::boot($this, 1);
        $calls = 0;
        $rules = [[
            'name' => 'silent',
            'when' => function () use (&$calls) {
                $calls++;

                return true;
            },
            'do' => fn () => ['ok' => false],
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(1, $calls);
        $this->assertSame(['silent'], $bot->blockedThisSol);
    }

    public function test_an_already_blocked_block_name_falls_back_to_the_rule_name(): void
    {
        $bot = BotSession::boot($this, 1);
        $calls = 0;
        $rules = [[
            'name' => 'loop',
            'when' => fn () => true,
            'do' => function () use (&$calls) {
                $calls++;

                return ['ok' => false, 'block' => 'same'];
            },
        ]];

        $this->playOneSol($bot, $rules);

        $this->assertSame(2, $calls);
        $this->assertSame(['same', 'loop'], $bot->blockedThisSol);
    }

    public function test_the_blocked_list_starts_empty_every_sol(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->playOneSol($bot, [['name' => 'silent', 'when' => fn () => true, 'do' => fn () => ['ok' => false]]]);
        $this->assertSame(['silent'], $bot->blockedThisSol);

        $seen = null;
        $this->playOneSol($bot, [['name' => 'probe', 'when' => function (BotSession $b) use (&$seen) {
            $seen ??= $b->blockedThisSol;

            return null;
        }, 'do' => fn () => ['ok' => true]]]);

        $this->assertSame([], $seen);
    }
}
