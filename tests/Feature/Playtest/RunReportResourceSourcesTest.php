<?php

namespace Tests\Feature\Playtest;

/**
 * A30/B1c: RunReport::snapshot() must attribute this Sol's Regolith/Organika
 * gains to a source category, using the regolith/organics_before/after each
 * log entry carries (BotSessionResourceTrackingTest) plus the real
 * colony_log 'hangar.mission_completed' event for mission rewards and
 * ResourcesService::foodNeed() for hunger consumption — no new game-code
 * tagging, no re-derivation of production amounts (those come straight from
 * the observed before/after delta).
 *
 * Categories (structurally "wie oben" per the plan, but correctly renamed
 * per resource — 'harvester' would be a misnomer for Organika, which the
 * Agrardom produces):
 *   regolith_sources: harvester | mission | trade | event
 *   organics_sources: agrardom  | mission | trade | event
 *   organics_consumption: hunger_consumed | mission_dispatch_consumed
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunReportResourceSourcesTest extends TestCase
{
    use RefreshDatabase;

    private function pushEntry(BotSession $bot, string $rule, array $overrides = [], bool $ok = true): void
    {
        $bot->log[] = array_merge([
            'sol' => $bot->sol,
            'rule' => $rule,
            'url' => '',
            'status' => $ok ? 200 : 422,
            'ok' => $ok,
            'error' => $ok ? null : 'rejected',
            'ap_before' => 10,
            'ap_after' => 10,
            'regolith_before' => 0,
            'regolith_after' => 0,
            'organics_before' => 0,
            'organics_after' => 0,
        ], $overrides);
    }

    public function test_trade_source_counted_from_accept_bar_offer_delta(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        $this->pushEntry($bot, 'accept_bar_offer', ['regolith_before' => 100, 'regolith_after' => 115]);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(15, $data['sols'][0]['regolith_sources']['trade']);
        $this->assertSame(0, $data['sols'][0]['regolith_sources']['harvester']);
    }

    public function test_harvester_source_is_sol_next_delta_minus_mission_rewards(): void
    {
        $bot = BotSession::boot($this, 2);
        $report = new RunReport(2);

        // Real mission-completion event this tick (tick == bot->sol after nextSol()
        // increments it — see BotSession::nextSol(), $this->sol++ happens first).
        $bot->sol = 0; // about to become 1 via nextSol()
        DB::table('colony_log')->insert([
            'user' => $bot->userId,
            'tick' => 1,
            'event' => 'hangar.mission_completed',
            'area' => 'colony',
            'parameters' => json_encode(['rewards' => ['regolith' => 5]]),
            'is_read' => true,
            'created_at' => now(),
        ]);

        // Synthetic sol_next entry: total regolith delta 20, of which 5 came from
        // the mission above → harvester's own share must be 15.
        $bot->sol = 1;
        $this->pushEntry($bot, 'sol_next', ['regolith_before' => 100, 'regolith_after' => 120]);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(15, $data['sols'][0]['regolith_sources']['harvester']);
        $this->assertSame(5, $data['sols'][0]['regolith_sources']['mission']);
    }

    public function test_mission_dispatch_consumption_tracked_separately_from_hunger(): void
    {
        $bot = BotSession::boot($this, 3);
        $report = new RunReport(3);

        // Dispatching a ship pays Organika provisions immediately (HangarService::
        // dispatch() -> organikaCostFor()) — must land in mission_dispatch_consumed,
        // not be confused with the tick-driven hunger consumption.
        $this->pushEntry($bot, 'dispatch_mission', ['organics_before' => 50, 'organics_after' => 44]);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(6, $data['sols'][0]['organics_consumption']['mission_dispatch_consumed']);
        $this->assertSame(0, $data['sols'][0]['organics_consumption']['hunger_consumed']);
    }

    public function test_rejected_action_never_counts_as_a_source(): void
    {
        $bot = BotSession::boot($this, 4);
        $report = new RunReport(4);

        // A rejected accept_bar_offer must leave the DB untouched (delta 0 here,
        // simulating that reality), but even a non-zero delta must not count —
        // ok=false actions are never a real source.
        $this->pushEntry($bot, 'accept_bar_offer', ['regolith_before' => 100, 'regolith_after' => 100], ok: false);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(0, $data['sols'][0]['regolith_sources']['trade']);
    }
}
