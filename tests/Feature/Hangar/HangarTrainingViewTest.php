<?php

namespace Tests\Feature\Hangar;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Server-markup tests for the hangar "Training" panel (T41, path depth). */
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

    private function page(): string
    {
        return $this->actingAs(User::find(3))->get(route('colony.hangar'))->assertOk()->getContent();
    }

    public function test_panel_shows_five_families_with_cost_chip(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('hangar-training', $html);
        $this->assertStringContainsString(__('pathdepth.ui_training_title'), $html);
        $this->assertStringContainsString(__('pathdepth.ui_experience_label'), $html);
        foreach (['exploration', 'salvage', 'supply', 'trade', 'protection'] as $key) {
            $this->assertStringContainsString(__("pathdepth.family_{$key}"), $html);
        }
        $this->assertStringContainsString('ap-cost-chip', $html);
        $this->assertStringContainsString(route('colony.pathdepth.invest', ['kind' => 'mission_family', 'key' => '__KEY__']), str_replace('\\/', '/', $html));
    }

    public function test_hangar_data_carries_family_states(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'salvage',
            'level' => 2, 'ap_spend' => 3,
        ]);
        $html = $this->page();

        $this->assertMatchesRegularExpression('/trainingFamilies:\s*\[/', $html);
        $this->assertStringContainsString('"key":"salvage"', $html);
        $this->assertStringContainsString('"level_cap":2', $html);
        $this->assertStringContainsString('"ap_spend":3', $html);
    }

    public function test_locked_family_names_the_reason(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'trade',
            'level' => 2, 'ap_spend' => 0,
        ]);
        $html = $this->page();

        $this->assertStringContainsString(
            json_encode(__('pathdepth.ui_locked_building', ['building' => __('buildings.hangar'), 'level' => 2]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            str_replace('\/', '/', $html)
        );
    }

    public function test_panel_texts_follow_locale(): void
    {
        app()->setLocale('de');
        $de = $this->page();
        app()->setLocale('en');
        $en = $this->page();

        $this->assertStringContainsString('hangar-training', $de);
        $this->assertStringContainsString(trans('pathdepth.ui_experience_label', [], 'de'), $de);
        $this->assertStringContainsString(trans('pathdepth.ui_experience_label', [], 'en'), $en);
        $this->assertStringNotContainsString(trans('pathdepth.ui_experience_label', [], 'de'), $en);
    }

    public function test_button_label_is_train(): void
    {
        $html = $this->page();

        $this->assertSame('Trainieren', trans('pathdepth.ui_train', [], 'de'));
        $this->assertStringContainsString(substr(json_encode(__('pathdepth.ui_train'), 15), 1, -1), $html);
    }
}
