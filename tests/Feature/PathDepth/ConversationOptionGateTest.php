<?php

namespace Tests\Feature\PathDepth;

use App\Exceptions\GameRuleException;
use App\Models\User;
use App\Services\PathDepthService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Owner decision 2026-11-13: cantina characters have no relationship-level gate by
 * cantina level any more. Instead the conversation options are staged by cantina level
 * (path_depth.conversation_option_cantina_level). Hangar mission families keep building_gate.
 */
class ConversationOptionGateTest extends TestCase
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
        config([
            'game.bypass.ap_checks' => false,
            'path_depth.conversation_options' => ['short' => 1, 'normal' => 2, 'long' => 4],
            'path_depth.conversation_option_cantina_level' => ['short' => 1, 'normal' => 2, 'long' => 3],
            'path_depth.conversation_ap_cap_per_sol' => ['base' => null],
            // Mechanism test with fixed level costs (independent of the AP scale values).
            'path_depth.levelup_ap.character' => [1 => 4, 2 => 6, 3 => 8, 4 => 10, 5 => 12],
        ]);
        $this->cantina(1);
        $this->service = $this->app->make(PathDepthService::class);
    }

    private function cantina(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20.0, 'ap_spend' => 0]
        );
    }

    private function relation(string $slug, int $level, int $apSpend = 0): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => $slug],
            ['level' => $level, 'ap_spend' => $apSpend]
        );
    }

    private function lockedAp(): int
    {
        return (int) DB::table('locked_actionpoints')->where('scope_id', self::COLONY_ID)->sum('spend_ap');
    }

    private function assertOptionLocked(int $points, int $required): void
    {
        try {
            $this->service->invest(self::COLONY_ID, 'character', 'bartender', $points);
            $this->fail('expected conversation_option_locked');
        } catch (GameRuleException $e) {
            $this->assertSame('conversation_option_locked', $e->errorCode);
            $this->assertStringContainsString((string) $required, $e->getMessage());
        }
    }

    public function test_relationship_grows_past_the_old_gate_with_a_small_cantina(): void
    {
        // Old rule: Cantina 1 capped characters at level 2 ("Gesperrt: Cantina braucht Stufe 2").
        $this->relation('bartender', 2);

        $result = $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1);

        $this->assertSame(1, $result['ap_spend']);
        $this->assertSame((int) config('path_depth.max_level'), $result['level_cap']);
    }

    public function test_normal_option_needs_cantina_level_two_and_spends_nothing_when_locked(): void
    {
        $this->assertOptionLocked(2, 2);
        $this->assertSame(0, $this->lockedAp());

        $this->cantina(2);
        $this->assertSame(2, $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2)['ap_spend']);
    }

    public function test_long_option_needs_cantina_level_three(): void
    {
        $this->cantina(2);
        $this->assertOptionLocked(4, 3);

        $this->cantina(3);
        $this->assertSame(4, $this->service->invest(self::COLONY_ID, 'character', 'bartender', 4)['points']);
    }

    public function test_amount_between_options_needs_the_level_of_the_next_larger_option(): void
    {
        $this->cantina(2);
        $this->assertOptionLocked(3, 3);
    }

    public function test_without_cantina_even_the_short_option_is_locked(): void
    {
        $this->cantina(0);
        $this->assertOptionLocked(1, 1);
    }

    public function test_mission_families_keep_the_building_gate(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20.0, 'ap_spend' => 0]
        );
        DB::table('colony_path_levels')->insert([
            'colony_id' => self::COLONY_ID, 'kind' => 'mission_family', 'entity_key' => 'exploration',
            'level' => 2, 'ap_spend' => 0,
        ]);

        $this->assertSame(2, $this->service->levelCap(self::COLONY_ID, 'mission_family'));
        try {
            $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 1);
            $this->fail('expected building_level_cap');
        } catch (GameRuleException $e) {
            $this->assertSame('building_level_cap', $e->errorCode);
        }
    }

    public function test_options_carry_availability_required_level_and_a_character_line(): void
    {
        $options = collect($this->service->conversation(self::COLONY_ID, 'bartender')['options'])->keyBy('key');

        $this->assertTrue($options['short']['available']);
        $this->assertSame(1, $options['short']['requires_cantina_level']);
        $this->assertNull($options['short']['locked_line']);
        $this->assertNull($options['short']['locked_line_key']);

        foreach (['normal' => 2, 'long' => 3] as $key => $required) {
            $this->assertFalse($options[$key]['available']);
            $this->assertSame($required, $options[$key]['requires_cantina_level']);
            $this->assertStringStartsWith('pathdepth.option_locked_lines.bartender.', $options[$key]['locked_line_key']);
            $this->assertSame(__($options[$key]['locked_line_key']), $options[$key]['locked_line']);
            $this->assertNotSame($options[$key]['locked_line_key'], $options[$key]['locked_line']);
        }
    }

    public function test_locked_line_is_stable_within_a_sol_and_varies_across_sols(): void
    {
        $lines = [];
        foreach (range(10, 30) as $tick) {
            $this->app->instance(TickService::class, new TickService($tick));
            $first = collect($this->service->conversation(self::COLONY_ID, 'bartender')['options'])->firstWhere('key', 'normal');
            $again = collect($this->service->conversation(self::COLONY_ID, 'bartender')['options'])->firstWhere('key', 'normal');
            $this->assertSame($first['locked_line_key'], $again['locked_line_key']);
            $lines[$first['locked_line_key']] = true;
        }

        $this->assertGreaterThan(1, count($lines));
    }

    public function test_characters_without_own_lines_fall_back_to_the_default_pool(): void
    {
        $this->assertFalse(is_array(trans('pathdepth.option_locked_lines.__nobody__')));
        $this->relation('smuggler', 0);
        $normal = collect($this->service->conversation(self::COLONY_ID, 'smuggler')['options'])->firstWhere('key', 'normal');

        $prefix = is_array(trans('pathdepth.option_locked_lines.smuggler'))
            ? 'pathdepth.option_locked_lines.smuggler.'
            : 'pathdepth.option_locked_lines.default.';
        $this->assertStringStartsWith($prefix, $normal['locked_line_key']);
        $this->assertIsArray(trans('pathdepth.option_locked_lines.default'));
    }

    public function test_controller_answers_with_locked_code_and_required_level(): void
    {
        $url = route('colony.pathdepth.invest', ['kind' => 'character', 'key' => 'bartender']);

        $this->actingAs(User::find(3))->postJson($url, ['ap' => 2])->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'conversation_option_locked', 'requires_cantina_level' => 2])
            ->assertJsonStructure(['message']);
        $this->assertSame(0, $this->lockedAp());
    }

    public function test_cantina_data_drops_the_relationship_gate(): void
    {
        $this->relation('bartender', 2);
        $data = $this->actingAs(User::find(3))->getJson(route('colony.bar'))->assertOk()->json();

        $this->assertArrayNotHasKey('relationshipGate', $data);
        $this->assertSame(['short' => 1, 'normal' => 2, 'long' => 3], $data['conversationOptionCantinaLevel']);
        $row = collect($data['relationships'])->firstWhere('key', 'bartender');
        $this->assertArrayNotHasKey('lock_reason', $row);
        $this->assertSame([true, false, false], array_column($row['conversation']['options'], 'available'));
    }

    public function test_texts_exist_in_de_and_en(): void
    {
        foreach (['de', 'en'] as $locale) {
            $this->assertNotSame('pathdepth.error_conversation_option_locked', __('pathdepth.error_conversation_option_locked', [], $locale));
            $pool = trans('pathdepth.option_locked_lines.default', [], $locale);
            $this->assertIsArray($pool, "default pool missing in {$locale}");
            $this->assertGreaterThanOrEqual(3, count($pool));
            $this->assertIsArray(trans('pathdepth.option_locked_lines.bartender', [], $locale));
        }
    }
}
