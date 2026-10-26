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

/** T43-G2 (per-Sol AP limit per family) and T43-G3 (family unlock by hangar level). */
class TrainingLimitTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private PathDepthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->instance(TickService::class, new TickService(10));
        $this->service = $this->app->make(PathDepthService::class);
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_daily_ap')->where('colony_id', self::COLONY_ID)->delete();
        $this->setHangar(3);
        config(['game.bypass.ap_checks' => false, 'path_depth.training_ap_cap_per_sol.base' => 4]);
    }

    private function setHangar(int $level): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 44, 'instance_id' => 1],
            ['level' => $level, 'status_points' => 20.0, 'ap_spend' => 0]
        );
    }

    private function assertRejected(string $code, callable $fn): void
    {
        try {
            $fn();
            $this->fail("expected GameRuleException {$code}");
        } catch (GameRuleException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    private function lockedAp(): int
    {
        return (int) DB::table('locked_actionpoints')->where('scope_id', self::COLONY_ID)->sum('spend_ap');
    }

    public function test_family_takes_only_cap_ap_per_sol(): void
    {
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 4);
        $locked = $this->lockedAp();

        $this->assertRejected('training_ap_cap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 1));
        $this->assertSame($locked, $this->lockedAp());
    }

    public function test_cap_is_per_family(): void
    {
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 4);
        $result = $this->service->invest(self::COLONY_ID, 'mission_family', 'salvage', 1);

        $this->assertSame(1, $result['ap_spend']);
    }

    public function test_request_above_remaining_cap_is_rejected_whole(): void
    {
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 3);

        $this->assertRejected('training_ap_cap', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 2));
    }

    public function test_cap_resets_next_sol(): void
    {
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 4);
        $this->app->instance(TickService::class, new TickService(11));
        $service = $this->app->make(PathDepthService::class);

        $this->assertSame(5, $service->invest(self::COLONY_ID, 'mission_family', 'exploration', 1)['ap_spend']);
    }

    public function test_null_cap_switches_rule_off(): void
    {
        config(['path_depth.training_ap_cap_per_sol.base' => null]);

        $this->assertTrue($this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 6)['leveled_up']);
        $this->assertNull($this->service->state(self::COLONY_ID, 'mission_family', 'exploration')['ap_cap']);
    }

    public function test_state_carries_cap_state(): void
    {
        $this->service->invest(self::COLONY_ID, 'mission_family', 'exploration', 3);

        $this->assertSame(
            ['cap' => 4, 'spent' => 3, 'remaining' => 1],
            $this->service->state(self::COLONY_ID, 'mission_family', 'exploration')['ap_cap']
        );
    }

    public function test_characters_have_no_training_cap(): void
    {
        $state = $this->service->state(self::COLONY_ID, 'character', 'bartender');

        $this->assertNull($state['ap_cap']);
    }

    public function test_families_unlock_by_hangar_level(): void
    {
        $this->setHangar(1);

        $this->assertSame(1, $this->service->state(self::COLONY_ID, 'mission_family', 'exploration')['unlock_level']);
        $this->assertNull($this->service->state(self::COLONY_ID, 'mission_family', 'exploration')['locked_until']);
        $this->service->invest(self::COLONY_ID, 'mission_family', 'salvage', 1);

        foreach (['supply' => 2, 'trade' => 2, 'protection' => 3] as $family => $level) {
            $this->assertSame($level, $this->service->state(self::COLONY_ID, 'mission_family', $family)['locked_until']);
            $this->assertRejected('training_locked', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', $family, 1));
        }
        $this->assertSame(1, $this->lockedAp());
    }

    public function test_family_opens_when_hangar_reaches_required_level(): void
    {
        $this->setHangar(2);
        $this->service->invest(self::COLONY_ID, 'mission_family', 'trade', 1);
        $this->assertRejected('training_locked', fn () => $this->service->invest(self::COLONY_ID, 'mission_family', 'protection', 1));

        $this->setHangar(3);
        $this->assertSame(1, $this->service->invest(self::COLONY_ID, 'mission_family', 'protection', 1)['ap_spend']);
    }

    public function test_unlock_mapping_covers_every_family(): void
    {
        $this->assertEqualsCanonicalizing(
            array_keys(config('path_depth.families')),
            array_keys(config('path_depth.family_unlock_hangar_level'))
        );
    }

    public function test_controller_returns_422_contract_for_cap_and_lock(): void
    {
        $user = User::find(3);
        $url = fn (string $key) => route('colony.pathdepth.invest', ['kind' => 'mission_family', 'key' => $key]);
        $this->actingAs($user)->postJson($url('exploration'), ['ap' => 4])->assertOk()
            ->assertJsonPath('ap_cap.remaining', 0);

        $this->actingAs($user)->postJson($url('exploration'), ['ap' => 1])->assertStatus(422)
            ->assertJsonPath('ok', false)->assertJsonPath('error', 'training_ap_cap')
            ->assertJsonStructure(['message']);

        $this->setHangar(1);
        $this->actingAs($user)->postJson($url('protection'), ['ap' => 1])->assertStatus(422)
            ->assertJsonPath('error', 'training_locked');
    }

    public function test_hangar_page_shows_cap_and_lock_reason(): void
    {
        $this->setHangar(1);
        $html = $this->actingAs(User::find(3))->get(route('colony.hangar'))->assertOk()->getContent();
        $flat = str_replace('\/', '/', $html);

        $this->assertStringContainsString('"locked_until":3', $html);
        $this->assertStringContainsString(
            json_encode(__('pathdepth.ui_locked_building', ['building' => __('buildings.hangar'), 'level' => 3]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $flat
        );
        $this->assertStringContainsString('"ap_cap":{"cap":4', $html);
        $this->assertStringContainsString('hangar-training-hint', $html);
        $this->assertStringContainsString(__('pathdepth.ui_training_ap_today'), $flat);
    }

    public function test_texts_exist_in_both_languages(): void
    {
        foreach (['de', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach (['error_training_ap_cap', 'error_training_locked', 'ui_training_ap_today', 'ui_training_ap_cap_tooltip'] as $key) {
                $this->assertNotSame("pathdepth.{$key}", __("pathdepth.{$key}"), "{$locale}:{$key}");
            }
        }
    }
}
