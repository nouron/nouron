<?php

namespace Tests\Feature\Playtest;

/**
 * A31/B2b: RunReport::build() must aggregate regolith_sources across every
 * Sol snapshot into 'regolith_path_attribution', mapped per the real GDD
 * (not the swapped letters in the old plan text):
 *   pfad_a_geologie  <- 'harvester' bucket (Analytik-Labor/Geologie-Kenntnis)
 *   pfad_b_frachter  <- 'mission' bucket (Hangar-Frachter-Mission)
 *   pfad_c_cantina   <- 'trade' bucket (Cantina/Corvan)
 */
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RunReportRegolithPathTest extends TestCase
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

    public function test_regolith_path_attribution_sums_sources_across_all_sols(): void
    {
        $bot = BotSession::boot($this, 1);
        $report = new RunReport(1);

        // Sol 1: trade (cantina) gain.
        $this->pushEntry($bot, 'accept_bar_offer', ['regolith_before' => 100, 'regolith_after' => 115]);
        $report->snapshot($bot);

        // Sol 2: mission + harvester gain via sol_next, split by the real
        // hangar.mission_completed event (tick == bot->sol).
        $bot->sol = 2;
        DB::table('colony_log')->insert([
            'user' => $bot->userId,
            'tick' => 2,
            'event' => 'hangar.mission_completed',
            'area' => 'colony',
            'parameters' => json_encode(['rewards' => ['regolith' => 5]]),
            'is_read' => true,
            'created_at' => now(),
        ]);
        $this->pushEntry($bot, 'sol_next', ['regolith_before' => 115, 'regolith_after' => 135]);
        $report->snapshot($bot);

        // Sol 3: another trade gain, so the run-level sum must add across sols.
        $bot->sol = 3;
        $this->pushEntry($bot, 'accept_bar_offer', ['regolith_before' => 135, 'regolith_after' => 140]);
        $report->snapshot($bot);

        $data = $report->build($bot);

        // Sol 1: trade 15. Sol 2: mission 5, harvester 15. Sol 3: trade 5.
        $this->assertSame([
            'pfad_a_geologie' => 15,
            'pfad_b_frachter' => 5,
            'pfad_c_cantina' => 20,
        ], $data['regolith_path_attribution']);
    }
}
