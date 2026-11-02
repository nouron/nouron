<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cantina JSON API (ADR 0006 stage 4d): same data as the Blade bar screen, actions on
 * the existing Bar/Merchant controllers. Action logic is covered by the Blade feature
 * tests — here the wiring.
 */
class CantinaApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_requires_token(): void
    {
        $this->getJson('/api/v1/cantina')->assertUnauthorized();
    }

    public function test_returns_cantina_data(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/cantina')->assertOk()->assertJsonStructure([
            'barLevel', 'currentSol', 'offers', 'offerTerms', 'offerDialogs', 'negotiatedOffers',
            'tradeAdvantage', 'merchantAdvantage', 'merchantVisit', 'merchantItems', 'merchantForecast',
            'hotspots', 'characterAssignment', 'characters', 'firstVisit', 'offerApCost', 'negotiateApCost',
            'encounter', 'storyEncounterSlug', 'concern', 'concernHasFailureLine', 'informationEncounter',
            'informationOutcomeLines', 'knowledgeOptions', 'concernApCost', 'devaKnowledgeChoices',
            'relationships', 'relationshipGate', 'relationshipMaxLevel',
        ]);
    }

    public function test_actions_are_wired(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        // Unknown ids / empty bodies: the controllers answer with their own JSON, never a route miss.
        foreach ([
            'colony/bar/accept/999999', 'colony/bar/negotiate/999999', 'colony/bar/accept-encounter/999999',
            'colony/bar/talk-to-bartender', 'colony/bar/concern/999999', 'colony/bar/information-encounter/999999',
            'colony/merchant/buy/999999', 'colony/merchant/visit/999999/open',
        ] as $path) {
            $status = $this->postJson("/api/v1/$path", [])->status();
            $this->assertContains($status, [200, 404, 409, 422], "$path → $status"); // markVisited is a silent no-op (200)
            $this->assertStringNotContainsString('could not be found', (string) $this->postJson("/api/v1/$path", [])->json('message'), $path);
        }
    }
}
