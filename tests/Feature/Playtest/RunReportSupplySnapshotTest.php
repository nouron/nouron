<?php

namespace Tests\Feature\Playtest;

use App\Services\ResourcesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * B1d (docs/playtest-instrumentation-plan.md): each Sol snapshot must report
 * supply utilization so the dashboard can flag colonies running over cap.
 * Reuses ResourcesService::getSupplyBreakdown() — the same source GameTick's
 * onboarding "supply_cap_full" trigger and the resource-bar SUP popup read
 * from — rather than re-deriving cap/used with a second formula.
 */
class RunReportSupplySnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_reports_supply_used_cap_and_utilization(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        // Ground truth via the same canonical breakdown the game itself uses
        // for the resource-bar SUP popup (ResourcesService::getSupplyBreakdown()).
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown($bot->colonyId);
        $expectedUsed = $breakdown['used']['buildings'] + $breakdown['used']['researches'] + $breakdown['used']['advisors'];
        $expectedCap = $breakdown['cap'];

        $report->snapshot($bot);
        $built = $report->build($bot);
        $snapshot = $built['sols'][0];

        $this->assertArrayHasKey('supply', $snapshot);
        $this->assertSame($expectedUsed, $snapshot['supply']['used']);
        $this->assertSame($expectedCap, $snapshot['supply']['cap']);

        // utilization is clamped to [0, 1] even in a hypothetical over-cap
        // state (GDD §13.1: supply has no hard upper bound, only a decay
        // penalty past the cap) — the raw over-cap ratio isn't meaningful for
        // the dashboard chart, so 1.0 is reported instead of e.g. 1.4.
        $this->assertGreaterThanOrEqual(0.0, $snapshot['supply']['utilization']);
        $this->assertLessThanOrEqual(1.0, $snapshot['supply']['utilization']);

        if ($expectedCap > 0) {
            $expectedUtilization = min(1.0, max(0.0, $expectedUsed / $expectedCap));
            $this->assertEqualsWithDelta($expectedUtilization, $snapshot['supply']['utilization'], 0.0001);
        } else {
            $this->assertSame(0.0, $snapshot['supply']['utilization']);
        }
    }

    public function test_snapshot_utilization_matches_hand_computed_expectation(): void
    {
        $bot = BotSession::boot($this, seed: 1);
        $report = new RunReport(seed: 1);

        // Pin the cap to a known value (bypassing GameTick's own calculation,
        // since this test only cares that RunReport reads user_resources.supply
        // and colony_buildings correctly, not that GameTick's formula is right
        // — that's covered by GameTickSupplyCapTest).
        $userId = $bot->userId;
        DB::table('user_resources')
            ->where('user_id', $userId)
            ->update(['supply' => 100]);

        $breakdown = app(ResourcesService::class)->getSupplyBreakdown($bot->colonyId);
        $used = $breakdown['used']['buildings'] + $breakdown['used']['researches'] + $breakdown['used']['advisors'];

        $report->snapshot($bot);
        $snapshot = $report->build($bot)['sols'][0];

        $this->assertSame($used, $snapshot['supply']['used']);
        $this->assertSame(100, $snapshot['supply']['cap']);
        $this->assertEqualsWithDelta($used / 100, $snapshot['supply']['utilization'], 0.0001);
    }
}
