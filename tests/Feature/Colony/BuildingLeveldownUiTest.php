<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A43 — Rückbau in the colony tile panel (GDD techtree §11.5): wiring of the button,
 * the confirmation dialog and its texts in the Svelte frontend (colony +page.svelte,
 * LeveldownDialog.svelte). The per-building visibility (placed instances only, CC never
 * below 1) is evaluated client-side by canLeveldown(); the buildings payload it reads
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

    private function colonyPage(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/colony/+page.svelte'));
    }

    private function dialog(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/colony/LeveldownDialog.svelte'));
    }

    public function test_hexview_wires_preview_and_leveldown_endpoints(): void
    {
        $dialog = $this->dialog();

        $this->assertStringContainsString('`/colony/building/leveldown-preview?', $dialog);
        $this->assertStringContainsString("'/colony/building/leveldown'", $dialog);
        $this->assertStringEndsWith('/colony/building/leveldown-preview', route('colony.building.leveldown-preview'));
        $this->assertStringEndsWith('/colony/building/leveldown', route('colony.building.leveldown'));
    }

    public function test_rueckbau_button_is_gated_by_can_leveldown_and_opens_the_dialog(): void
    {
        $page = $this->colonyPage();
        $dialog = $this->dialog();

        $this->assertStringContainsString('{#if canLeveldown(b)}', $page);
        $this->assertStringContainsString('onclick={() => (leveldownOf = b)}', $page);
        $this->assertStringContainsString('<LeveldownDialog', $page);
        $this->assertStringContainsString('<dialog bind:this={dialog} class="sol-modal leveldown-dialog"', $dialog);
        $this->assertStringContainsString('onclick={confirm}', $dialog);
    }

    public function test_rueckbau_button_reads_bau_abbrechen_on_a_level_zero_construction_site(): void
    {
        $this->assertStringContainsString(
            "b.level === 0 ? t('colony.leveldown_cancel_construction') : t('colony.leveldown')",
            $this->colonyPage(),
        );
        $this->assertNotSame('colony.leveldown_cancel_construction', __('colony.leveldown_cancel_construction'));
        $this->assertNotSame('colony.leveldown', __('colony.leveldown'));
    }

    public function test_rueckbau_button_carries_no_ap_cost_chip(): void
    {
        $page = $this->colonyPage();

        $this->assertSame(1, preg_match('/<div class="tile-leveldown">(.*?)<\/div>/s', $page, $match));
        $this->assertStringNotContainsString('ap-cost-chip', $match[1]);
    }

    public function test_confirmation_dialog_texts_are_translated_into_the_page(): void
    {
        $dialog = $this->dialog();

        foreach ([
            'leveldown_new_level',
            'leveldown_ap_forfeited',
            'leveldown_freed_workplaces',
            'leveldown_colonists_lost',
            'leveldown_unfilled',
            'leveldown_tile_released',
            'leveldown_construction_cancelled',
            'leveldown_no_refund',
        ] as $key) {
            $this->assertStringContainsString("t('colony.{$key}'", $dialog, $key);
            $this->assertNotSame("colony.{$key}", __("colony.{$key}"), $key);
        }
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

        $buildings = collect($this->actingAs(User::where('user_id', self::BART_USER_ID)->firstOrFail())
            ->getJson(route('colony.view'))->assertOk()->json('buildings'));

        $harvester = $buildings->first(fn ($b) => (int) $b['building_id'] === 27 && (int) $b['instance_id'] === 1);
        $this->assertSame(1, (int) $harvester['tile_x']);

        $unplaced = $buildings->first(fn ($b) => (int) $b['building_id'] !== 25 && $b['tile_x'] === null);
        $this->assertNotNull($unplaced, 'fixture holds at least one unplaced building row');
    }
}
