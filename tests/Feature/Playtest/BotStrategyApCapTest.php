<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\TickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\Utility\BotContext;
use Tests\Feature\Playtest\Utility\Sources\CcSource;
use Tests\Feature\Playtest\Utility\Sources\UpgradeSource;
use Tests\Feature\Playtest\Utility\UtilityFixtures;
use Tests\TestCase;

/**
 * The per-building per-Sol AP cap (game.build.ap_cap_per_sol) must not cost the bot
 * rejections: a building whose cap is used up for this Sol is simply not offered,
 * so the AP goes to the next building or to another rule. Cap off: unchanged.
 */
class BotStrategyApCapTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private const BIO = 41;

    private function setCap(?int $base): void
    {
        config(['game.build.ap_cap_per_sol' => ['base' => $base, 'engineer_bonus_per_rank' => [1 => 2, 2 => 3, 3 => 4]]]);
    }

    /** Book the whole cap of a building row as spent in the current Sol. */
    private function exhaust(BotSession $bot, int $buildingId, int $instanceId = 1): void
    {
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', $buildingId)->where('instance_id', $instanceId)
            ->update(['ap_cap_tick' => app(TickService::class)->getTickCount(), 'ap_cap_spent' => 99]);
    }

    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }
        $this->fail("Rule {$name} not found");
    }

    private function boot(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 3, 1000);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);
        $this->setCc($bot, 2);
        $this->placeAt($bot, self::BIO, 1);

        return $bot;
    }

    public function test_invest_cc_is_not_offered_once_the_cc_cap_is_used_up(): void
    {
        $bot = $this->boot();
        $this->setCap(3);
        $this->assertNotEmpty($this->rule('invest_cc')['when']($bot));

        $this->exhaust($bot, BuildingId::CommandCenter->value);

        $this->assertEmpty($this->rule('invest_cc')['when']($bot));
    }

    public function test_cap_off_leaves_invest_cc_untouched_even_with_stale_counters(): void
    {
        $bot = $this->boot();
        $this->setCap(null);
        $this->exhaust($bot, BuildingId::CommandCenter->value);

        $this->assertNotEmpty($this->rule('invest_cc')['when']($bot));
    }

    public function test_feed_colony_skips_a_capped_agrardom(): void
    {
        $bot = $this->boot();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', self::BIO)->update(['level' => 0]);
        $this->setCap(3);
        $this->assertNotEmpty($this->rule('feed_colony')['when']($bot));

        $this->exhaust($bot, self::BIO);

        $this->assertEmpty($this->rule('feed_colony')['when']($bot));
    }

    public function test_invest_production_spreads_to_the_next_building_when_one_is_capped(): void
    {
        $bot = $this->boot();
        $this->placeAt($bot, BuildingId::Housing->value, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::Housing->value)->update(['level' => 3]);
        $this->setCap(3);
        $first = $this->rule('invest_production')['when']($bot);
        $this->assertNotEmpty($first);

        $this->exhaust($bot, (int) $first->building_id, (int) $first->instance_id);
        $second = $this->rule('invest_production')['when']($bot);

        $this->assertTrue(empty($second) || (int) $second->building_id !== (int) $first->building_id);
    }

    public function test_utility_sources_do_not_offer_capped_buildings(): void
    {
        $bot = $this->boot();
        $this->setPhase($bot, 2);
        $this->setCap(3);
        $ctx = fn () => BotContext::capture($bot, BotProfile::named('default'), withGoal: true);
        $this->assertNotEmpty((new CcSource(6))->candidates($bot, $ctx()));
        $this->assertContains('invest:'.self::BIO.':1', array_map(fn ($c) => $c->key, (new UpgradeSource(17))->candidates($bot, $ctx())));

        $this->exhaust($bot, BuildingId::CommandCenter->value);
        $this->exhaust($bot, self::BIO);

        $this->assertEmpty((new CcSource(6))->candidates($bot, $ctx()));
        $this->assertNotContains('invest:'.self::BIO.':1', array_map(fn ($c) => $c->key, (new UpgradeSource(17))->candidates($bot, $ctx())));
    }
}
