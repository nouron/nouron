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
 * Cantina conversation variant C (spec 2026-11-13-cantina-gespraechsoptionen-gefuehl, AP numbers
 * from 2026-11-13-ap-skala §11.4): one conversation per character and Sol, the player picks the
 * length; an option always costs its full price (no clamping), the surplus over the next
 * relationship level is lost and reported as `wasted`.
 */
class ConversationVariantCTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private PathDepthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->tickAt(10);
        DB::table('colony_path_levels')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_daily_ap')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20.0, 'ap_spend' => 0]
        );
        config([
            'game.bypass.ap_checks' => false,
            'path_depth.levelup_ap.character' => [1 => 1, 2 => 3, 3 => 3, 4 => 3, 5 => 3],
            'path_depth.conversation_options' => ['short' => 1, 'long' => 2],
            'path_depth.conversation_option_cantina_level' => ['short' => 1, 'long' => 3],
            'path_depth.conversation_ap_cap_per_sol' => ['base' => 2],
        ]);
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

    private function setLevel(int $level, int $apSpend): void
    {
        DB::table('colony_path_levels')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'kind' => 'character', 'entity_key' => 'bartender'],
            ['level' => $level, 'ap_spend' => $apSpend]
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

    public function test_second_conversation_in_the_same_sol_is_refused(): void
    {
        $this->setLevel(1, 0);
        $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1);

        $this->assertRejected('conversation_done_today', fn () => $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1));
        $this->assertSame(1, $this->lockedAp());
    }

    public function test_character_can_be_talked_to_again_next_sol(): void
    {
        $this->setLevel(1, 0);
        $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1);
        $this->tickAt(11);

        $result = $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2);

        $this->assertTrue($result['leveled_up']);
        $this->assertSame(2, $result['level']);
        $this->assertSame(3, $this->lockedAp());
    }

    public function test_option_costs_full_price_and_surplus_is_wasted(): void
    {
        // Level 0 -> 1 needs 1 AP; the 2-AP conversation charges 2, 1 is lost.
        $result = $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2);

        $this->assertSame(2, $this->lockedAp());
        $this->assertSame(2, $result['points']);
        $this->assertTrue($result['leveled_up']);
        $this->assertSame(1, $result['level']);
        $this->assertSame(0, $result['ap_spend']);
        $this->assertSame(1, $result['wasted']);
    }

    public function test_exact_conversation_wastes_nothing(): void
    {
        $this->setLevel(1, 1);
        $result = $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2);

        $this->assertTrue($result['leveled_up']);
        $this->assertSame(0, $result['wasted']);
    }

    public function test_options_report_full_cost_and_wasted_ap(): void
    {
        $this->setLevel(1, 2);
        $conv = $this->service->conversation(self::COLONY_ID, 'bartender');

        $this->assertSame(['short', 'long'], array_column($conv['options'], 'key'));
        $this->assertSame([1, 2], array_column($conv['options'], 'ap'));
        $this->assertSame([0, 1], array_column($conv['options'], 'wasted'));
        $this->assertSame([false, false], array_column($conv['options'], 'capped'));
        $this->assertFalse($conv['talked_today']);
        $this->assertSame(1, $conv['to_next_level']);
    }

    public function test_options_are_not_selectable_after_todays_conversation(): void
    {
        $this->setLevel(1, 0);
        $this->service->invest(self::COLONY_ID, 'character', 'bartender', 1);
        $conv = $this->service->conversation(self::COLONY_ID, 'bartender');

        $this->assertTrue($conv['talked_today']);
        $this->assertSame([true, true], array_column($conv['options'], 'capped'));
    }

    public function test_option_above_the_characters_sol_limit_is_not_selectable(): void
    {
        config(['path_depth.conversation_ap_cap_per_sol' => ['base' => 1]]);
        $this->setLevel(1, 0);

        $conv = $this->service->conversation(self::COLONY_ID, 'bartender');
        $this->assertSame([false, true], array_column($conv['options'], 'capped'));
        $this->assertRejected('conversation_ap_cap', fn () => $this->service->invest(self::COLONY_ID, 'character', 'bartender', 2));
        $this->assertSame(0, $this->lockedAp());
    }

    public function test_invest_endpoint_reports_wasted_ap(): void
    {
        $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson('/api/v1/colony/path-depth/character/bartender/invest', ['ap' => 2])
            ->assertOk()
            ->assertJsonPath('wasted', 1)
            ->assertJsonPath('conversation.talked_today', true);
    }
}
