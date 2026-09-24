<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Js;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A43 — Rückbau in the colony tile panel (GDD techtree §11.5): server-rendered
 * wiring of the button, the confirmation dialog and its texts. The per-building
 * visibility (placed instances only, CC never below 1) is evaluated client-side
 * by canLeveldown() in public/js/colony-hexgrid.js; the buildings payload it reads
 * is covered here.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart).
 */
class BuildingLeveldownUiTest extends TestCase
{
    use RefreshDatabase;

    private const BART_USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function hexview(): TestResponse
    {
        return $this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->get(route('colony.view'));
    }

    public function test_hexview_wires_preview_and_leveldown_endpoints(): void
    {
        $response = $this->hexview();

        $response->assertOk();
        $response->assertSee(route('colony.building.leveldown-preview'), false);
        $response->assertSee(route('colony.building.leveldown'), false);
    }

    public function test_rueckbau_button_is_gated_by_can_leveldown_and_opens_the_dialog(): void
    {
        $response = $this->hexview();

        $response->assertSee('x-if="canLeveldown(selectedBuilding)"', false);
        $response->assertSee('@click="openLeveldown(selectedBuilding)"', false);
        $response->assertSee('x-ref="leveldownDialog"', false);
        $response->assertSee('@click="confirmLeveldown()"', false);
    }

    public function test_rueckbau_button_reads_bau_abbrechen_on_a_level_zero_construction_site(): void
    {
        $response = $this->hexview();

        $response->assertSee(
            'selectedBuilding.level === 0 ? '.Js::from(__('colony.leveldown_cancel_construction'))
                .' : '.Js::from(__('colony.leveldown')),
            false,
        );
    }

    public function test_rueckbau_button_carries_no_ap_cost_chip(): void
    {
        $html = $this->hexview()->getContent();

        $this->assertSame(1, preg_match('/<div class="tile-leveldown">(.*?)<\/div>/s', $html, $match));
        $this->assertStringNotContainsString('ap-cost-chip', $match[1]);
    }

    public function test_confirmation_dialog_texts_are_translated_into_the_page(): void
    {
        $response = $this->hexview();

        foreach ([
            'leveldown_new_level',
            'leveldown_ap_forfeited',
            'leveldown_freed_workplaces',
            'leveldown_capacity_loss',
            'leveldown_homeless',
            'leveldown_tile_released',
            'leveldown_construction_cancelled',
        ] as $key) {
            $response->assertSee(json_encode(__("colony.{$key}")), false);
        }
        $response->assertSee(__('colony.leveldown_no_refund'));
    }

    /**
     * canLeveldown() decides on tile_x: a building row without a tile has no
     * Rückbau button, a placed one (incl. a level-0 construction site) has.
     */
    public function test_buildings_payload_distinguishes_placed_from_unplaced_rows(): void
    {
        DB::table('colony_buildings')
            ->where(['colony_id' => self::COLONY_ID, 'building_id' => 27, 'instance_id' => 1])
            ->update(['tile_x' => 1, 'tile_y' => 0]);

        $buildings = collect($this->hexview()->viewData('buildings'));

        $harvester = $buildings->first(fn ($b) => (int) $b->building_id === 27 && (int) $b->instance_id === 1);
        $this->assertSame(1, (int) $harvester->tile_x);

        $unplaced = $buildings->first(fn ($b) => (int) $b->building_id !== 25 && $b->tile_x === null);
        $this->assertNotNull($unplaced, 'fixture holds at least one unplaced building row');
    }
}
