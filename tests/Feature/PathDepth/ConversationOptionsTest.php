<?php

namespace Tests\Feature\PathDepth;

use App\Models\User;
use App\Services\PathDepthService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T43-G4: cantina conversation options with different AP costs and a per-Sol AP limit per
 * character; since variant C (2026-11-13) one conversation per character and Sol, see
 * ConversationVariantCTest for the full-price and wasted-AP rules.
 */
class ConversationOptionsTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private PathDepthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20.0, 'ap_spend' => 0]
        );
        config([
            'game.bypass.ap_checks' => false,
            'path_depth.conversation_options' => ['short' => 1, 'long' => 2],
            'path_depth.conversation_option_cantina_level' => ['short' => 1, 'long' => 3],
            'path_depth.conversation_ap_cap_per_sol' => ['base' => 2],
        ]);
        $this->service = $this->app->make(PathDepthService::class);
    }

    private function tickAt(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
        $this->service = $this->app->make(PathDepthService::class);
    }

    private function lockedAp(): int
    {
        return (int) DB::table('locked_actionpoints')->where('scope_id', self::COLONY_ID)->sum('spend_ap');
    }

    private function meet(string $slug): void
    {
        $this->service->markMet(self::COLONY_ID, $slug);
    }

    public function test_cap_is_per_character(): void
    {
        $this->meet('smuggler');
        $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2);

        $this->assertSame(1, $this->service->invest(self::COLONY_ID, 'character', 'smuggler', 1)['level']);
    }

    public function test_null_cap_switches_rule_off(): void
    {
        config([
            'path_depth.conversation_ap_cap_per_sol' => ['base' => null],
            'path_depth.conversation_options' => ['short' => 1, 'long' => 4],
        ]);
        $this->service->invest(self::COLONY_ID, 'character', 'bartender', 4);

        $this->assertNull($this->service->conversation(self::COLONY_ID, 'bartender')['cap']);
        $this->assertSame(4, $this->lockedAp());
    }

    public function test_family_training_is_not_affected_by_conversation_cap(): void
    {
        config(['path_depth.training_ap_cap_per_sol' => ['base' => null]]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20.0, 'ap_spend' => 0]
        );
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 5);

        // Families are still clamped to the rest of the level (only conversations charge full price).
        $this->assertSame(min(5, (int) config('path_depth.levelup_ap.mission_family.1')), $this->lockedAp());
    }

    public function test_no_options_at_max_level(): void
    {
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => 'bartender',
            'level' => 5, 'ap_spend' => 0,
        ]);

        $this->assertSame([], $this->service->conversation(self::COLONY_ID, 'bartender')['options']);
    }

    public function test_option_names_come_from_pool_deterministically_and_rotate(): void
    {
        $names = [];
        foreach (range(10, 40) as $tick) {
            $this->tickAt($tick);
            $first = $this->service->conversation(self::COLONY_ID, 'bartender')['options'][0]['name'];
            $this->assertSame($first, $this->service->conversation(self::COLONY_ID, 'bartender')['options'][0]['name']);
            $names[$first] = true;
        }

        $pool = trans('pathdepth.conversation_names.short');
        $this->assertIsArray($pool);
        $this->assertGreaterThanOrEqual(3, count($pool));
        $this->assertGreaterThan(1, count($names));
        $this->assertSame([], array_diff(array_keys($names), $pool));
    }

    public function test_texts_exist_in_de_and_en(): void
    {
        foreach (['de', 'en'] as $locale) {
            $this->assertNotSame('pathdepth.error_conversation_ap_cap', __('pathdepth.error_conversation_ap_cap', [], $locale));
            $this->assertNotSame('pathdepth.ui_conversation_cap_tooltip', __('pathdepth.ui_conversation_cap_tooltip', [], $locale));
            foreach (array_keys(config('path_depth.conversation_options')) as $opt) {
                $pool = trans("pathdepth.conversation_names.{$opt}", [], $locale);
                $this->assertIsArray($pool, "{$opt} pool missing in {$locale}");
                $this->assertGreaterThanOrEqual(3, count($pool));
            }
        }
    }

    public function test_controller_refuses_a_second_conversation_and_returns_state(): void
    {
        $user = User::find(3);
        $url = route('colony.pathdepth.invest', ['kind' => 'character', 'key' => 'bartender']);

        $this->actingAs($user)->postJson($url, ['ap' => 2])->assertOk()
            ->assertJsonPath('conversation.cap.remaining', 0)
            ->assertJsonPath('conversation.talked_today', true)
            ->assertJsonPath('conversation.options.0.key', 'short');

        $this->actingAs($user)->postJson($url, ['ap' => 1])->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'conversation_done_today'])
            ->assertJsonStructure(['message']);
    }

    public function test_controller_only_accepts_configured_option_costs_for_characters(): void
    {
        $url = route('colony.pathdepth.invest', ['kind' => 'character', 'key' => 'bartender']);

        $this->actingAs(User::find(3))->postJson($url, ['ap' => 3])->assertStatus(422);
        $this->assertSame(0, $this->lockedAp());
    }
}
