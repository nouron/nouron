<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\Techtree\ResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\BotStrategy;
use Tests\Feature\Playtest\Utility\Sources\ResearchSource;
use Tests\TestCase;

/** T30 §14 U6: research by effect per AP, not by lowest id. */
class ResearchSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

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
