<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Recon stays the drone's routine job (Regolith now comes from salvage flights,
 * see BotStrategyFindFlightTest).
 */
class BotStrategyMissionChoiceTest extends TestCase
{
    use RefreshDatabase;

    private const SHIP_FREIGHTER = 47;

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
            'bay' => 1,
            'ship_state' => 'docked',
        ]);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 3)
            ->update(['amount' => $regolith]);
        DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 5)
            ->update(['amount' => $organics]);
        // The start harvester sits on a productive deposit — scarcity comes from the stock alone.
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('q', 1)->where('r', 0)
            ->update(['tile_type' => 'regolith_y2_d2', 'resource_amount' => 200]);

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
