<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\TestCase;

/** T30 §14 U5: buy the freighter for a waiting large find even with fewer than 3 advisors. */
class FreighterSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function bootHangar2(int $credits): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        $this->setCredits($bot, $credits);
        $this->giveAp($bot, 10);

        return $bot;
    }

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new FreighterSource(25))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    public function test_a_waiting_large_find_makes_the_freighter_a_candidate(): void
    {
        $bot = $this->bootHangar2(5000);
        $this->makeFind($bot, 'find_large');

        $candidates = $this->candidates($bot);

        $this->assertSame(['ship:freighter'], array_map(fn (Candidate $c) => $c->key, $candidates));
        $this->assertSame('request_ship', $candidates[0]->name);
        $this->assertGreaterThan(0.0, $candidates[0]->net);
    }

    public function test_no_waiting_find_no_freighter(): void
    {
        $this->assertSame([], $this->candidates($this->bootHangar2(5000)));
    }

    public function test_the_next_hire_must_stay_affordable(): void
    {
        $bot = $this->bootHangar2((int) config('ships.freighter.nexus_cost'));
        $this->makeFind($bot, 'find_large');

        $this->assertSame([], $this->candidates($bot));
    }
}
