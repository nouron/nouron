<?php

namespace Tests\Feature\Playtest;

/**
 * A30/B1c: RunReport::snapshot() must attribute this Sol's Regolith/Organika
 * gains to a source category. Regolith comes from the tagged ledger bookings
 * (App\Support\RegolithLedger, drained per request into BotSession::$regolithLedger);
 * Organika still from the regolith/organics_before/after each log entry carries
 * (BotSessionResourceTrackingTest) plus the real colony_log
 * 'hangar.mission_completed' event for mission rewards and
 * ResourcesService::foodNeed() for hunger consumption.
 *
 * Categories (structurally "wie oben" per the plan, but correctly renamed
 * per resource — 'harvester' would be a misnomer for Organika, which the
 * Agrardom produces):
 *   regolith_sources: harvester | mission | trade | find | find_flight | event |
 *                     geology | import | recycling | concern | other
 *   organics_sources: agrardom  | mission | trade | event
 *   organics_consumption: hunger_consumed | mission_dispatch_consumed
 */
use App\Support\RegolithLedger;
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

    public function test_snapshot_reports_the_hunger_streak(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);
        DB::table('glx_colonies')->where('id', $bot->colonyId)->update(['hunger_streak' => 3]);

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(3, $data['sols'][0]['hunger_streak']);
    }

    public function test_trade_source_comes_from_cantina_guest_and_merchant_bookings(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        $bot->regolithLedger[$bot->sol]['in'] = ['cantina_guest' => 10, 'merchant_buy' => 5];

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(15, $data['sols'][0]['regolith_sources']['trade']);
        $this->assertSame(0, $data['sols'][0]['regolith_sources']['harvester']);
    }

    public function test_every_ledger_source_tag_lands_in_its_own_bucket(): void
    {
        $bot = BotSession::boot($this, 2);
        $report = new RunReport(2);

        $bot->regolithLedger[$bot->sol]['in'] = ['harvester_base' => 15, 'geology' => 4, 'mission_ore' => 3, 'mission_loot' => 2,
            'import' => 6, 'recycle' => 1, 'cantina_concern' => 7, 'find_self' => 8, 'find_flight' => 9, 'brand_new_tag' => 2];

        $report->snapshot($bot);
        $sources = $report->build($bot)['sols'][0]['regolith_sources'];

        $this->assertSame(['harvester' => 15, 'mission' => 5, 'trade' => 0, 'find' => 8, 'find_flight' => 9, 'event' => 0,
            'geology' => 4, 'import' => 6, 'recycling' => 1, 'concern' => 7, 'other' => 2], $sources);
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

    public function test_outflows_never_count_as_a_source(): void
    {
        $bot = BotSession::boot($this, 4);
        $report = new RunReport(4);

        $bot->regolithLedger[$bot->sol]['out'] = ['build_place' => 30, 'cantina_sell' => 10];

        $report->snapshot($bot);
        $data = $report->build($bot);

        $this->assertSame(0, array_sum($data['sols'][0]['regolith_sources']));
    }

    public function test_ledger_reports_flows_stock_and_the_unexplained_rest_per_sol(): void
    {
        $bot = BotSession::boot($this, 5);
        $report = new RunReport(5);
        $start = $bot->regolithStartStock;

        // Sol 0: +20 harvester, −12 placement, stock really rose by 8 → fully explained.
        $bot->regolithLedger[0] = ['in' => ['harvester_base' => 20], 'out' => ['build_place' => 12]];
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => $start + 8]);
        $report->snapshot($bot);

        // Sol 1: +5 import booked, but the stock rose by 9 → 4 Rg came from an untagged site.
        $bot->sol = 1;
        $bot->regolithLedger[1] = ['in' => ['import' => 5], 'out' => []];
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)->update(['amount' => $start + 17]);
        $report->snapshot($bot);

        $ledger = $report->build($bot)['regolith_ledger'];

        $this->assertSame($start, $ledger['start_stock']);
        $this->assertSame(['harvester_base' => 20, 'import' => 5], $ledger['sources']);
        $this->assertSame(['build_place' => 12], $ledger['sinks']);
        $this->assertSame([0, $start + 8, 0], [$ledger['per_sol'][0]['sol'], $ledger['per_sol'][0]['stock'], $ledger['per_sol'][0]['unattributed']]);
        $this->assertSame([1, $start + 17, 4], [$ledger['per_sol'][1]['sol'], $ledger['per_sol'][1]['stock'], $ledger['per_sol'][1]['unattributed']]);
    }

    public function test_bot_request_bookings_land_in_the_current_sol(): void
    {
        $bot = BotSession::boot($this, 6);
        $bot->sol = 3;

        RegolithLedger::record($bot->colonyId, 'import', 7);
        RegolithLedger::record($bot->colonyId, 'repair', -2);
        $bot->bookRegolith();

        $this->assertSame(['in' => ['import' => 7], 'out' => ['repair' => 2]], $bot->regolithLedger[3]);
    }
}
