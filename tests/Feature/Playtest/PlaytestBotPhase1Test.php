<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Step 3: BotStrategy's Phase-1 core (rules 1-6) driving the bot instead of
 * a bare /sol/next loop. Goal: the run reaches Phase 2 (checkPhase1Completion:
 * CC Lv3 + 2 production buildings Lv2 + 3 advisors). If it doesn't, that's a
 * balance finding to report, not something to patch around with a bypass.
 */
class PlaytestBotPhase1Test extends TestCase
{
    use RefreshDatabase;

    private const MAX_ACTIONS_PER_SOL = 50;

    public function test_bot_reaches_phase_2_using_phase_1_rules(): void
    {
        $bot = BotSession::boot($this, seed: 4242);
        $rules = BotStrategy::default();

        $maxSols = config('game.run.tick_limit') + 5;

        while ($bot->isActive() && $bot->sol < $maxSols) {
            $this->playOneSol($bot, $rules);
            $bot->nextSol();

            $phaseNow = (int) DB::table('runs')->where('id', $bot->runId)->value('phase');
            if ($phaseNow >= 2) {
                break;
            }
        }

        $phase = (int) DB::table('runs')->where('id', $bot->runId)->value('phase');

        $this->assertGreaterThanOrEqual(
            2,
            $phase,
            "Bot never reached Phase 2 (sol={$bot->sol}, status={$bot->status()}, fail_reason={$bot->failReason()}). ".
            'Action log tail: '.json_encode(array_slice($bot->log, -20))
        );
    }

    /**
     * @param  array<int, array{name:string, when:callable, do:callable}>  $rules
     */
    private function playOneSol(BotSession $bot, array $rules): void
    {
        $blockedThisSol = [];

        for ($i = 0; $i < self::MAX_ACTIONS_PER_SOL; $i++) {
            $fired = false;

            foreach ($rules as $rule) {
                if (in_array($rule['name'], $blockedThisSol, true)) {
                    continue;
                }
                if (! $rule['when']($bot)) {
                    continue;
                }

                $res = $rule['do']($bot);
                if (! $res['ok']) {
                    $blockedThisSol[] = $rule['name'];

                    continue;
                }

                $fired = true;
                break;
            }

            if (! $fired) {
                return;
            }
        }

        $this->fail('MAX_ACTIONS_PER_SOL exceeded on Sol '.$bot->sol.' — likely state desync. Log tail: '
            .json_encode(array_slice($bot->log, -20)));
    }
}
