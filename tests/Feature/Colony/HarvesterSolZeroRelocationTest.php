<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\OnboardingService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sol-0 flow of a NEW run: the single Harvester must always be relocatable once (real AP
 * gate on), and the dropped second Harvester must not be offered while its CC gate holds.
 */
class HarvesterSolZeroRelocationTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const HARVESTER_ID = 27;

    private function startNewRun(): User
    {
        $this->app->make(TestSeeder::class)->run();
        config(['game.bypass.ap_checks' => false, 'game.bypass.resource_costs' => false]);
        $user = User::where('user_id', 3)->firstOrFail();
        $this->app->make(OnboardingService::class)->resetColonyToSol1(3, self::COLONY_ID);
        DB::table('runs')->where('user_id', 3)->where('status', 'active')->update(['started_at' => now()]);

        return $user;
    }

    private function explored_regolith_tile(): object
    {
        $tile = DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)
            ->where('tile_type', 'like', 'regolith_%')->where('is_explored', 1)->first();
        $this->assertNotNull($tile, 'a new run pre-scouts at least one regolith tile');

        return $tile;
    }

    public function test_single_harvester_can_be_relocated_in_sol_zero_of_a_new_run(): void
    {
        $user = $this->startNewRun();
        $tile = $this->explored_regolith_tile();

        $this->actingAs($user)->postJson(route('colony.building.place'), [
            'building_id' => self::HARVESTER_ID, 'q' => $tile->q, 'r' => $tile->r, 'instance_id' => 1,
        ])->assertOk()->assertJson(['ok' => true]);

        $row = DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::HARVESTER_ID)->where('instance_id', 1)->first();
        $this->assertSame([(int) $tile->q, (int) $tile->r], [(int) $row->tile_x, (int) $row->tile_y]);
    }

    public function test_second_harvester_stays_rejected_below_its_cc_gate(): void
    {
        $user = $this->startNewRun();
        $tile = $this->explored_regolith_tile();

        $this->actingAs($user)->postJson(route('colony.building.place'), [
            'building_id' => self::HARVESTER_ID, 'q' => $tile->q, 'r' => $tile->r, 'instance_id' => 2,
        ])->assertStatus(422)->assertJson(['error' => 'harvester_second_instance_cc_gate']);
    }

    public function test_tile_panel_offers_move_and_gates_second_harvester_button(): void
    {
        $html = $this->actingAs($this->startNewRun())->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('harvesterSecondCcLevel', $html);
        $this->assertStringContainsString('canPlaceSecondHarvester(selectedTile)', $html);
        $this->assertStringContainsString('canMoveHarvesterHere(selectedTile)', $html);
        $this->assertStringContainsString('moveHarvesterHere(selectedTile)', $html);
    }

    public function test_move_chip_shows_the_real_ap_cost_per_hex(): void
    {
        // The Harvester "Verlegen" chip must show the cost that is actually charged
        // (config relocate_ap_per_hex), not a hardcoded 1 AP/Feld.
        $perHex = (int) config('game.harvester.relocate_ap_per_hex');
        $html = $this->actingAs($this->startNewRun())->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString(__('colony.ap_per_tile', ['n' => $perHex]), $html);
        $this->assertGreaterThan(1, $perHex, 'the old hardcoded 1 would no longer be distinguishable');
        $this->assertStringNotContainsString(__('colony.ap_per_tile', ['n' => 1]), $html);
    }
}
