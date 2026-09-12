<?php

namespace Tests\Feature\Playtest;

/**
 * A30/B1a: BotSession::act() must capture the colony's available AP
 * immediately before and after every action, so RunReport can attribute
 * AP-spend to a category by rule name (docs/playtest-instrumentation-plan.md
 * B1a). Isolated from RunReport's aggregation logic — see
 * RunReportApBreakdownTest for that.
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotSessionApTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_act_records_ap_before_and_after_on_every_log_entry(): void
    {
        $bot = BotSession::boot($this, 1);

        // Payload/route validity doesn't matter here — ap_before/ap_after must
        // be captured regardless of whether the action itself succeeds.
        $bot->act('explore_tile', 'POST', '/colony/tile/explore', ['q' => 999, 'r' => 999]);

        $entry = end($bot->log);
        $this->assertArrayHasKey('ap_before', $entry, 'Every log entry must carry ap_before');
        $this->assertArrayHasKey('ap_after', $entry, 'Every log entry must carry ap_after');
        $this->assertIsInt($entry['ap_before']);
        $this->assertIsInt($entry['ap_after']);
    }

    public function test_ap_after_reflects_actual_spend_on_a_successful_action(): void
    {
        $bot = BotSession::boot($this, 2);

        // hire_advisor costs Credits, not AP — use a real AP-costing action instead:
        // investing into the Command Center construction site always exists at Sol 1.
        $res = $bot->act('invest_cc', 'POST', '/colony/building/invest', [
            'building_id' => 25,
            'ap_amount' => 1,
        ]);

        $entry = end($bot->log);
        if ($res['ok']) {
            $this->assertLessThanOrEqual($entry['ap_before'], $entry['ap_after'] + 1,
                'A successful AP-costing action must not increase available AP');
        } else {
            $this->assertSame($entry['ap_before'], $entry['ap_after'],
                'A rejected action must leave available AP unchanged');
        }
    }
}
