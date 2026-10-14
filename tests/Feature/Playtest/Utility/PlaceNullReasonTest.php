<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\TestCase;

/** Review S3 I4: place_building says why it found nothing (diagnostic, report key place_null_reasons). */
class PlaceNullReasonTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function placeCandidate(BotSession $bot): mixed
    {
        return collect(BotStrategy::default(BotProfile::named('default')))->firstWhere('name', 'place_building')['when']($bot);
    }

    private function bootRich(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->setCc($bot, 2);
        $this->placeAt($bot, 41, 1);
        $this->setResource($bot, 3, 200);
        $this->setCredits($bot, 5000);
        $this->setSupply($bot, 1000);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_a_hold_blocked_placement_records_hold(): void
    {
        $bot = $this->bootRich();
        $bot->regolithHold = 190;

        $this->assertNull($this->placeCandidate($bot));

        $this->assertSame([$bot->sol => ['hold' => 1]], $bot->placeNullReasons);
    }

    public function test_a_supply_blocked_placement_records_supply(): void
    {
        $bot = $this->bootRich();
        $this->placeAt($bot, BuildingId::Housing->value, 1, instanceId: 2);   // housing cap (2) reached
        $this->setSupply($bot, 0);

        $this->assertNull($this->placeCandidate($bot));

        $this->assertSame([$bot->sol => ['supply' => 1]], $bot->placeNullReasons);
    }

    public function test_a_found_candidate_records_nothing(): void
    {
        $bot = $this->bootRich();

        $this->assertNotNull($this->placeCandidate($bot));

        $this->assertSame([], $bot->placeNullReasons);
    }
}
