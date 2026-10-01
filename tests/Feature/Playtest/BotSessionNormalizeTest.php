<?php

namespace Tests\Feature\Playtest;

use App\Services\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Throwaway proof that act()/normalize() handle the response shapes the real
 * heuristic will meet, before BotStrategy is built on top of it. Kept as
 * regression coverage even after BotStrategy's Phase-1 rules started
 * exercising the same endpoints for real (PlaytestBotPhase1Test) — it still
 * covers a response shape (raw exception message, no machine code) that the
 * real rule set doesn't hit on the current happy path.
 */
class BotSessionNormalizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_structured_422_with_context_fields_is_captured(): void
    {
        // Base AP (ap.base=12, single shared pool, no advisors yet) runs out
        // after that many invests — the next one hits the ap_limit gate.
        // Exhausted via the real endpoint, not a DB shortcut, so this doubles
        // as proof the gate is reachable at all. Read from the service rather
        // than hardcoded so this doesn't drift from config('game.ap.base').
        $bot = BotSession::boot($this, seed: 1);

        // Agrardom gate (ROADMAP C16): CC Lv1 -> Lv2 requires an Agrardom already
        // placed. Pre-place it so this loop exercises the AP-limit gate in
        // isolation, not the (already covered elsewhere) Agrardom gate.
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'building_id' => 41, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 2, 'tile_y' => 0]
        );

        $available = app(AdvisorService::class)->getAvailableActionPoints($bot->colonyId);

        for ($i = 0; $i < $available; $i++) {
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

    public function test_hangar_rule_error_is_captured_as_machine_code(): void
    {
        // Hangar isn't built at Sol 1 — dispatch to any instance fails. Since
        // T25 (2026-10-01) the hangar answers with the {error: code, message:
        // text} contract instead of the raw exception text it used to send.
        $bot = BotSession::boot($this, seed: 1);

        $res = $bot->act('dispatch_mission', 'POST', '/colony/hangar/1/dispatch', [
            'mission_key' => 'mission_recon_flight',
            'difficulty' => 'easy',
        ]);

        $this->assertFalse($res['ok']);
        $this->assertSame(422, $res['status']);
        $this->assertSame('no_ship_in_hangar', $res['error']);
        $this->assertSame('no_ship_in_hangar', end($bot->log)['error']);
        $this->assertSame(__('colony.hangar_error_no_ship_in_hangar'), $res['body']['message']);
    }
}
