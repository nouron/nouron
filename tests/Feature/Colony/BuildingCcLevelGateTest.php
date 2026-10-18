<?php

namespace Tests\Feature\Colony;

/**
 * Player rule: a building level needs a Command Center of at least the same level.
 * Enforced in ColonyController::investBuilding() (the real player/bot path) and
 * BuildingService::levelupBlocker() (techtree path).
 *
 * Fixture: Colony 1, user_id=3 (Bart), housingComplex (28) instance 1, CC (25).
 */

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BuildingCcLevelGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 28)->where('instance_id', 1)
            ->update(['level' => 2, 'ap_spend' => 0, 'tile_x' => 0, 'tile_y' => 1]);
    }

    private function invest()
    {
        return $this->actingAs(User::where('user_id', 3)->firstOrFail())
            ->postJson(route('colony.building.invest'), ['building_id' => 28, 'instance_id' => 1]);
    }

    private function setCc(int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 25)->update(['level' => $level]);
    }

    public function test_building_lv3_is_rejected_while_cc_is_lv2_without_side_effects(): void
    {
        $this->setCc(2);

        $this->invest()->assertStatus(422)
            ->assertJsonPath('error', 'building_cc_gate')
            ->assertJsonPath('required_cc_level', 3);

        $this->assertSame(0, (int) DB::table('colony_buildings')->where('colony_id', 1)->where('building_id', 28)->value('ap_spend'));
    }

    public function test_building_lv3_is_accepted_once_cc_is_lv3(): void
    {
        $this->setCc(3);

        $response = $this->invest();

        $this->assertNotSame('building_cc_gate', $response->json('error'));
    }

    public function test_error_text_exists_in_both_locales(): void
    {
        foreach (['de', 'en'] as $locale) {
            $this->assertNotSame('colony.error_building_cc_gate', __('colony.error_building_cc_gate', ['level' => 3], $locale));
            $this->assertNotSame('techtree.error_building_cc_gate', __('techtree.error_building_cc_gate', [], $locale));
        }
    }
}
