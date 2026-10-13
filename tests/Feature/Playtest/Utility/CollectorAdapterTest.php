<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 3.2/3.3: behind the collector the legacy rules get the same when/do calls in the same order. */
class CollectorAdapterTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    private static function log(BotSession $b, string $rule, bool $ok): void
    {
        $b->log[] = ['sol' => $b->sol, 'rule' => $rule, 'url' => '/x/'.$rule.'/'.count($b->log), 'status' => $ok ? 200 : 422,
            'ok' => $ok, 'error' => $ok ? null : 'rejected', 'ap_before' => 5, 'ap_after' => $ok ? 4 : 5,
            'regolith_before' => 9, 'regolith_after' => 9, 'organics_before' => 3, 'organics_after' => 3];
    }

    /** @param list<string> $trace */
    private function rules(array &$trace): array
    {
        $fired = 0;

        return [
            ['name' => 'first',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:first';

                    return null;
                },
                'do' => fn () => ['ok' => true]],
            ['name' => 'flaky',
                'when' => function () use (&$trace) {
                    $trace[] = 'when:flaky';

                    return 'f';
                },
                'do' => function (BotSession $b) use (&$trace) {
                    $trace[] = 'do:flaky';
                    self::log($b, 'flaky', false);

                    return ['ok' => false];
                }],
            ['name' => 'worker',
                'when' => function () use (&$trace, &$fired) {
                    $trace[] = 'when:worker';

                    return $fired < 2 ? 'w' : null;
                },
                'do' => function (BotSession $b, string $c) use (&$trace, &$fired) {
                    $trace[] = 'do:worker:'.$c;
                    $fired++;
                    self::log($b, 'worker', true);

                    return ['ok' => true];
                }],
        ];
    }

    public function test_adapter_calls_when_and_do_in_the_legacy_order(): void
    {
        $bot = BotSession::boot($this, 1);
        $legacyTrace = [];
        $this->playOneSol($bot, $this->rules($legacyTrace));
        $legacyLog = array_column($bot->log, 'rule');

        $bot->log = [];
        $adapterTrace = [];
        $this->playOneSol($bot, UtilityStrategy::fromRules($this->rules($adapterTrace), 'adapter', BotProfile::named('default')));

        $this->assertSame($legacyTrace, $adapterTrace);
        $this->assertSame($legacyLog, array_column($bot->log, 'rule'));
        $this->assertSame(['flaky'], $bot->blockedThisSol);
    }

    public function test_bands_follow_the_rule_index_and_mandatory_rules_are_marked(): void
    {
        $rules = BotStrategy::default(BotProfile::named('default'));
        $sources = UtilityStrategy::legacySourcesFrom($rules);

        $this->assertCount(count($rules), $sources);
        foreach ($sources as $i => $source) {
            $this->assertSame($rules[$i]['name'], $source->name());
            $this->assertSame($i, $source->band());
        }
        $mandatory = array_values(array_map(fn (LegacyRuleSource $s) => $s->name(), array_filter($sources, fn (LegacyRuleSource $s) => $s->mandatory)));
        $this->assertSame(['repair_critical', 'place_agrardom', 'feed_colony'], $mandatory);
    }
}
