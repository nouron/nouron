<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A37 investigation prep: no playtest report ever captured Kenntnis
 * (knowledge) progression, only building levels (B1b). Needed to check
 * whether the "no run ever wins" finding (A37) is partly explained by the
 * same category of gap that caused A34/A38 (advisor promotion, map seeding)
 * — i.e. whether research_knowledge actually reaches meaningful levels
 * during a run, or silently stalls.
 */
class RunReportResearchSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_captures_research_levels(): void
    {
        $bot = BotSession::boot($this, seed: 1);

        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => 90],
            ['level' => 3, 'ap_spend' => 7]
        );

        $report = new RunReport(seed: 1);
        $report->snapshot($bot);
        $sol = $report->build($bot)['sols'][0];

        $this->assertArrayHasKey('researches', $sol);
        $this->assertSame(3, $sol['researches'][90]['level']);
        $this->assertSame(7, $sol['researches'][90]['ap_spend']);
    }
}
