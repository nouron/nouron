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
    // Safety net against a rule that keeps "succeeding" without changing state, not
    // a pacing limit. 50 aborted a legitimate Sol (baseline 2026-09-26, seed 7 Sol 74:
    // 63 actions on ~33 AP plus bar/merchant bonus AP); 200 leaves ample headroom
    // while an endless loop still trips it within a fraction of a second.
    private const MAX_ACTIONS_PER_SOL = 200;

    // CI 2026-10-09: a rule "succeeding" with an identical response and no AP/Regolith/
    // Organics change has done nothing observable — repeating it this many times in a
    // row aborts the Sol with the rule named, instead of silently burning the cap.
    private const NO_OP_REPEAT_LIMIT = 3;

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

    /** Stop condition for playSolsUntil(): true once the run has reached the start of Sol $sol. */
    private static function stopAtSol(int $sol): callable
    {
        return fn (BotSession $bot): bool => $bot->sol >= $sol;
    }

    /**
     * @param  array<int, array{name:string, when:callable, do:callable}>  $rules
     */
    private function playOneSol(BotSession $bot, array $rules): void
    {
        $blockedThisSol = [];
        $lastNoOp = null;
        $noOpRepeats = 0;

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

                $logCount = count($bot->log);
                $res = $rule['do']($bot, $candidate);
                if (! $res['ok']) {
                    $blockedThisSol[] = $rule['name'];

                    continue;
                }

                $noOp = self::noOpSignature($bot, $logCount, $rule['name'], $res);
                $noOpRepeats = ($noOp !== null && $noOp === $lastNoOp) ? $noOpRepeats + 1 : 0;
                $lastNoOp = $noOp;
                if ($noOpRepeats >= self::NO_OP_REPEAT_LIMIT) {
                    $this->fail("Rule '{$rule['name']}' repeated a no-op success (identical response, no AP/Regolith/Organics change) on Sol {$bot->sol} — likely state desync. Response: "
                        .json_encode($res['body'] ?? null).' Log tail: '.json_encode(array_slice($bot->log, -5)));
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

    /**
     * Signature of a successful action that changed nothing observable — exactly one
     * act() log entry with no AP/Regolith/Organics delta — or null when it did change
     * something (or made no/several requests, which this check cannot judge).
     */
    private static function noOpSignature(BotSession $bot, int $logCountBefore, string $rule, array $res): ?string
    {
        if (count($bot->log) !== $logCountBefore + 1) {
            return null;
        }
        $entry = $bot->log[$logCountBefore];
        if ($entry['ap_before'] !== $entry['ap_after']
            || $entry['regolith_before'] !== $entry['regolith_after']
            || $entry['organics_before'] !== $entry['organics_after']) {
            return null;
        }

        return $rule.'|'.$entry['url'].'|'.md5((string) json_encode($res['body'] ?? null));
    }
}
