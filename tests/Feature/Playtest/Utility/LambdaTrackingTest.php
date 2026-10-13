<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 2.4: λ_AP = EMA of the marginal density per Sol; a Sol ending with idle AP counts as 0. */
class LambdaTrackingTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_lambda_follows_the_last_executed_score_and_idle_sols_pull_it_down(): void
    {
        $bot = BotSession::boot($this, 1);
        $source = new class implements CandidateSource
        {
            /** @var array<int, bool> Sols in which the candidate already fired */
            public array $fired = [];

            public function name(): string
            {
                return 'work';
            }

            public function band(): int
            {
                return 0;
            }

            public function candidates(BotSession $b, BotContext $ctx): array
            {
                if (isset($this->fired[$b->sol])) {
                    return [];
                }

                return [new Candidate(name: 'work', key: 'work', band: 0, net: 3.0, apCost: 1, do: function (BotSession $s): array {
                    $this->fired[$s->sol] = true;
                    $s->log[] = ['sol' => $s->sol, 'rule' => 'work', 'url' => '/w/'.$s->sol, 'status' => 200, 'ok' => true, 'error' => null,
                        'ap_before' => 5, 'ap_after' => 4, 'regolith_before' => 0, 'regolith_after' => 0, 'organics_before' => 0, 'organics_after' => 0];

                    return ['ok' => true];
                })];
            }
        };
        $idleAp = 0;
        $collector = new Collector([$source], 'utility', BotProfile::named('default'), function () use (&$idleAp): int {
            return $idleAp;
        });
        // The fake source ignores the context; only the AP probe decides "idle".
        $rules = [$collector->rule()];

        $this->playOneSol($bot, $rules);   // Sol 0: score 3.0, no AP left
        $bot->sol = 1;
        $idleAp = 5;
        $this->playOneSol($bot, $rules);   // Sol 1: score 3.0, but ends with idle AP -> 0
        $bot->sol = 2;
        $this->playOneSol($bot, $rules);   // closes Sol 1

        $this->assertEqualsWithDelta(1.25, $bot->lambdaBySol[0], 1e-6);   // 0.7 * 0.5 + 0.3 * 3.0
        $this->assertEqualsWithDelta(0.875, $bot->lambdaBySol[1], 1e-6);  // 0.7 * 1.25 + 0.3 * 0
        $this->assertEqualsWithDelta(0.875, $bot->lambdaAp, 1e-6);
    }

    public function test_adapter_mode_never_tracks_lambda(): void
    {
        $bot = BotSession::boot($this, 1);
        $rules = UtilityStrategy::fromRules([['name' => 'noop', 'when' => fn () => null, 'do' => fn () => ['ok' => true]]], 'adapter', BotProfile::named('default'));

        $this->playOneSol($bot, $rules);
        $bot->sol = 1;
        $this->playOneSol($bot, $rules);

        $this->assertSame([], $bot->lambdaBySol);
        $this->assertNull($bot->lambdaAp);
    }
}
