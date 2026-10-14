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

    /** One Sol in which $candidate fires once, closed by the next; returns λ after Sol 0. */
    private function lambdaAfterOne(Candidate $candidate): float
    {
        $bot = BotSession::boot($this, 1);
        $source = new class($candidate) implements CandidateSource
        {
            public function __construct(private readonly Candidate $candidate) {}

            public function name(): string
            {
                return $this->candidate->name;
            }

            public function band(): int
            {
                return 0;
            }

            public function candidates(BotSession $b, BotContext $ctx): array
            {
                $fired = array_filter($b->log, fn (array $e): bool => $e['rule'] === $this->candidate->name) !== [];

                return $fired ? [] : [$this->candidate];
            }
        };
        $rules = [(new Collector([$source], 'utility', BotProfile::named('default'), fn (): int => 0))->rule()];

        $this->playOneSol($bot, $rules);
        $bot->sol = 1;
        $this->playOneSol($bot, $rules);

        return $bot->lambdaBySol[0];
    }

    private function work(float $net, int $apCost, int $cycleAp = 0): Candidate
    {
        return new Candidate(name: 'work', key: 'work', band: 0, net: $net, apCost: $apCost, do: function (BotSession $s): array {
            $s->log[] = ['sol' => $s->sol, 'rule' => 'work', 'url' => '/w/'.$s->sol, 'status' => 200, 'ok' => true, 'error' => null,
                'ap_before' => 5, 'ap_after' => 4, 'regolith_before' => 0, 'regolith_after' => 0, 'organics_before' => 0, 'organics_after' => 0];

            return ['ok' => true];
        }, cycleAp: $cycleAp);
    }

    public function test_the_finishing_click_of_a_cycle_counts_with_the_whole_cycle_s_ap(): void
    {
        // Review S3 I5: net 20 over a 10-AP cycle is density 2, not 20 for the last click.
        $this->assertEqualsWithDelta(0.7 * 0.5 + 0.3 * 2.0, $this->lambdaAfterOne($this->work(20.0, 1, 10)), 1e-6);
    }

    public function test_the_marginal_density_is_capped(): void
    {
        // Review S3 I5: λ feeds the advisor-slot value it is measured from — capped at lambda_ap_max (3).
        $this->assertEqualsWithDelta(0.7 * 0.5 + 0.3 * 3.0, $this->lambdaAfterOne($this->work(100.0, 1)), 1e-6);
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
