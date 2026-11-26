<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Spec 2026-11-25 §3.2/§7: "Expertenstab" = sum of all advisor ranks. Hiring early
 * and never replacing advisors is already the default behaviour (hire_advisor is
 * the second rule, the bot dismisses nobody). New: while the objective is drawn and
 * open, an objective-focused profile keeps the next promotion's Credits back from
 * merchant buys, so the promotion is never delayed by shopping.
 */
class BotStrategySeniorAdvisorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_focus_profile_keeps_the_next_promotion_back_from_merchant_buys(): void
    {
        $bot = $this->boot(withObjective: true);
        $this->insertAdvisor($bot, rank: 1);
        $promotion = (int) config('game.advisor.promotion_costs')[2];
        $this->insertMerchantItem($bot, 100);

        $this->setCredits($bot, 100 + $promotion - 1);
        $this->assertNull($this->rule('focus', 'buy_merchant_item')['when']($bot));

        $this->setCredits($bot, 100 + $promotion);
        $this->assertNotNull($this->rule('focus', 'buy_merchant_item')['when']($bot));
    }

    public function test_the_reserve_follows_the_most_expensive_pending_promotion(): void
    {
        $bot = $this->boot(withObjective: true);
        $this->insertAdvisor($bot, rank: 2, personellId: 35);
        $this->insertAdvisor($bot, rank: 3, personellId: 36);
        $this->insertMerchantItem($bot, 100);

        $this->setCredits($bot, 100 + (int) config('game.advisor.promotion_costs')[3] - 1);

        $this->assertNull($this->rule('focus', 'buy_merchant_item')['when']($bot), 'rank 2 -> 3 is the pending promotion');
    }

    public function test_default_profile_and_closed_objective_keep_no_reserve(): void
    {
        $bot = $this->boot(withObjective: true);
        $this->insertAdvisor($bot, rank: 1);
        $this->insertMerchantItem($bot, 100);
        $this->setCredits($bot, 100);

        $this->assertNotNull($this->rule('default', 'buy_merchant_item')['when']($bot), 'default ignores objectives');

        DB::table('run_objectives')->where('run_id', $bot->runId)->update(['completed_at' => 40]);
        $this->assertNotNull($this->rule('focus', 'buy_merchant_item')['when']($bot), 'completed objective');
    }

    public function test_promotion_itself_is_not_held_back_by_its_own_reserve(): void
    {
        $bot = $this->boot(withObjective: true);
        $this->insertAdvisor($bot, rank: 1, activeTicks: 1000);
        $this->setCredits($bot, (int) config('game.advisor.promotion_costs')[2]);

        $this->assertNotNull($this->rule('focus', 'promote_advisor')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function boot(bool $withObjective): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        DB::table('advisors')->where('colony_id', $bot->colonyId)->delete();
        if ($withObjective) {
            DB::table('run_objectives')->insert([
                'run_id' => $bot->runId,
                'task_key' => 'task_senior_advisors',
                'target_value' => (int) config('game.run.tasks.task_senior_advisors.target'),
                'current_value' => 0,
                'streak_value' => 0,
                'completed_at' => null,
            ]);
        }

        return $bot;
    }

    private function setCredits(BotSession $bot, int $credits): void
    {
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits]);
    }

    private function insertAdvisor(BotSession $bot, int $rank, int $personellId = 35, int $activeTicks = 0): void
    {
        DB::table('advisors')->insert([
            'user_id' => $bot->userId,
            'colony_id' => $bot->colonyId,
            'personell_id' => $personellId,
            'rank' => $rank,
            'active_ticks' => $activeTicks,
        ]);
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
