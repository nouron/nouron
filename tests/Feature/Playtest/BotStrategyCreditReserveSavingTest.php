<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Batch 2026-09-30 (missions/events rules): Credits sank to ~100 by Sol 50 in
 * every profile — promotions, ship requests and merchant buys took whatever came
 * in, so task_credit_reserve never had a chance. Owner: every profile saves while
 * that objective is drawn and open. Credit spending may only use the surplus
 * above the threshold (thrifty/focus keep their larger 1.5x buffer).
 */
class BotStrategyCreditReserveSavingTest extends TestCase
{
    use RefreshDatabase;

    private int $threshold;

    protected function setUp(): void
    {
        parent::setUp();
        $this->threshold = (int) config('game.run.tasks.task_credit_reserve.threshold');
    }

    public function test_default_profile_skips_a_merchant_buy_that_would_dip_below_the_threshold(): void
    {
        $bot = $this->boot(credits: $this->threshold + 100);
        $this->insertMerchantItem($bot, cost: 200);

        $this->assertNull($this->rule('default', 'buy_merchant_item')['when']($bot));
    }

    public function test_default_profile_buys_from_the_surplus_above_the_threshold(): void
    {
        $bot = $this->boot(credits: $this->threshold + 300);
        $this->insertMerchantItem($bot, cost: 200);

        $this->assertNotNull($this->rule('default', 'buy_merchant_item')['when']($bot));
    }

    public function test_default_profile_skips_a_promotion_that_would_dip_below_the_threshold(): void
    {
        $bot = $this->boot(credits: $this->threshold + 100);
        $this->insertPromotableAdvisor($bot);

        $this->assertNull($this->rule('default', 'promote_advisor')['when']($bot));
    }

    public function test_thrifty_profile_keeps_the_larger_buffer(): void
    {
        $bot = $this->boot(credits: $this->threshold + 300);
        $this->insertMerchantItem($bot, cost: 200);

        $this->assertNull($this->rule('thrifty', 'buy_merchant_item')['when']($bot));
    }

    public function test_spending_is_normal_without_the_objective(): void
    {
        $bot = $this->boot(credits: $this->threshold + 100);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        $this->insertMerchantItem($bot, cost: 200);
        $this->insertPromotableAdvisor($bot);

        $this->assertNotNull($this->rule('default', 'buy_merchant_item')['when']($bot));
        $this->assertNotNull($this->rule('default', 'promote_advisor')['when']($bot));
    }

    public function test_default_profile_holds_ship_requests_below_the_threshold(): void
    {
        $bot = $this->boot(credits: $this->threshold - 1);

        $this->assertNull($this->rule('default', 'request_ship')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function boot(int $credits): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => 'task_credit_reserve',
            'target_value' => (int) config('game.run.tasks.task_credit_reserve.target'),
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits]);

        return $bot;
    }

    private function insertMerchantItem(BotSession $bot, int $cost): void
    {
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => $bot->colonyId,
            'tick_start' => 0,
            'tick_end' => 2000000000,
            'was_visited' => false,
        ]);
        DB::table('merchant_items')->insert([
            'visit_id' => $visitId,
            'item_type' => 'resource',
            'label' => 'Organika',
            'cost_credits' => $cost,
            'payload' => json_encode(['resource_id' => 5, 'amount' => 10]),
            'sold' => false,
        ]);
    }

    private function insertPromotableAdvisor(BotSession $bot): void
    {
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        DB::table('advisors')->insert([
            'user_id' => $bot->userId,
            'colony_id' => $bot->colonyId,
            'personell_id' => 35,
            'rank' => 1,
            'active_ticks' => 1000,
        ]);
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $profile, string $name): array
    {
        foreach (BotStrategy::default(BotProfile::named($profile)) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
