<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Baseline 2026-09-26 (B5): dispatch_mission always sent mission_recon_flight/easy,
 * so the Regolith missions (Path B: freighter supply run, drone prospecting flight)
 * never flew — after harvester exhaustion the bot had no Regolith alternative. When
 * Regolith is scarce the bot now sends a Regolith mission with a ship that can fly
 * it; otherwise recon stays the drone's routine job.
 */
class BotStrategyMissionChoiceTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_FREIGHTER = 47;

    private const SHIP_DRONE = 85;

    private const KNOWLEDGE_GEOLOGY = 92;

    public function test_freighter_flies_the_supply_run_when_regolith_is_scarce(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, regolith: 10);

        $candidate = $this->rule('dispatch_regolith_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('mission_supply_run', $candidate['mission_key']);
        $this->assertSame(self::SHIP_FREIGHTER, (int) $candidate['ship']->ship_id);
    }

    public function test_supply_run_dispatch_succeeds_against_the_server(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, regolith: 10);
        $rule = $this->rule('dispatch_regolith_mission');

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], json_encode($res['body']));
        $this->assertSame('mission_supply_run', DB::table('colony_hangar_missions')
            ->where('colony_id', $bot->colonyId)->value('destination'));
    }

    public function test_no_regolith_mission_while_regolith_is_plentiful(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, regolith: 300);

        $this->assertEmpty($this->rule('dispatch_regolith_mission')['when']($bot));
    }

    public function test_no_regolith_mission_without_the_organics_for_provisions(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, regolith: 10, organics: 0);

        $this->assertEmpty($this->rule('dispatch_regolith_mission')['when']($bot));
    }

    public function test_drone_flies_prospecting_when_scarce_and_geology_is_known(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_DRONE, regolith: 10);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => $bot->colonyId, 'research_id' => self::KNOWLEDGE_GEOLOGY],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0],
        );

        $candidate = $this->rule('dispatch_regolith_mission')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame('mission_prospecting_flight', $candidate['mission_key']);
        $this->assertContains($candidate['difficulty'], config('missions.catalog.mission_prospecting_flight.difficulties'));
    }

    public function test_drone_without_geology_gets_no_regolith_mission(): void
    {
        $bot = $this->bootWithDockedShip(self::SHIP_DRONE, regolith: 10);

        $this->assertEmpty($this->rule('dispatch_regolith_mission')['when']($bot));
    }

    public function test_regolith_mission_outranks_the_compounds_mission(): void
    {
        $names = array_column(BotStrategy::default(), 'name');

        $this->assertContains('dispatch_regolith_mission', $names);
        $this->assertLessThan(
            array_search('dispatch_compounds_mission', $names, true),
            array_search('dispatch_regolith_mission', $names, true),
        );
    }

    public function test_recon_dispatch_only_picks_a_drone(): void
    {
        // mission_recon_flight is drone-only (config/missions.php); a freighter
        // picked here would only collect a wrong_ship_type rejection.
        $bot = $this->bootWithDockedShip(self::SHIP_FREIGHTER, regolith: 300);

        $this->assertEmpty($this->rule('dispatch_mission')['when']($bot));
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithDockedShip(int $shipId, int $regolith, int $organics = 30): BotSession
    {
        $bot = BotSession::boot($this, 1);

        DB::table('colony_ships')->where('colony_id', $bot->colonyId)->delete();
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)
            ->where('building_id', BuildingId::Hangar->value)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1,
            'level' => 2,
            'status_points' => 20,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
        DB::table('colony_ships')->insert([
            'colony_id' => $bot->colonyId,
            'ship_id' => $shipId,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
            'hangar_instance_id' => 1,
            'ship_state' => 'docked',
        ]);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)
            ->update(['amount' => $regolith]);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)
            ->update(['amount' => $organics]);
        // The start harvester sits on a productive deposit — scarcity comes from the stock alone.
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('q', 1)->where('r', 0)
            ->update(['tile_type' => 'regolith_normal', 'resource_amount' => 200]);

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
