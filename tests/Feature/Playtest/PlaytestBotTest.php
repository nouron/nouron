<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Step 1 (skeleton): prove the plumbing before building the decision matrix.
 * Loop calls only POST /sol/next until the run ends — no strategy yet, so the
 * run is expected to fail on time_limit once the sol cap is hit.
 */
class PlaytestBotTest extends TestCase
{
    use RefreshDatabase;

    public function test_bot_advances_sols_until_run_ends_via_sol_next_only(): void
    {
        $bot = BotSession::boot($this, seed: 4242);

        $maxSols = config('game.run.tick_limit') + 5;

        while ($bot->isActive() && $bot->sol < $maxSols) {
            $bot->nextSol();
        }

        $this->assertNotEquals('active', $bot->status(), 'Run must end within tick_limit + 5 sols');
        $this->assertEquals('failed', $bot->status(), 'With no strategy, the bot only ticks time — expected outcome is time_limit failure');
        $this->assertGreaterThan(0, $bot->sol, 'Bot must have advanced at least one Sol');
    }
}
