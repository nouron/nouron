<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\ResourcesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\TestCase;

/** Spec 3.1: one consistent snapshot of state, prices and weights per decision. */
class BotContextTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    public function test_capture_reads_colony_state_and_profile_weights(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 3, 77);

        $ctx = BotContext::capture($bot, BotProfile::named('default')->withOpening('hangar'));

        $this->assertSame([0, 1, 77, 40, 'hangar', 44], [$ctx->sol, $ctx->phase, $ctx->regolith, $ctx->horizon(), $ctx->opening, $ctx->openingBuildingId]);
        $this->assertSame(0.5, $ctx->lambdaAp, 'start value until the session tracks lambda');
        $this->assertSame(8.0, $ctx->incomeRg, 'income floor without any sol_next yet');
        $this->assertSame(0.95, $ctx->weights['gamma']);
        $this->assertSame(1.0, $ctx->phase1Urgency());
    }

    public function test_auto_opening_has_no_opening_building(): void
    {
        $bot = BotSession::boot($this, 1);

        $this->assertNull(BotContext::capture($bot, BotProfile::named('default'))->openingBuildingId);
    }

    public function test_income_is_the_mean_regolith_gain_of_the_last_three_sols(): void
    {
        $bot = BotSession::boot($this, 1);
        foreach ([100, 20, 30, 40] as $gain) {
            $this->logSolNext($bot, $gain);
        }

        $this->assertSame(30.0, BotContext::capture($bot, BotProfile::named('default'))->incomeRg);
    }

    public function test_organics_price_follows_the_scarcity_formula(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->setResource($bot, 5, 0);
        $profile = BotProfile::named('default');
        $w = $profile->weights();
        $foodNeed = app(ResourcesService::class)->foodNeed($bot->colonyId);

        $empty = BotContext::capture($bot, $profile)->price('organics');
        $this->setResource($bot, 5, 10000);
        $full = BotContext::capture($bot, $profile)->price('organics');

        $this->assertEqualsWithDelta($w['price']['organics'] * Valuation::scarcity($foodNeed * 3, 0, 0.5, 4.0), $empty, 1e-9);
        $this->assertGreaterThanOrEqual($full, $empty);
    }

    public function test_lambda_comes_from_the_session_once_tracked(): void
    {
        $bot = BotSession::boot($this, 1);
        $bot->lambdaAp = 1.7;

        $this->assertSame(1.7, BotContext::capture($bot, BotProfile::named('default'))->lambdaAp);
    }
}
