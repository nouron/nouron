<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\TickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Baseline 2026-09-26 (T24): barOfferCandidate() never checked whether the bot
 * could pay the give side, so accept_bar_offer collected
 * bar_offer_insufficient_resources 40x in one batch (BarService::acceptOffer()
 * checks the give-side balance: Credits user-level, everything else colony-level).
 */
class BotStrategyBarOfferTest extends TestCase
{
    use RefreshDatabase;

    private const RES_CREDITS = 1;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    public function test_unaffordable_offer_is_skipped(): void
    {
        $bot = $this->bootWithBar();
        $this->setColonyResource($bot, self::RES_ORGANICS, 5);
        $this->offer($bot, giveResource: self::RES_ORGANICS, giveAmount: 20, getResource: self::RES_CREDITS);

        $this->assertEmpty($this->rule('accept_bar_offer')['when']($bot));
    }

    public function test_affordable_offer_is_taken_even_behind_an_unaffordable_one(): void
    {
        $bot = $this->bootWithBar();
        $this->setColonyResource($bot, self::RES_ORGANICS, 5);
        $this->offer($bot, giveResource: self::RES_ORGANICS, giveAmount: 20, getResource: self::RES_CREDITS);
        $affordable = $this->offer($bot, giveResource: self::RES_REGOLITH, giveAmount: 10, getResource: self::RES_ORGANICS);

        $candidate = $this->rule('accept_bar_offer')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame($affordable, (int) $candidate->id);
    }

    public function test_credits_give_side_is_checked_against_user_credits(): void
    {
        $bot = $this->bootWithBar();
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => 10]);
        $this->offer($bot, giveResource: self::RES_CREDITS, giveAmount: 50, getResource: self::RES_REGOLITH);

        $this->assertEmpty($this->rule('accept_bar_offer')['when']($bot));
    }

    /** T60: only figures sitting in the cantina today can be traded with (the API answers not_present otherwise). */
    public function test_only_offers_of_figures_in_todays_cast_are_taken(): void
    {
        $bot = $this->bootWithBar();
        config(['game.bar.guests_per_sol' => ['min' => 1, 'max' => 1]]);
        $first = $this->offer($bot, giveResource: self::RES_REGOLITH, giveAmount: 10, getResource: self::RES_ORGANICS);
        $second = $this->offer($bot, giveResource: self::RES_REGOLITH, giveAmount: 10, getResource: self::RES_ORGANICS);
        // Today's cast holds only the second guest (stored like CantinaPresenceService::cast() does).
        DB::table('cantina_casts')->insert([
            'colony_id' => $bot->colonyId, 'tick' => app(TickService::class)->getTickCount(),
            'size' => 1, 'figures' => json_encode(["offer_{$second}"]),
        ]);

        $candidate = $this->rule('accept_bar_offer')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame($second, (int) $candidate->id);
        $this->assertNotSame($first, (int) $candidate->id);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithBar(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        // T60 caps the cantina to a few figures per Sol; these tests are about the offer choice.
        config(['game.bar.guests_per_sol' => ['min' => 99, 'max' => 99]]);
        DB::table('bar_offers')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Bar->value,
            'instance_id' => 1,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
        $this->setColonyResource($bot, self::RES_REGOLITH, 200);

        return $bot;
    }

    private function setColonyResource(BotSession $bot, int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => $resourceId],
            ['amount' => $amount],
        );
    }

    private function offer(BotSession $bot, int $giveResource, int $giveAmount, int $getResource): int
    {
        return (int) DB::table('bar_offers')->insertGetId([
            'colony_id' => $bot->colonyId,
            'give_resource_id' => $giveResource,
            'give_amount' => $giveAmount,
            'get_resource_id' => $getResource,
            'get_amount' => 10,
            'expires_tick' => app(TickService::class)->getTickCount() + 5,
            'is_accepted' => false,
        ]);
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
