<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A37-Rest continuation (2026-09-15): compounds/Werkstoffe balance was never
 * tracked per Sol, only regolith/organics/credits. Needed to check whether
 * the trust buildings (infirmary/monument/temple/securityHub) still never
 * get built because too little compounds accumulates, or because something
 * else keeps outranking them in placeCandidate() priority.
 */
class RunReportCompoundsSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private const RES_COMPOUNDS = 4;

    public function test_snapshot_reports_compounds_balance(): void
    {
        $bot = BotSession::boot($this, seed: 1);

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => self::RES_COMPOUNDS],
            ['amount' => 42]
        );

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);
        $sol = $report->build($bot)['sols'][0];

        $this->assertSame(42, $sol['compounds']);
    }
}
