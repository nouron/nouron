<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'relationships', 'conversationOptionCantinaLevel', 'relationshipMaxLevel',
            'bartender' => ['relationship_level', 'ap_bonus', 'talked_today', 'today_result', 'advice_open'],
        ]);
    }

    // ── Stille Forschung (spec 2026-11-11-tomas-rat-rueckmeldung, sentences 24–25) ──

    private function setTomas(int $level): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => 1, 'kind' => 'character', 'entity_key' => 'bartender'],
            ['level' => $level, 'ap_spend' => 0],
        );
    }

    public function test_knowledge_options_carry_selectable_and_reason(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 44)->delete(); // cartography needs the hangar

        $options = collect($this->getJson('/api/v1/cantina')->assertOk()->json('knowledgeOptions'))->keyBy('key');

        $this->assertTrue($options['construction']['selectable']);
        $this->assertFalse($options['cartography']['selectable']);
        $this->assertSame('requires_building', $options['cartography']['blocked_reason']);
        $this->assertSame(__('knowledge.bonus_wait_requires_building'), $options['cartography']['blocked_label']);
    }

    public function test_deva_choices_carry_selectable_too(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 27)->delete(); // geology needs the harvester

        $choices = collect($this->getJson('/api/v1/cantina')->assertOk()->json('devaKnowledgeChoices'))->keyBy('key');

        $this->assertFalse($choices['geology']['selectable']);
        $this->assertTrue($choices['health']['selectable']);
    }

    public function test_bartender_block_while_tomas_is_a_stranger(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/cantina')->assertOk()->assertJson(['bartender' => [
            'relationship_level' => 0, 'ap_bonus' => 0, 'talked_today' => false, 'today_result' => null, 'advice_open' => false,
        ]]);
    }

    public function test_bartender_block_before_and_after_todays_talk(): void
    {
        config(['game.bartender.ap_bonus_by_relationship_level' => [0 => 0, 1 => 1, 2 => 1, 3 => 2, 4 => 2, 5 => 3]]);
        Sanctum::actingAs(User::find(self::USER_ID));
        $this->setTomas(3);

        $this->getJson('/api/v1/cantina')->assertOk()->assertJson(['bartender' => [
            'relationship_level' => 3, 'ap_bonus' => 2, 'talked_today' => false, 'today_result' => null, 'advice_open' => true,
        ]]);

        $this->postJson('/api/v1/colony/bar/talk-to-bartender', ['knowledge_id' => 90])->assertOk();

        $this->getJson('/api/v1/cantina')->assertOk()
            ->assertJsonPath('bartender.talked_today', true)
            ->assertJsonPath('bartender.advice_open', false)
            ->assertJsonPath('bartender.today_result.knowledge_id', 90)
            ->assertJsonPath('bartender.today_result.ap_invested', 2);
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
