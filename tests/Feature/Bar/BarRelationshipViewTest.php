<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cantina "Beziehung pflegen" panel (T41, path depth): the relationship rows from the
 * Cantina JSON (BarController::data()) and their rendering in the Svelte page
 * frontend/src/routes/(game)/cantina/+page.svelte.
 */
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

    private function data(): array
    {
        return $this->actingAs(User::find(3))->getJson(route('colony.bar'))->assertOk()->json();
    }

    private function cantinaPage(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/cantina/+page.svelte'));
    }

    /** @return array<string, array<string, mixed>> relationship rows keyed by character slug */
    private function relationships(): array
    {
        return collect($this->data()['relationships'])->keyBy('key')->all();
    }

    /** Nobody but Tomas sits in the cantina today: present guests are listed even unmet (owner 2026-11-13). */
    private function emptyCantina(): void
    {
        foreach (['bar_offers', 'bar_encounters', 'bar_concerns', 'bar_information_encounters'] as $table) {
            DB::table($table)->where('colony_id', self::COLONY_ID)->delete();
        }
        config(['game.bar.story_encounter.chance' => 0.0]);
    }

    private function met(string $slug, int $level = 0, int $apSpend = 0): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => $slug,
            'level' => $level, 'ap_spend' => $apSpend,
        ]);
    }

    public function test_bartender_stands_behind_the_counter_inside_the_picture_not_below_it(): void
    {
        $html = $this->cantinaPage();

        $viewport = strpos($html, 'class="cantina-viewport"');
        $counter = strpos($html, 'data-bartender-counter');
        $panel = strpos($html, '<section class="bar-relationships">');

        $this->assertNotFalse($counter);
        $this->assertGreaterThan($viewport, $counter);
        $this->assertLessThan($panel, $counter);
        // The counter sits before the swipe dots, i.e. still inside the viewport.
        $this->assertLessThan(strpos($html, 'swipe-dots'), $counter);
        $this->assertSame(1, substr_count($html, "open('bartender');"));
        $this->assertStringContainsString("t('colony.bartender_counter_role')", $html);
        // His conversation options stay reachable in the relationship panel.
        $this->assertArrayHasKey('bartender', $this->relationships());
    }

    public function test_only_bartender_listed_when_nobody_met(): void
    {
        $this->emptyCantina();
        $this->assertSame(['bartender'], array_keys($this->relationships()));

        $html = $this->cantinaPage();
        $this->assertStringContainsString("t('pathdepth.ui_relationship_title')", $html);
        $this->assertStringContainsString('{#each data.relationships.filter((r) => r.met) as rel (rel.key)}', $html);
        $this->assertStringContainsString('data-character={rel.key}', $html);
    }

    public function test_met_characters_are_listed_and_unmet_are_not(): void
    {
        $this->emptyCantina();
        $this->met('smuggler');
        $rows = $this->relationships();

        $this->assertArrayHasKey('smuggler', $rows);
        $this->assertSame(config('characters.smuggler.name'), $rows['smuggler']['name']);
        $this->assertArrayNotHasKey('veteran', $rows);
        $this->assertCount(2, $rows);
    }

    public function test_row_has_cost_chip_and_route(): void
    {
        $html = $this->cantinaPage();

        $this->assertStringContainsString('<span class="ap-chip ap-cost-chip ap-chip--build" aria-hidden="true">{opt.ap} AP</span>', $html);
        $this->assertStringEndsWith(
            '/colony/path-depth/character/__KEY__/invest',
            route('colony.pathdepth.invest', ['kind' => 'character', 'key' => '__KEY__'])
        );
        $this->assertStringContainsString('`/colony/path-depth/character/${rel.key}/invest`', $html);
    }

    public function test_state_is_carried_for_the_row(): void
    {
        $this->met('smuggler', 1, 3);
        $row = $this->relationships()['smuggler'];

        $this->assertSame(3, $row['ap_spend']);
        // Characters have no cantina level gate any more (owner 2026-11-13).
        $this->assertSame((int) config('path_depth.max_level'), $row['level_cap']);
    }

    public function test_small_cantina_locks_options_not_the_relationship(): void
    {
        $this->met('smuggler', 2);
        $row = $this->relationships()['smuggler'];

        $this->assertArrayNotHasKey('lock_reason', $row);
        $options = collect($row['conversation']['options'])->keyBy('key');
        $this->assertTrue($options['short']['available']);
        $this->assertFalse($options['long']['available']);
        $this->assertNotNull($options['long']['locked_line']);
    }

    public function test_run_and_overall_labels_are_separated_and_codex_page_unchanged(): void
    {
        $this->assertStringContainsString("t('pathdepth.ui_relationship_run_hint')", $this->cantinaPage());

        $this->actingAs(User::find(3))->getJson(route('user.codex'))->assertOk()->assertJsonStructure(['characters']);
        $codex = file_get_contents(base_path('frontend/src/routes/(infra)/codex/+page.svelte'));
        $this->assertStringNotContainsString('bar-relationships', $codex);
        $this->assertStringContainsString("t('colony.codex_page_intro')", $codex);
    }

    public function test_texts_follow_locale(): void
    {
        // The Svelte page reads its texts from the locale endpoint (lang/{lang}/*.php).
        $en = $this->getJson('/api/v1/locale/en')->assertOk();
        $de = $this->getJson('/api/v1/locale/de')->assertOk();

        $en->assertJsonPath('pathdepth.ui_relationship_title', trans('pathdepth.ui_relationship_title', [], 'en'));
        $de->assertJsonPath('pathdepth.ui_relationship_title', trans('pathdepth.ui_relationship_title', [], 'de'));
        $this->assertNotSame(trans('pathdepth.ui_relationship_title', [], 'en'), trans('pathdepth.ui_relationship_title', [], 'de'));
    }

    public function test_button_label_is_get_to_know_and_hint_has_no_restart_clause(): void
    {
        $this->assertStringContainsString("t('pathdepth.ui_get_to_know')", $this->cantinaPage());
        $this->assertSame('Kennenlernen', trans('pathdepth.ui_get_to_know', [], 'de'));
        $this->assertStringNotContainsString('von vorn', __('pathdepth.ui_relationship_run_hint'));
        $this->assertStringNotContainsString('starts over', trans('pathdepth.ui_relationship_run_hint', [], 'en'));
    }

    public function test_conversation_options_replace_the_single_invest_button(): void
    {
        $this->met('smuggler', 1, 1);
        $row = $this->relationships()['smuggler'];

        // Options travel with the row (names from the pool, effective AP cost, configured cost to send).
        $this->assertSame((int) config('path_depth.conversation_ap_cap_per_sol.base'), $row['conversation']['cap']['cap']);
        $this->assertSame(array_keys(config('path_depth.conversation_options')), array_column($row['conversation']['options'], 'key'));

        $html = $this->cantinaPage();
        $this->assertStringContainsString('{#each rel.conversation.options as opt (opt.key)}', $html);
        $this->assertStringContainsString('investRelation(rel, opt)', $html);
        $this->assertStringNotContainsString('investRelation(rel)', $html);
        $this->assertStringContainsString("t('pathdepth.ui_conversation_cap_tooltip')", $html);
    }

    public function test_option_costs_are_full_price_with_wasted_ap(): void
    {
        // Variant C (2026-11-13): no clamping, the surplus over the level is reported as wasted.
        config(['path_depth.conversation_options' => ['short' => 1, 'long' => 2], 'path_depth.levelup_ap.character.2' => 3]);
        $this->met('smuggler', 1, 2);
        $long = collect($this->relationships()['smuggler']['conversation']['options'])->firstWhere('key', 'long');

        $this->assertSame(2, $long['ap']);
        $this->assertSame(2, $long['cost']);
        $this->assertSame(1, $long['wasted']);
    }
}
