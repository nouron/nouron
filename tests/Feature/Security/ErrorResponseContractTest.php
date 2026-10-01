<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T25 — AJAX error contract (docs/frontend-conventions.md, T11): `error` is a
 * stable snake_case machine code, `message` is the translated player text.
 * Before, these endpoints put raw exception text or hard-coded German into
 * `error` (internal details leaked, no translation).
 *
 * Fixture: Bart (user 3) → colony 1 (TestSeeder).
 */
class ErrorResponseContractTest extends TestCase
{
    use RefreshDatabase;

    private const HANGAR_BUILDING = 44;

    private User $bart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->bart = User::findOrFail(3);

        DB::table('colony_hangar_missions')->where('colony_id', 1)->delete();
        DB::table('colony_ships')->where('colony_id', 1)->delete();
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', self::HANGAR_BUILDING)->delete();
        DB::table('colony_buildings')->insert([
            'colony_id' => 1, 'building_id' => self::HANGAR_BUILDING, 'instance_id' => 1,
            'level' => 3, 'status_points' => 20, 'ap_spend' => 0,
        ]);
    }

    private function assertErrorContract($response, string $code, string $langKey): void
    {
        $response->assertJsonPath('ok', false)
            ->assertJsonPath('error', $code)
            ->assertJsonPath('message', __($langKey));
        $this->assertMatchesRegularExpression('/^[a-z_]+$/', $response->json('error'));
    }

    // ── Hangar ─────────────────────────────────────────────────────────────

    public function test_recall_without_mission_returns_code_and_message(): void
    {
        $res = $this->actingAs($this->bart)->postJson(route('colony.hangar.recall', ['instanceId' => 1]));

        $res->assertStatus(422);
        $this->assertErrorContract($res, 'no_active_mission', 'colony.hangar_error_no_active_mission');
    }

    public function test_repair_without_ship_returns_code_and_message(): void
    {
        $res = $this->actingAs($this->bart)->postJson(route('colony.hangar.repair', ['instanceId' => 1]));

        $res->assertStatus(422);
        $this->assertErrorContract($res, 'no_docked_ship', 'colony.hangar_error_no_docked_ship');
    }

    public function test_assign_unknown_ship_returns_code_and_message(): void
    {
        $res = $this->actingAs($this->bart)->postJson(route('colony.hangar.assign'), ['ship_row_id' => 999999, 'instance_id' => 1]);

        $res->assertStatus(422);
        $this->assertErrorContract($res, 'ship_not_pending', 'colony.hangar_error_ship_not_pending');
    }

    public function test_request_without_credits_returns_code_and_message(): void
    {
        config(['game.bypass.resource_costs' => false]);
        DB::table('user_resources')->where('user_id', 3)->update(['credits' => 0]);

        $res = $this->actingAs($this->bart)->postJson(route('colony.hangar.request'), ['ship_id' => 85]);

        $res->assertStatus(422);
        $res->assertJsonPath('error', 'insufficient_credits');
        $this->assertNotSame('insufficient_credits', $res->json('message'));
        $this->assertStringNotContainsString('Insufficient credits: need', $res->json('message'));
    }

    public function test_dispatch_unknown_mission_returns_code_and_message(): void
    {
        DB::table('colony_ships')->insert([
            'colony_id' => 1, 'ship_id' => 85, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0,
            'hangar_instance_id' => 1, 'ship_state' => 'docked',
        ]);

        $res = $this->actingAs($this->bart)->postJson(route('colony.hangar.dispatch', ['instanceId' => 1]), [
            'mission_key' => 'mission_does_not_exist',
            'difficulty' => 'normal',
        ]);

        $res->assertStatus(422);
        $this->assertErrorContract($res, 'unknown_mission', 'colony.hangar_error_unknown_mission');
    }

    // ── Merchant ───────────────────────────────────────────────────────────

    public function test_merchant_buy_unknown_item_returns_code_and_message(): void
    {
        $res = $this->actingAs($this->bart)->postJson(route('colony.merchant.buy', ['itemId' => 999999]));

        $res->assertStatus(422);
        $this->assertErrorContract($res, 'merchant_item_not_found', 'colony.merchant_error_item_not_found');
    }

    // ── Advisors ───────────────────────────────────────────────────────────

    public function test_firing_unknown_advisor_returns_code_and_message(): void
    {
        $res = $this->actingAs($this->bart)->deleteJson(route('advisors.fire', ['id' => 999999]));

        $res->assertNotFound();
        $this->assertErrorContract($res, 'advisor_not_found', 'advisors.error_not_found');
    }
}
