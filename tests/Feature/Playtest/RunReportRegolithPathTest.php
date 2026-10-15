<?php

namespace Tests\Feature\Playtest;

/**
 * A31/B2b: RunReport::build() must aggregate regolith_sources across every
 * Sol snapshot into 'regolith_path_attribution', mapped per the real GDD
 * (not the swapped letters in the old plan text):
 *   pfad_a_geologie  <- 'harvester' + 'geology' buckets (Analytik-Labor/Geologie-Kenntnis)
 *   pfad_b_frachter  <- 'mission' bucket (Hangar-Frachter-Mission)
 *   pfad_c_cantina   <- 'trade' bucket (Cantina/Corvan)
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunReportRegolithPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_regolith_path_attribution_sums_sources_across_all_sols(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        // Sol 0: trade (cantina) gain.
        $bot->regolithLedger[0]['in'] = ['cantina_guest' => 15];
        $report->snapshot($bot);

        // Sol 2: mission + harvester + geology; import and recycling belong to no path.
        $bot->sol = 2;
        $bot->regolithLedger[2]['in'] = ['mission_ore' => 5, 'harvester_base' => 15, 'geology' => 3, 'import' => 9, 'recycle' => 2];
        $report->snapshot($bot);

        // Sol 3: another trade gain, so the run-level sum must add across sols.
        $bot->sol = 3;
        $bot->regolithLedger[3]['in'] = ['cantina_guest' => 5];
        $report->snapshot($bot);

        $data = $report->build($bot);

        $this->assertSame([
            'pfad_a_geologie' => 18,
            'pfad_b_frachter' => 5,
            'pfad_c_cantina' => 20,
        ], $data['regolith_path_attribution']);
    }
}
