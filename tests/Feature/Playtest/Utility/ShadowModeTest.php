<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec S2: the shadow mode scores the legacy candidates but never changes the decision. */
class ShadowModeTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    public function test_shadow_decision_keeps_the_legacy_choice_and_carries_band_and_free_pick(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 1);   // phase 1: only the CC Lv2 step is urgent
        $rules = [
            ['name' => 'explore_tile', 'when' => fn () => (object) ['q' => 1, 'r' => 0], 'do' => fn () => ['ok' => true]],
            ['name' => 'invest_cc', 'when' => fn () => true, 'do' => fn () => ['ok' => true]],
        ];
        $collector = new Collector(UtilityStrategy::legacySourcesFrom($rules), 'shadow', BotProfile::named('default'));

        $decision = $collector->when($bot);

        $this->assertSame('explore_tile', $decision->chosen->name);
        // band-faithful (S3): the unscored explore_tile in the lower band stays first
        $this->assertSame('explore_tile', $decision->shadow?->name);
        // cross-band info: the scored CC step
        $this->assertSame('invest_cc', $decision->shadowFree?->name);
        $this->assertTrue($decision->shadowFree->isScored());
        $this->assertSame(['invest_cc'], array_map(fn (Candidate $c): string => $c->name, $decision->shadowTop3));
    }

    public function test_legacy_valuation_scores_only_the_migrating_classes(): void
    {
        $bot = BotSession::boot($this, 1);
        $ctx = BotContext::capture($bot, BotProfile::named('default'));
        $cc = new Candidate(name: 'invest_cc', key: 'invest_cc', band: 6, net: null, apCost: 0, do: fn () => ['ok' => true], payload: true);
        $explore = new Candidate(name: 'explore_tile', key: 'explore_tile', band: 15, net: null, apCost: 0, do: fn () => ['ok' => true]);

        $this->assertNotNull(LegacyValuation::estimate($cc, $bot, $ctx));
        $this->assertNull(LegacyValuation::estimate($explore, $bot, $ctx));
    }
}
