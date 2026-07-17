<?php

namespace Tests\Feature\Playtest;

use App\Models\Run;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The playtest bot: BotStrategy's full rule set (1-10) plays a whole run
 * through the real HTTP routes, RunReport aggregates a JSON artifact.
 *
 * Assertions here are deliberately weak — the bot is a measurement
 * instrument, not a gate. It is NOT asserted: victory, sols-to-Phase-2,
 * rejection rates. Those are read from the report, not enforced — a bot
 * test that goes red on every balance tweak gets disabled, not read.
 */
class PlaytestBotTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_bot_plays_a_full_run_and_produces_a_report(): void
    {
        $seed = 4242;
        $bot = BotSession::boot($this, $seed);
        $rules = BotStrategy::default();
        $report = new RunReport($seed);

        $this->playSolsUntil($bot, $rules, afterAction: fn (BotSession $b) => $report->snapshot($b));

        $this->assertNotEquals(
            'active',
            $bot->status(),
            'Run must end within tick_limit + 5 sols. Log tail: '.json_encode(array_slice($bot->log, -20))
        );

        $data = $report->build($bot);
        $path = $report->write($data);
        $report->printTable($data);

        $this->assertGreaterThan(0, $data['actions']['ok'], 'Bot must have taken at least one successful action');
        $this->assertFileExists($path);
        $this->assertIsArray(json_decode(file_get_contents($path), true), 'Report artifact must be valid JSON');
    }

    /**
     * Determinism check (plan's "same seed, same run" cross-check): two runs with the
     * identical seed must draw identical objectives — proof that A4
     * (RunProgressService::drawObjectives seeding) actually holds. Objectives
     * are only drawn on the Phase 1 -> 2 transition, so both runs have to be
     * played into Phase 2 before there is anything to compare.
     *
     * KNOWN FAILING (2026-07-17): same root cause as PlaytestBotPhase1Test —
     * with real resource costs enforced, the bot never reaches Phase 2 (no
     * credit income path other than accept_bar_offer, which itself fails).
     * Left red pending that balance ticket.
     */
    public function test_same_seed_draws_identical_objectives(): void
    {
        $rules = BotStrategy::default();

        $bot1 = BotSession::boot($this, seed: 777);
        $this->playSolsUntil($bot1, $rules, fn (BotSession $b) => $this->phaseOf($b) >= 2);
        $taskKeys1 = Run::findOrFail($bot1->runId)->objectives->pluck('task_key')->sort()->values()->all();
        $this->assertNotEmpty($taskKeys1, 'Precondition: run 1 must reach Phase 2 and draw objectives');

        // End run 1 so LobbyController::start()'s "active + pending" query can't
        // pick it up again — boot() re-seeds the same colony/user for run 2.
        DB::table('runs')->where('id', $bot1->runId)->update(['status' => 'completed']);

        $bot2 = BotSession::boot($this, seed: 777);
        $this->playSolsUntil($bot2, $rules, fn (BotSession $b) => $this->phaseOf($b) >= 2);
        $taskKeys2 = Run::findOrFail($bot2->runId)->objectives->pluck('task_key')->sort()->values()->all();

        $this->assertSame($taskKeys1, $taskKeys2);
    }

    private function phaseOf(BotSession $bot): int
    {
        return (int) DB::table('runs')->where('id', $bot->runId)->value('phase');
    }
}
