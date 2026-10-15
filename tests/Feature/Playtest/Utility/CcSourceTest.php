<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\TestCase;

/** T30 §14 U4: an unaffordable CC level is never attempted (no resource_limit loop) — it becomes the goal. */
class CcSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new CcSource(6))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    private function boot(int $cc, int $regolith, int $phase = 1, bool $agrardom = true): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, $cc);
        $this->setPhase($bot, $phase);
        $this->setResource($bot, 3, $regolith);
        $this->giveAp($bot, 15);
        if ($agrardom) {
            $this->placeAt($bot, 41, 1);
        }

        return $bot;
    }

    public function test_phase_1_cc_level_2_is_a_positive_candidate(): void
    {
        $candidates = $this->candidates($this->boot(1, 200));

        $this->assertSame(['cc:25:2'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertGreaterThan(0.0, $candidates[0]->net);
        $this->assertSame('invest_cc', $candidates[0]->name);
    }

    public function test_phase_1_cc_level_3_is_no_longer_a_positive_candidate(): void
    {
        $candidates = $this->candidates($this->boot(2, 200));

        foreach ($candidates as $candidate) {
            $this->assertLessThanOrEqual(0.0, $candidate->net, $candidate->key.' must not be urgent in phase 1');
        }
    }

    public function test_cc_level_2_waits_for_the_agrardom(): void
    {
        $this->assertSame([], $this->candidates($this->boot(1, 500, agrardom: false)));
    }

    public function test_phase_1_stops_at_level_3(): void
    {
        $this->assertSame([], $this->candidates($this->boot(3, 500)));
    }

    public function test_unaffordable_level_4_gives_no_candidate(): void
    {
        $this->assertSame([], $this->candidates($this->boot(3, 50, phase: 2)));
    }

    public function test_affordable_level_4_in_phase_2_is_a_candidate(): void
    {
        $candidates = $this->candidates($this->boot(3, 200, phase: 2));

        $this->assertSame(['cc:25:4'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertGreaterThan(0.0, $candidates[0]->net);
    }
}
