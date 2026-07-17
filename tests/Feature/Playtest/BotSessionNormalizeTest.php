<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Throwaway proof that act()/normalize() handle the response shapes the real
 * heuristic will meet, before BotStrategy is built on top of it. Deleted once
 * Schritt 3 (Phase-1-Regeln) exercises the same endpoints for real.
 */
class BotSessionNormalizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_structured_422_with_context_fields_is_captured(): void
    {
        // Base Construction-AP (6, no advisors yet) runs out after 6 invests —
        // the 7th hits the ap_limit gate. Exhausted via the real endpoint, not
        // a DB shortcut, so this doubles as proof the gate is reachable at all.
        $bot = BotSession::boot($this, seed: 1);

        for ($i = 0; $i < 6; $i++) {
            $res = $bot->act('invest_cc', 'POST', '/colony/building/invest', ['building_id' => 25]);
            $this->assertTrue($res['ok'], "invest #{$i} unexpectedly failed: ".json_encode($res['body']));
        }

        $res = $bot->act('invest_cc', 'POST', '/colony/building/invest', ['building_id' => 25]);

        $this->assertFalse($res['ok']);
        $this->assertSame(422, $res['status']);
        $this->assertSame('ap_limit', $res['error']);
        $this->assertSame('construction', $res['body']['ap_type']);
        $this->assertSame('ap_limit', end($bot->log)['error']);
    }

    public function test_raw_exception_message_without_machine_code_is_captured(): void
    {
        // Hangar isn't built at Sol 1 — dispatch to any instance throws a raw
        // RuntimeException message (HangarController wraps it as 'error', no
        // separate 'message' field). Proves normalize() doesn't assume the
        // {error:code, message:text} shape everywhere.
        $bot = BotSession::boot($this, seed: 1);

        $res = $bot->act('dispatch_mission', 'POST', '/colony/hangar/1/dispatch', [
            'mission_key' => 'mission_recon_flight',
        ]);

        $this->assertFalse($res['ok']);
        $this->assertSame(422, $res['status']);
        $this->assertStringContainsString('No ship assigned to hangar instance', $res['error']);
        $this->assertArrayNotHasKey('message', $res['body']);
    }
}
