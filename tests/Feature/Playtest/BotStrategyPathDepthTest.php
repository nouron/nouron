<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\DailyApCapService;
use App\Services\PathDepthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\Utility\UtilityFixtures;
use Tests\TestCase;

/**
 * T58a (owner 2026-11-20): the bot uses the AP sinks added since its last calibration —
 * Tomas' advice (free), Hangar training and Cantina conversations. Smoke batch 2026-11-20
 * left 35 % (Cantina-First) and 23 % (Hangar-First) of the AP of Sols 12-25 unused, which
 * measured the bot, not the balance.
 */
class BotStrategyPathDepthTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        config(['game.bar.guests_per_sol' => ['min' => 99, 'max' => 99]]);
    }

    // ── Tomas' advice ─────────────────────────────────────────────────────────

    public function test_asks_tomas_for_advice_when_it_is_due(): void
    {
        $bot = $this->bootWithCantina();
        $this->relationship($bot, 'bartender', 5);

        $knowledgeId = $this->rule('ask_bartender_advice')['when']($bot);

        $this->assertNotNull($knowledgeId);
        $this->assertTrue($this->rule('ask_bartender_advice')['do']($bot, $knowledgeId)['ok'] ?? false, json_encode(end($bot->log)));
    }

    public function test_no_advice_while_tomas_is_still_a_stranger(): void
    {
        $bot = $this->bootWithCantina();

        $this->assertEmpty($this->rule('ask_bartender_advice')['when']($bot));
    }

    public function test_no_advice_when_every_knowledge_is_blocked_for_a_bonus(): void
    {
        $bot = $this->bootWithCantina();
        $this->relationship($bot, 'bartender', 5);
        // The next level of every knowledge waits for a Command Center level the colony lacks.
        config(['game.knowledge_cc_level_cap' => [1 => 99, 2 => 99, 3 => 99, 4 => 99, 5 => 99]]);

        $this->assertEmpty($this->rule('ask_bartender_advice')['when']($bot), 'the server would answer knowledge_cc_gate');
    }

    // ── Cantina conversations ─────────────────────────────────────────────────

    public function test_talks_to_tomas_first_with_the_short_option(): void
    {
        $bot = $this->bootWithCantina();
        $this->giveAp($bot, 3);

        $candidate = $this->rule('converse_character')['when']($bot);

        $this->assertSame(['slug' => 'bartender', 'ap' => 1], $candidate);
        $this->assertTrue($this->rule('converse_character')['do']($bot, $candidate)['ok'] ?? false, json_encode(end($bot->log)));
    }

    public function test_one_conversation_per_figure_and_sol(): void
    {
        $bot = $this->bootWithCantina();
        $this->giveAp($bot, 3);
        app(DailyApCapService::class)->record($bot->colonyId, PathDepthService::conversationCapKey('bartender'), 1);

        // Only Tomas sits in the cantina, and he was talked to today.
        $this->assertEmpty($this->rule('converse_character')['when']($bot));
    }

    public function test_no_conversation_without_ap(): void
    {
        $bot = $this->bootWithCantina();
        $this->giveAp($bot, 0);

        $this->assertEmpty($this->rule('converse_character')['when']($bot));
    }

    // ── Hangar training ───────────────────────────────────────────────────────

    public function test_trains_an_unlocked_family_with_cap_left(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->giveAp($bot, 3);

        $family = $this->rule('train_mission_family')['when']($bot);

        $this->assertContains($family, ['exploration', 'salvage'], 'Hangar 1 unlocks exploration and salvage only');
        $this->assertTrue($this->rule('train_mission_family')['do']($bot, $family)['ok'] ?? false, json_encode(end($bot->log)));
    }

    public function test_no_training_once_every_unlocked_family_used_todays_cap(): void
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 1);
        $this->giveAp($bot, 3);
        $service = app(PathDepthService::class);
        foreach (['exploration', 'salvage'] as $family) {
            $cap = $service->state($bot->colonyId, PathDepthService::KIND_FAMILY, $family)['ap_cap']['cap'];
            app(DailyApCapService::class)->record($bot->colonyId, PathDepthService::capKey($family), $cap);
        }

        $this->assertEmpty($this->rule('train_mission_family')['when']($bot));
    }

    public function test_no_training_without_a_hangar(): void
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::Hangar->value)->delete();
        $this->giveAp($bot, 3);

        $this->assertEmpty($this->rule('train_mission_family')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithCantina(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        foreach (['bar_offers', 'bar_encounters', 'bar_concerns', 'bar_information_encounters'] as $table) {
            DB::table($table)->where('colony_id', $bot->colonyId)->delete();
        }
        config(['game.bar.story_encounter.chance' => 0.0]);
        $this->placeAt($bot, BuildingId::Bar->value, 1);

        return $bot;
    }

    private function relationship(BotSession $bot, string $slug, int $level): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'kind' => PathDepthService::KIND_CHARACTER, 'entity_key' => $slug],
            ['level' => $level, 'ap_spend' => 0],
        );
    }

    /** @return array{name:string, when:callable, do:callable} */
    private function rule(string $name): array
    {
        foreach (BotStrategy::default() as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }
        $this->fail("rule {$name} not found");
    }
}
