<?php

namespace Tests\Feature\Playtest;

/**
 * A30/B1c: BotSession::act() must also bracket every action with real
 * Regolith/Organika reads (same pattern as ap_before/ap_after from B1a), so
 * RunReport can attribute resource gains/losses to a source category by rule
 * name — mirrors BotSessionApTrackingTest.
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSessionResourceTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_act_records_regolith_and_organics_before_and_after(): void
    {
        $bot = BotSession::boot($this, 1);

        $bot->act('explore_tile', 'POST', '/colony/tile/explore', ['q' => 999, 'r' => 999]);

        $entry = end($bot->log);
        foreach (['regolith_before', 'regolith_after', 'organics_before', 'organics_after'] as $key) {
            $this->assertArrayHasKey($key, $entry, "Every log entry must carry {$key}");
            $this->assertIsInt($entry[$key]);
        }
    }

    public function test_act_reflects_actual_resource_delta_on_sol_next(): void
    {
        $bot = BotSession::boot($this, 2);
        $before = BotStrategy::organics($bot);

        $bot->nextSol();

        $entry = end($bot->log);
        $this->assertSame('sol_next', $entry['rule']);
        $this->assertSame($before, $entry['organics_before'], 'organics_before must match the real pre-tick stock');
        $this->assertSame(BotStrategy::organics($bot), $entry['organics_after'], 'organics_after must match the real post-tick stock');
    }
}
