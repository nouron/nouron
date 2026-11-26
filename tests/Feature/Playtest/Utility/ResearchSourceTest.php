<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\Techtree\ResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\PinsPreScaleApValues;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\TestCase;

/** T30 §14 U6: research by effect per AP, not by lowest id. */
class ResearchSourceTest extends TestCase
{
    use PinsPreScaleApValues;
    use RefreshDatabase;
    use UtilityFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pinPreScaleApValues();
    }

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new ResearchSource(19))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    private function bootWithLab(int $labLevel): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Sciencelab->value, $labLevel);
        $this->giveAp($bot, 15);

        return $bot;
    }

    public function test_geology_is_researched_before_construction(): void
    {
        $bot = $this->bootWithLab(2);
        $keys = array_map(fn (Candidate $c) => $c->key, $this->candidates($bot));
        $this->assertContains('research:92', $keys, 'fixture: geology must be researchable at lab Lv2');

        $this->assertSame('research:92', UtilitySelector::select($this->candidates($bot), [])->chosen->key);
    }

    public function test_cc_gated_levels_are_not_offered(): void
    {
        $bot = $this->bootWithLab(5);
        $this->setCc($bot, 3);
        foreach (range(90, 96) as $id) {
            $this->setKnowledge($bot, $id, $id === 92 ? 2 : 3);
        }

        $this->assertSame(['research:92'], array_map(fn (Candidate $c) => $c->key, $this->candidates($bot)));
    }

    public function test_research_below_the_minimum_density_is_not_offered(): void
    {
        // Review S3 I6: research is no endless AP sink — below the small self-salvage
        // density (research_min_density) the AP fall through to scans and salvage.
        $bot = $this->bootWithLab(2);
        $candidates = $this->candidates($bot);

        $this->assertContains('research:92', array_map(fn (Candidate $c) => $c->key, $candidates), 'geology stays worth it');
        $this->assertNotContains('research:95', array_map(fn (Candidate $c) => $c->key, $candidates), 'trade has no valued effect');
        foreach ($candidates as $c) {
            $this->assertGreaterThanOrEqual(0.3, $c->net / $c->apCost, $c->key);
        }
    }

    public function test_an_open_research_lead_objective_makes_every_knowledge_worth_researching(): void
    {
        // M3 2026-11-24: research had no objective term, so even objective-driven profiles
        // left trade, health & co. alone (no valued effect) while "Forschungsvorsprung" was open.
        $bot = $this->bootWithLab(2);
        $this->placeAt($bot, BuildingId::Bar->value, 1);   // trade needs the Cantina
        $this->placeAt($bot, 46, 1);                       // health needs an infirmary
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => 'task_research_lead',
            'target_value' => (int) config('game.run.tasks.task_research_lead.target'),
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);

        // Only objective-driven profiles weigh objectives (aimless: objectiveFocus 0).
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);
        $keys = array_map(fn (Candidate $c) => $c->key, (new ResearchSource(19))->candidates($bot, $ctx));
        $aimless = BotContext::capture($bot, BotProfile::named('aimless'), withGoal: true);
        $this->assertNotContains('research:95', array_map(fn (Candidate $c) => $c->key, (new ResearchSource(19))->candidates($bot, $aimless)), 'aimless ignores objectives');

        $this->assertContains('research:95', $keys, 'trade counts towards the objective');
        $this->assertContains('research:94', $keys, 'health counts towards the objective');
    }

    public function test_every_knowledge_level_is_an_equal_share_of_the_research_lead_objective(): void
    {
        // Spec 2026-11-25 §3.1/§7: "Forschungsvorsprung" is the sum of all knowledge levels,
        // so each level step is worth w_objective / target — cheap breadth gains per AP.
        $bot = $this->bootWithLab(2);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => 'task_research_lead',
            'target_value' => 16,
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);
        $ctx = BotContext::capture($bot, BotProfile::named('focus'), withGoal: true);
        $share = (float) $ctx->weights['w_objective'] / 16;

        $this->assertEqualsWithDelta($share, Valuers::research($bot, $ctx, 92, 0, 0)->terms['objective'] ?? 0.0, 1e-9, 'level 0 -> 1');
        $this->assertEqualsWithDelta($share, Valuers::research($bot, $ctx, 90, 4, 0)->terms['objective'] ?? 0.0, 1e-9, 'level 4 -> 5');
    }

    public function test_knowledge_without_its_second_building_is_not_offered(): void
    {
        // M4 2026-11-24: health (needs an infirmary) and defense (needs hangar Lv2) took AP
        // although they could never level up; the levelup then failed 75-83 times per run
        // with requires_building.
        $bot = $this->bootWithLab(3);
        $offered = fn () => array_column(BotStrategy::researchableKnowledge($bot)['eligible'], 'id');

        $this->assertNotContains(94, $offered(), 'health needs an infirmary');
        $this->assertNotContains(96, $offered(), 'defense needs hangar Lv2');

        $this->placeAt($bot, 46, 1); // infirmary
        $this->assertContains(94, $offered(), 'with an infirmary health is open');
    }

    public function test_without_a_lab_knowledge_opens_with_its_own_building(): void
    {
        // Owner 2026-11-26: the lab is no gate any more; cartography hangs on the hangar alone.
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::Sciencelab->value)->delete();
        $this->placeAt($bot, BuildingId::Hangar->value, 1);

        $this->assertContains(91, array_column(BotStrategy::researchableKnowledge($bot)['eligible'], 'id'));
    }

    public function test_do_levels_up_when_ready_and_adds_otherwise(): void
    {
        $bot = $this->bootWithLab(2);
        $cost = app(ResearchService::class)->knowledgeLevelupCost($bot->colonyId, 92, (int) DB::table('researches')->where('id', 92)->value('ap_for_levelup'));
        $this->setKnowledge($bot, 92, 0, $cost);
        $geology = collect($this->candidates($bot))->firstWhere('key', 'research:92');

        $this->assertTrue(($geology->do)($bot)['ok']);
        $this->assertSame(1, (int) DB::table('colony_researches')->where('colony_id', $bot->colonyId)->where('research_id', 92)->value('level'));
    }

    public function test_research_options_keep_the_legacy_candidate(): void
    {
        $bot = $this->bootWithLab(2);

        $this->assertSame(90, BotStrategy::researchableKnowledge($bot)['eligible'][0]->id);
    }
}
