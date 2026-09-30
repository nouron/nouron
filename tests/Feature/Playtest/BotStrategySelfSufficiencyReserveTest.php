<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Baseline batch 2026-09-30: the objective-focused profiles (focus/eager) never
 * completed task_self_sufficiency — they spent Regolith down to ~100 in Phase 2
 * while the streak needs Regolith > regolith_min. The bot had no rule for this
 * objective at all, so the batch said nothing about its real difficulty.
 *
 * Rule: while task_self_sufficiency is drawn and open, an objective-focused
 * profile keeps regolith_min (+10 % buffer) in stock; Regolith spending may only
 * use the surplus. Non-focused profiles are unaffected.
 */
class BotStrategySelfSufficiencyReserveTest extends TestCase
{
    use RefreshDatabase;

    private const RES_REGOLITH = 3;

    public function test_focus_profile_keeps_the_self_sufficiency_reserve_when_placing(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setRegolith($bot, $this->reserve() + 10);

        $this->assertNull(
            $this->rule(BotProfile::named('focus'), 'place_building')['when']($bot),
            'placement must not dip into the Regolith kept for self-sufficiency',
        );
    }

    public function test_focus_profile_builds_with_the_surplus_above_the_reserve(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setRegolith($bot, $this->reserve() + 1000);

        $this->assertNotNull($this->rule(BotProfile::named('focus'), 'place_building')['when']($bot));
    }

    public function test_default_profile_is_not_held_back(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setRegolith($bot, $this->reserve() + 10);

        $this->assertNotNull($this->rule(BotProfile::named('default'), 'place_building')['when']($bot));
    }

    public function test_focus_profile_is_not_held_back_without_the_objective(): void
    {
        $bot = $this->bootPhase2WithObjective();
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        $this->setRegolith($bot, $this->reserve() + 10);

        $this->assertNotNull($this->rule(BotProfile::named('focus'), 'place_building')['when']($bot));
    }

    public function test_focus_profile_still_repairs_inside_the_reserve(): void
    {
        $bot = $this->bootPhase2WithObjective();
        $this->setRegolith($bot, $this->reserve() + 10);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->update(['status_points' => 1]);

        $repairRules = array_filter(
            ['repair_critical', 'repair_maintenance'],
            fn (string $name) => $this->rule(BotProfile::named('focus'), $name)['when']($bot) !== null,
        );

        $this->assertNotEmpty($repairRules, 'repairs cost 1 Regolith and keep buildings from decaying — never blocked by the reserve');
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function reserve(): int
    {
        return (int) floor((int) config('game.run.tasks.task_self_sufficiency.regolith_min') * 1.1) + 1;
    }

    private function bootPhase2WithObjective(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2]);
        DB::table('run_objectives')->where('run_id', $bot->runId)->delete();
        DB::table('run_objectives')->insert([
            'run_id' => $bot->runId,
            'task_key' => 'task_self_sufficiency',
            'target_value' => (int) config('game.run.tasks.task_self_sufficiency.target'),
            'current_value' => 0,
            'streak_value' => 0,
            'completed_at' => null,
        ]);
        // Supply and AP are not what this test is about — keep them out of the way.
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['supply' => 1000]);

        return $bot;
    }

    private function setRegolith(BotSession $bot, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'resource_id' => self::RES_REGOLITH],
            ['amount' => $amount],
        );
    }

    /**
     * @return array{name:string, when:callable, do:callable}
     */
    private function rule(BotProfile $profile, string $name): array
    {
        foreach (BotStrategy::default($profile) as $rule) {
            if ($rule['name'] === $name) {
                return $rule;
            }
        }

        $this->fail("Rule {$name} not found");
    }
}
