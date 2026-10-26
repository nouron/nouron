<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Server-markup tests for the cantina "Beziehung pflegen" panel (T41, path depth). */
class BarRelationshipViewTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(100));
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0]
        );
    }

    private function page(): string
    {
        return $this->actingAs(User::find(3))->get(route('colony.bar'))->assertOk()->getContent();
    }

    private function met(string $slug, int $level = 0, int $apSpend = 0): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => $slug,
            'level' => $level, 'ap_spend' => $apSpend,
        ]);
    }

    private function jsonText(string $s): string
    {
        return substr(json_encode($s, 15), 1, -1);
    }

    public function test_only_bartender_listed_when_nobody_met(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('bar-relationships', $html);
        $this->assertStringContainsString(__('pathdepth.ui_relationship_title'), $html);
        $this->assertStringContainsString('data-character="bartender"', $html);
        $this->assertSame(1, substr_count($html, 'data-character="'));
    }

    public function test_met_characters_are_listed_and_unmet_are_not(): void
    {
        $this->met('smuggler');
        $html = $this->page();

        $this->assertStringContainsString('data-character="smuggler"', $html);
        $this->assertStringContainsString(config('characters.smuggler.name'), $html);
        $this->assertStringNotContainsString('data-character="veteran"', $html);
        $this->assertSame(2, substr_count($html, 'data-character="'));
    }

    public function test_row_has_cost_chip_and_route(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('ap-cost-chip', $html);
        $this->assertStringContainsString(
            route('colony.pathdepth.invest', ['kind' => 'character', 'key' => '__KEY__']),
            str_replace('\\/', '/', $html)
        );
    }

    public function test_state_is_carried_for_the_row(): void
    {
        $this->met('smuggler', 1, 3);
        $html = $this->page();

        $this->assertStringContainsString('"ap_spend":3', $html);
        $this->assertStringContainsString('"level_cap":2', $html);
    }

    public function test_building_cap_names_cantina_as_reason(): void
    {
        $this->met('smuggler', 2);
        $html = $this->page();

        $reason = __('pathdepth.ui_locked_building', ['building' => __('buildings.bar'), 'level' => 2]);
        $this->assertStringContainsString($this->jsonText($reason), $html);
    }

    public function test_run_and_overall_labels_are_separated_and_codex_page_unchanged(): void
    {
        $html = $this->page();
        $this->assertStringContainsString(__('pathdepth.ui_relationship_run_hint'), $html);

        $codex = $this->actingAs(User::find(3))->get(route('user.codex'))->assertOk()->getContent();
        $this->assertStringNotContainsString('bar-relationships', $codex);
        $this->assertStringContainsString(__('colony.codex_page_intro'), $codex);
    }

    public function test_texts_follow_locale(): void
    {
        app()->setLocale('en');
        $en = $this->page();
        $this->assertStringContainsString(trans('pathdepth.ui_relationship_title', [], 'en'), $en);
        $this->assertStringNotContainsString(trans('pathdepth.ui_relationship_title', [], 'de'), $en);
    }

    public function test_button_label_is_get_to_know_and_hint_has_no_restart_clause(): void
    {
        $html = $this->page();

        $this->assertStringContainsString($this->jsonText(__('pathdepth.ui_get_to_know')), $html);
        $this->assertSame('Kennenlernen', trans('pathdepth.ui_get_to_know', [], 'de'));
        $this->assertStringNotContainsString('von vorn', __('pathdepth.ui_relationship_run_hint'));
        $this->assertStringNotContainsString('starts over', trans('pathdepth.ui_relationship_run_hint', [], 'en'));
    }

    public function test_conversation_options_replace_the_single_invest_button(): void
    {
        $this->met('smuggler', 0, 1);
        $html = $this->page();

        // Options travel with the row (names from the pool, effective AP cost, configured cost to send).
        $this->assertStringContainsString('"conversation":{"cap":{"cap":'.config('path_depth.conversation_ap_cap_per_sol.base'), $html);
        foreach (['short', 'normal', 'long'] as $key) {
            $this->assertStringContainsString('"key":"'.$key.'"', $html);
        }
        $this->assertStringContainsString('x-for="opt in rel.conversation.options"', $html);
        $this->assertStringNotContainsString('investRelation(rel)', $html);
        $this->assertStringContainsString(
            $this->jsonText(__('pathdepth.ui_conversation_cap_tooltip')),
            $html
        );
    }

    public function test_option_costs_are_clamped_to_the_rest_of_the_level(): void
    {
        config(['path_depth.conversation_options' => ['short' => 1, 'normal' => 2, 'long' => 10]]);
        $this->met('smuggler', 0, 1);
        $html = $this->page();

        // level 1 costs 4, 1 spent -> the long option shows 3 AP but still sends cost 10
        $this->assertStringContainsString('"ap":3,"cost":10,"capped":false', $html);
    }
}
