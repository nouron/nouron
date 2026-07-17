<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 4: rules 7-10 (Phase 2 — research, missions, bar, ship purchase) added
 * to the Phase-1 core. No RunReport yet (step 5) — this just proves the full
 * rule set plays a whole run without a 5xx and without hanging.
 */
class PlaytestBotFullRunTest extends TestCase
{
    use RefreshDatabase;

    private const MAX_ACTIONS_PER_SOL = 50;

    public function test_bot_plays_a_full_run_with_all_rules_without_5xx(): void
    {
        $bot = BotSession::boot($this, seed: 4242);
        $rules = BotStrategy::default();

        $maxSols = config('game.run.tick_limit') + 5;

        while ($bot->isActive() && $bot->sol < $maxSols) {
            $this->playOneSol($bot, $rules);
            $bot->nextSol();
        }

        $this->assertNotEquals(
            'active',
            $bot->status(),
            'Run must end within tick_limit + 5 sols. Log tail: '.json_encode(array_slice($bot->log, -20))
        );

        $okCount = count(array_filter($bot->log, fn ($entry) => $entry['ok']));
        $this->assertGreaterThan(0, $okCount, 'Bot must have taken at least one successful action');
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
