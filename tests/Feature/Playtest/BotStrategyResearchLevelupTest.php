<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\Techtree\ResearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Perf finding 2026-09-28: every 'research_knowledge' rule invocation tried
 * order:'levelup' first no matter what, which is guaranteed to fail with
 * 'insufficient_ap_invested' until ap_spend reaches the level's AP cost —
 * ~1015 of ~2900 actions per playtest run were this doomed request. The
 * ap_spend/ap_for_levelup values needed to know whether a levelup can
 * succeed are already cheaply readable from the DB before making any HTTP
 * request, so the rule should only attempt 'levelup' once that threshold is
 * actually reached.
 */
class BotStrategyResearchLevelupTest extends TestCase
{
    use RefreshDatabase;

    public function test_research_rule_only_adds_when_ap_spend_is_below_the_levelup_threshold(): void
    {
        $bot = BotSession::boot($this, 1);
        $researchId = $this->researchCandidateId($bot);
        $this->assertNotNull($researchId, 'fixture needs an eligible research candidate');
        $this->setApSpend($bot, $researchId, 0);
        $this->giveAp($bot);

        $logCountBefore = count($bot->log);
        $rule = $this->rule('research_knowledge');
        $rule['do']($bot, $researchId);

        $newEntries = array_slice($bot->log, $logCountBefore);
        $this->assertCount(1, $newEntries, 'below the levelup threshold, only one request (add) should be made: '.json_encode($newEntries));
        $this->assertSame('research_knowledge', $newEntries[0]['rule']);
        $this->assertTrue($newEntries[0]['ok'], 'the add request should succeed: '.json_encode($newEntries));
    }

    public function test_research_rule_calls_levelup_when_ap_spend_already_reached_the_threshold(): void
    {
        $bot = BotSession::boot($this, 1);
        $researchId = $this->researchCandidateId($bot);
        $this->assertNotNull($researchId, 'fixture needs an eligible research candidate');
        $apForLevelup = $this->apForLevelup($bot, $researchId);
        $this->setApSpend($bot, $researchId, $apForLevelup);
        $this->giveAp($bot);

        $levelBefore = $this->researchLevel($bot, $researchId);
        $logCountBefore = count($bot->log);
        $rule = $this->rule('research_knowledge');
        $rule['do']($bot, $researchId);

        $newEntries = array_slice($bot->log, $logCountBefore);
        $this->assertCount(1, $newEntries, 'at the levelup threshold, a single levelup request should suffice: '.json_encode($newEntries));
        $this->assertTrue($newEntries[0]['ok'], 'the levelup request should succeed: '.json_encode($newEntries));
        $this->assertSame($levelBefore + 1, $this->researchLevel($bot, $researchId));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function researchCandidateId(BotSession $bot): ?int
    {
        // researchCandidate() holds back research below 3 active advisors unless
        // there's enough Regolith spare for the cheapest pending path building —
        // not what this test is about, so give it plenty.
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => 3],
            ['amount' => 100000],
        );

        // Knowledge research requires Sciencelab Lv1+ (researches.required_building_level) —
        // a fresh Sol-1 colony has none placed yet.
        if (! DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::Sciencelab->value)->exists()) {
            $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_colony_zone', 1)
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('colony_buildings as cb')
                    ->where('cb.colony_id', $bot->colonyId)
                    ->whereColumn('cb.tile_x', 'colony_tiles.q')
                    ->whereColumn('cb.tile_y', 'colony_tiles.r'))
                ->first();
            $this->assertNotNull($tile, 'fixture needs a free colony-zone tile for Sciencelab');
            DB::table('colony_buildings')->insert([
                'colony_id' => $bot->colonyId,
                'building_id' => BuildingId::Sciencelab->value,
                'instance_id' => 1,
                'level' => 1,
                'status_points' => 20,
                'ap_spend' => 0,
                'tile_x' => $tile->q,
                'tile_y' => $tile->r,
            ]);
        }

        $ref = new \ReflectionMethod(BotStrategy::class, 'researchCandidate');
        $ref->setAccessible(true);

        return $ref->invoke(null, $bot);
    }

    private function apForLevelup(BotSession $bot, int $researchId): int
    {
        $fallback = (int) (DB::table('researches')->where('id', $researchId)->value('ap_for_levelup') ?? 0);

        return app(ResearchService::class)->knowledgeLevelupCost($bot->colonyId, $researchId, $fallback);
    }

    private function researchLevel(BotSession $bot, int $researchId): int
    {
        return (int) (DB::table('colony_researches')
            ->where('colony_id', $bot->colonyId)
            ->where('research_id', $researchId)
            ->value('level') ?? 0);
    }

    private function setApSpend(BotSession $bot, int $researchId, int $apSpend): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => $researchId],
            ['ap_spend' => $apSpend, 'level' => 0, 'status_points' => 20],
        );
    }

    private function giveAp(BotSession $bot): void
    {
        // Frees up the shared colony AP pool for the current tick — the bot
        // fixture's base AP (config('game.ap.base')) is already enough for
        // these tests, this only clears anything the boot fixture locked.
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
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
