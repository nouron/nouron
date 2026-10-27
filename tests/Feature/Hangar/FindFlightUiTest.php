<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\OnboardingHintService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.4/§13.7 Task 7: server side of the find card, the target list and the hints. */
class FindFlightUiTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_hangar_missions')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_ships')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 5, 'tile_y' => 5]);
        DB::table('colony_ships')->insert(['colony_id' => self::COLONY_ID, 'ship_id' => 85, 'bay' => 0, 'ship_state' => 'docked',
            'level' => 0, 'status_points' => 20, 'ap_spend' => 0]);
        DB::table('colony_tiles')->updateOrInsert(['colony_id' => self::COLONY_ID, 'q' => 2, 'r' => 0],
            ['ring' => 2, 'tile_type' => 'terrain_empty', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 1,
                'event_type' => 'find_medium']);
    }

    private function bart(): User
    {
        return User::where('user_id', 3)->firstOrFail();
    }

    public function test_colony_view_ships_the_find_action_and_the_flight_route(): void
    {
        $html = $this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('"reason":"flight"', $html);
        $this->assertStringContainsString('/colony/hangar/__BAY__/dispatch', $html);
    }

    public function test_colony_view_exposes_organika_for_the_flight_button_gate(): void
    {
        $organika = (int) DB::table('colony_resources')->where('colony_id', self::COLONY_ID)->where('resource_id', 5)->value('amount');

        $html = $this->actingAs($this->bart())->get(route('colony.view'))->assertOk()->getContent();

        $this->assertStringContainsString('organika: '.$organika.',', $html);
        $this->assertStringContainsString('findGateOrganika', $html);
    }

    public function test_flight_dispatch_answers_with_the_tile_in_flight(): void
    {
        $res = $this->actingAs($this->bart())->postJson(route('colony.hangar.dispatch', ['bay' => 0]),
            ['mission_key' => 'mission_find_salvage', 'difficulty' => 'normal', 'target' => ['q' => 2, 'r' => 0]]);

        $res->assertOk()->assertJsonPath('tile.find.action.reason', 'in_flight')
            ->assertJsonStructure(['apAvailable', 'organika']);
    }

    public function test_hangar_build_card_carries_the_salvage_notice(): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 44)->delete();
        // Path buildings are offered only once the Agrardom stands.
        DB::table('colony_buildings')->updateOrInsert(['colony_id' => self::COLONY_ID, 'building_id' => 41, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 6, 'tile_y' => 6]);

        $buildings = $this->actingAs($this->bart())->getJson(route('colony.buildings.available'))->json('buildings');
        $hangar = collect($buildings)->firstWhere('building_id', 44);

        $this->assertNotNull($hangar, 'hangar is offered');
        $this->assertSame(__('colony.hangar_build_notice'), $hangar['notice']);
        foreach ($buildings as $entry) {
            $this->assertArrayHasKey('notice', $entry);
            if ((int) $entry['building_id'] !== 44) {
                $this->assertNull($entry['notice'], 'only the hangar carries a notice: '.$entry['key']);
            }
        }
    }

    public function test_find_flight_hint_is_active_from_hangar_level_one(): void
    {
        $service = $this->app->make(OnboardingHintService::class);
        $list = (new \ReflectionMethod($service, 'buildHintList'))->invoke($service, self::COLONY_ID, 5, 3);

        $this->assertTrue(collect($list)->firstWhere('key', 'hint_find_flight')['active']);
    }

    public function test_hangar_page_exposes_the_max_status_points_instead_of_a_literal(): void
    {
        $html = $this->actingAs($this->bart())->get(route('colony.hangar'))->assertOk()->getContent();

        $this->assertStringContainsString('maxStatus: '.(int) config('game.ship_repair.max_status_points'), $html);
        $this->assertStringNotContainsString("status_points + '/20'", $html);
        $this->assertStringNotContainsString('status_points >= 20', $html);
    }

    public function test_find_missing_event_has_a_readable_line(): void
    {
        $this->assertNotSame('missions.sol_report_find_missing', __('missions.sol_report_find_missing'));
    }
}
