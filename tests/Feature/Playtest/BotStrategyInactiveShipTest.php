<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T14: a ship whose hangar sits below its class is inactive (GDD §7) and the
 * server refuses its mission start with ship_inactive. The bot's dispatch rules
 * must skip such a ship instead of eating that rejection every Sol.
 */
class BotStrategyInactiveShipTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_FREIGHTER = 47;

    public function test_compounds_mission_skips_a_freighter_in_a_level_1_hangar(): void
    {
        $bot = $this->bootWithDockedFreighter(hangarLevel: 1);

        $this->assertEmpty($this->rule('dispatch_compounds_mission')['when']($bot));
    }

    public function test_compounds_mission_uses_the_freighter_once_the_hangar_matches_its_class(): void
    {
        $bot = $this->bootWithDockedFreighter(hangarLevel: 2);

        $candidate = $this->rule('dispatch_compounds_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(self::SHIP_FREIGHTER, (int) $candidate->ship_id);
    }

    public function test_salvage_mission_skips_a_freighter_in_a_level_1_hangar(): void
    {
        $bot = $this->bootWithDockedFreighter(hangarLevel: 1);
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->first();
        DB::table('colony_tiles')->where('id', $tile->id)->update(['is_deep_scanned' => 1, 'event_type' => 'event_ruin']);

        $this->assertEmpty($this->rule('dispatch_salvage_mission')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithDockedFreighter(int $hangarLevel): BotSession
    {
        $bot = BotSession::boot($this, 1);

        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Hangar->value)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1,
            'level' => $hangarLevel,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => 9,
            'tile_y' => 9,
        ]);
        DB::table('colony_ships')->insert([
            'colony_id' => $bot->colonyId,
            'ship_id' => self::SHIP_FREIGHTER,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'hangar_instance_id' => 1,
            'ship_state' => 'docked',
        ]);
        // mission_salvage_sweep needs construction Lv1.
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => 90],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0],
        );

        return $bot;
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
