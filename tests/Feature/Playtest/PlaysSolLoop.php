<?php

namespace Tests\Feature\Playtest;

/**
 * Shared rule-firing loop for playtest bot tests: tries each rule top to
 * bottom, first match fires, a rule that fails is blocked for the rest of
 * the Sol. Used by PlaytestBotTest and PlaytestBotPhase1Test so the loop
 * semantics can't silently drift between them.
 *
 * Rule contract: `when(BotSession): mixed` returns the candidate it found
 * (falsy = no match), which is then passed to `do(BotSession, mixed): array`
 * — so a rule that needs a DB-queried candidate (a building row, a tile, an
 * offer, ...) computes it exactly once per attempt, not once to decide and
 * again to act.
 */
trait PlaysSolLoop
{
    private const MAX_ACTIONS_PER_SOL = 50;

    /**
     * Advance Sols by firing rules each Sol until the run ends, tick_limit+5
     * is hit (loop-safety outer bound), or $stop(bot) returns true — checked
     * after each completed Sol, e.g. to stop once Phase 2 is reached.
     *
     * $afterAction, if given, runs after the Sol's actions but BEFORE
     * nextSol() — for state that must be read pre-tick (e.g. RunReport's
     * per-Sol snapshot of that Sol's locked_actionpoints).
     *
     * @param  array<int, array{name:string, when:callable, do:callable}>  $rules
     */
    private function playSolsUntil(BotSession $bot, array $rules, ?callable $stop = null, ?callable $afterAction = null): void
    {
        $maxSols = config('game.run.tick_limit') + 5;

        while ($bot->isActive() && $bot->sol < $maxSols) {
            $this->playOneSol($bot, $rules);
            if ($afterAction !== null) {
                $afterAction($bot);
            }
            $bot->nextSol();

            if ($stop !== null && $stop($bot)) {
                return;
            }
        }
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

                $candidate = $rule['when']($bot);
                if (! $candidate) {
                    continue;
                }

                $res = $rule['do']($bot, $candidate);
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
