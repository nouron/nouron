<?php

namespace Tests\Feature\Playtest;

use App\Services\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CI failure 2026-10-09 (test_same_seed_draws_identical_objectives, seed 777):
 * the second BotSession::boot() in one test only switched the authenticated user
 * (actingAs), but the session still carried the first bot's
 * 'activeIds.colonyId' — so every controller resolving the colony from the
 * session (ResolvesActiveColony) acted on bot 1's colony. Bot 2's research
 * orders were "successful" no-ops on bot 1's CC-gated knowledge, AP/state of
 * bot 2 never changed, and the rule looped until MAX_ACTIONS_PER_SOL.
 */
class BotSessionColonyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_second_boot_in_the_same_test_acts_on_its_own_colony(): void
    {
        $bot1 = BotSession::boot($this, seed: 1);
        // Touch a session-resolving endpoint so bot 1's colony is cached in the session.
        $bot1->act('probe', 'POST', '/techtree/research/90/order', ['order' => 'add', 'ap' => 1]);

        $bot2 = BotSession::boot($this, seed: 2);
        $this->assertNotSame($bot1->colonyId, $bot2->colonyId);

        $res = $bot2->act('probe', 'POST', '/techtree/research/91/order', ['order' => 'add', 'ap' => 1]);

        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertSame(
            1,
            (int) DB::table('colony_researches')->where('colony_id', $bot2->colonyId)->where('research_id', 91)->value('ap_spend'),
            'bot 2 must invest into its own colony'
        );
        $this->assertNull(
            DB::table('colony_researches')->where('colony_id', $bot1->colonyId)->where('research_id', 91)->value('ap_spend'),
            'bot 1 colony must stay untouched by bot 2'
        );
        $this->assertSame(
            app(AdvisorService::class)->getAvailableActionPoints($bot2->colonyId),
            $res['body']['ap_available']
        );
    }
}
