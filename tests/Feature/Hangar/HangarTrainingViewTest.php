<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Hangar "Training" panel (T41, path depth): the family rows from the Hangar JSON
 * (HangarController::data()) and their rendering in the Svelte page
 * frontend/src/routes/(game)/hangar/+page.svelte.
 */
class HangarTrainingViewTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    private function data(): array
    {
        return $this->actingAs(User::find(3))->getJson(route('colony.hangar'))->assertOk()->json();
    }

    /** @return array<string, array<string, mixed>> training rows keyed by family */
    private function families(): array
    {
        return collect($this->data()['trainingFamilies'])->keyBy('key')->all();
    }

    private function hangarPage(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/hangar/+page.svelte'));
    }

    public function test_panel_shows_five_families_with_cost_chip(): void
    {
        $families = $this->families();

        $this->assertSame(['exploration', 'salvage', 'supply', 'trade', 'protection'], array_keys($families));
        foreach ($families as $key => $row) {
            $this->assertSame(__("pathdepth.family_{$key}"), $row['name']);
        }

        $html = $this->hangarPage();
        $this->assertStringContainsString('<section class="hangar-training">', $html);
        $this->assertStringContainsString("t('pathdepth.ui_training_title')", $html);
        $this->assertStringContainsString("t('pathdepth.ui_experience_label')", $html);
        // The Trainieren button is the shared AP button, which carries the cost chip.
        $this->assertStringContainsString('<ApUpgradeButton', $html);
        $this->assertStringContainsString('ap-cost-chip', file_get_contents(base_path('frontend/src/lib/components/ApUpgradeButton.svelte')));
        $this->assertStringEndsWith(
            '/colony/path-depth/mission_family/__KEY__/invest',
            route('colony.pathdepth.invest', ['kind' => 'mission_family', 'key' => '__KEY__'])
        );
        $this->assertStringContainsString('`/colony/path-depth/mission_family/${fam.key}/invest`', $html);
    }

    public function test_hangar_data_carries_family_states(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'salvage',
            'level' => 2, 'ap_spend' => 3,
        ]);
        $salvage = $this->families()['salvage'];

        $this->assertSame(2, $salvage['level_cap']);
        $this->assertSame(3, $salvage['ap_spend']);
    }

    public function test_locked_family_names_the_reason(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'trade',
            'level' => 2, 'ap_spend' => 0,
        ]);

        $this->assertSame(
            __('pathdepth.ui_locked_building', ['building' => __('buildings.hangar'), 'level' => 2]),
            $this->families()['trade']['lock_reason']
        );
    }

    public function test_panel_texts_follow_locale(): void
    {
        // The Svelte page reads its texts from the locale endpoint (lang/{lang}/*.php).
        $this->getJson('/api/v1/locale/de')->assertOk()
            ->assertJsonPath('pathdepth.ui_experience_label', trans('pathdepth.ui_experience_label', [], 'de'));
        $this->getJson('/api/v1/locale/en')->assertOk()
            ->assertJsonPath('pathdepth.ui_experience_label', trans('pathdepth.ui_experience_label', [], 'en'));
        $this->assertNotSame(trans('pathdepth.ui_experience_label', [], 'de'), trans('pathdepth.ui_experience_label', [], 'en'));
    }

    public function test_button_label_is_train(): void
    {
        $this->assertSame('Trainieren', trans('pathdepth.ui_train', [], 'de'));
        $this->assertStringContainsString("label={t('pathdepth.ui_train')}", $this->hangarPage());
    }

    public function test_panel_sits_above_the_bays(): void
    {
        $html = $this->hangarPage();

        $training = strpos($html, '<section class="hangar-training">');
        $this->assertNotFalse($training);
        $this->assertLessThan(strpos($html, '<div class="carousel-wrapper">'), $training);
    }

    public function test_family_rows_carry_effect_numbers_from_config(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'salvage',
            'level' => 2, 'ap_spend' => 0,
        ]);
        $salvage = $this->families()['salvage'];
        $per = (float) config('path_depth.yield_per_level');

        $this->assertEquals(round($per * 2 * 100), $salvage['yield_now_pct']);
        $this->assertEquals(round($per * 3 * 100), $salvage['yield_next_pct']);
        $this->assertSame(1, $salvage['unlock_level']);
        $this->assertContains(__('missions.mission_salvage_sweep_name'), $salvage['missions']);
        $this->assertStringContainsString("t('pathdepth.ui_training_effect', { pct: fam.yield_per_level_pct })", $this->hangarPage());
    }

    public function test_mission_dialog_shows_family_pill(): void
    {
        $salvage = collect($this->data()['missionCatalog'])->firstWhere('family', 'salvage');
        $this->assertNotNull($salvage, 'catalog entries carry their training family');
        $this->assertSame(__('pathdepth.family_salvage'), $salvage['family_name']);
        $this->assertArrayHasKey('family_level', $salvage);

        $html = $this->hangarPage();

        $this->assertStringContainsString('<span class="hangar-mission-family">', $html);
        $this->assertStringContainsString("t('missions.family_pill'", $html);
    }
}
