<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Bot maintenance (baseline 2026-09-26, B2/B3/B4):
 *
 *  - B2: the Command Center (tile_x = NULL, it has no tile row) was filtered out
 *    of the repair candidates and never repaired — CC level losses were a bot bug.
 *  - B3: repair only fired below 30 % SP, so every building idled at ~6/20 and a
 *    single critical storm took several levels at once. The bot now keeps SP above
 *    a maintenance floor when AP and Regolith allow, lowest SP first, CC and
 *    housing preferred.
 *  - B4: a harvester on an exhausted tile produces nothing — repairing it every
 *    Sol only wasted AP (relocation is the separate relocate_harvester rule).
 */
class BotStrategyRepairTest extends TestCase
{
    use RefreshDatabase;

    private const BIO_FACILITY = 41;

    public function test_critical_repair_includes_the_command_center(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::CommandCenter->value, 1, 4);

        $candidate = $this->rule('repair_critical')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(BuildingId::CommandCenter->value, (int) $candidate->building_id);
    }

    public function test_critical_repair_of_the_command_center_needs_no_regolith(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::CommandCenter->value, 1, 4);
        $this->setRegolith($bot, 0);

        $candidate = $this->rule('repair_critical')['when']($bot);

        $this->assertNotEmpty($candidate, 'CC repair is AP-only (ColonyController::repairBuilding)');
        $this->assertSame(BuildingId::CommandCenter->value, (int) $candidate->building_id);
    }

    public function test_command_center_repair_succeeds_against_the_server(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::CommandCenter->value, 1, 4);
        $rule = $this->rule('repair_critical');

        $res = $rule['do']($bot, $rule['when']($bot));

        $this->assertTrue($res['ok'], json_encode($res['body']));
    }

    public function test_maintenance_repairs_a_building_below_the_floor(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        // 50 % — above the old 30 % trigger, below the maintenance floor.
        $this->setSp($bot, BuildingId::Housing->value, 1, 10);

        $this->assertEmpty($this->rule('repair_critical')['when']($bot));

        $candidate = $this->rule('repair_maintenance')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(BuildingId::Housing->value, (int) $candidate->building_id);
    }

    public function test_maintenance_leaves_buildings_above_the_floor_alone(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::Housing->value, 1, 14);

        $this->assertEmpty($this->rule('repair_maintenance')['when']($bot));
    }

    public function test_maintenance_runs_before_research_absorbs_the_ap(): void
    {
        $names = array_column(BotStrategy::default(), 'name');

        $this->assertContains('repair_maintenance', $names);
        $this->assertLessThan(
            array_search('research_knowledge', $names, true),
            array_search('repair_maintenance', $names, true),
        );
    }

    public function test_maintenance_prefers_the_lowest_status_first(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->placeBioFacility($bot, sp: 7);
        $this->setSp($bot, BuildingId::Housing->value, 1, 10);

        $candidate = $this->rule('repair_maintenance')['when']($bot);

        $this->assertSame(self::BIO_FACILITY, (int) $candidate->building_id);
    }

    public function test_maintenance_prefers_command_center_and_housing_on_equal_status(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->placeBioFacility($bot, sp: 10);
        $this->setSp($bot, BuildingId::Housing->value, 1, 10);

        $candidate = $this->rule('repair_maintenance')['when']($bot);

        $this->assertSame(BuildingId::Housing->value, (int) $candidate->building_id);
    }

    public function test_harvester_on_an_exhausted_tile_is_not_repaired(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::Harvester->value, 1, 3);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('q', 1)->where('r', 0)
            ->update(['tile_type' => 'regolith_normal', 'resource_amount' => 0]);

        $this->assertEmpty($this->rule('repair_critical')['when']($bot));
        $this->assertEmpty($this->rule('repair_maintenance')['when']($bot));
    }

    public function test_harvester_on_a_productive_tile_is_still_repaired(): void
    {
        $bot = $this->bootWithAllBuildingsIntact();
        $this->setSp($bot, BuildingId::Harvester->value, 1, 3);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('q', 1)->where('r', 0)
            ->update(['tile_type' => 'regolith_normal', 'resource_amount' => 200]);

        $candidate = $this->rule('repair_critical')['when']($bot);

        $this->assertNotEmpty($candidate);
        $this->assertSame(BuildingId::Harvester->value, (int) $candidate->building_id);
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function bootWithAllBuildingsIntact(): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->update(['status_points' => 20]);
        $this->setRegolith($bot, 200);

        return $bot;
    }

    private function setSp(BotSession $bot, int $buildingId, int $instanceId, float $sp): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', $bot->colonyId)
            ->where('building_id', $buildingId)
            ->where('instance_id', $instanceId)
            ->update(['status_points' => $sp]);
    }

    private function setRegolith(BotSession $bot, int $amount): void
    {
        DB::table('colony_resources')
            ->where('colony_id', $bot->colonyId)
            ->where('resource_id', 3)
            ->update(['amount' => $amount]);
    }

    private function placeBioFacility(BotSession $bot, float $sp): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId,
            'building_id' => self::BIO_FACILITY,
            'instance_id' => 1,
            'level' => 1,
            'status_points' => $sp,
            'ap_spend' => 0,
            'tile_x' => -1,
            'tile_y' => 1,
        ]);
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
