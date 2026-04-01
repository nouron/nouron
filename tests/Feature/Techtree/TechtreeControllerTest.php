<?php

namespace Tests\Feature\Techtree;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * I4: Cross-Colony-Build-Exploit
 *
 * Verifies that the TechtreeController always operates on the authenticated
 * user's own colony (resolved via session / getPrimeColony), and that no
 * URL or request parameter can redirect an action to a different user's colony.
 */
class TechtreeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected int $userIdBart     = 3;  // owns colony 1 (Springfield)
    protected int $colonyIdBart   = 1;
    protected int $colonyIdOther  = 2;  // Shelbyville — no owner in test data

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    /**
     * The techtree action URL contains {type}/{id}/{order} — there is no
     * colony_id segment. This test verifies that the action endpoint only
     * modifies the authenticated user's own colony, not any other colony.
     */
    public function testActionOnlyAffectsOwnColony(): void
    {
        $bart = User::find($this->userIdBart);

        // Record colony 2 (other colony) ap_spend before the request
        $before = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdOther, 'building_id' => 27])
            ->value('ap_spend');

        // Bart invests AP in oremine — ap_spend on colony 1 must change
        DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdBart, 'building_id' => 27])
            ->update(['ap_spend' => 0]);

        $this->actingAs($bart)
            ->get(route('techtree.action', ['type' => 'building', 'id' => 27, 'order' => 'add']))
            ->assertSuccessful();

        // Colony 2 must be untouched
        $afterOther = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdOther, 'building_id' => 27])
            ->value('ap_spend');

        $this->assertEquals($before, $afterOther, 'Colony 2 must not be affected by Bart\'s action');

        // Colony 1 must have changed (ap_spend increased by 1)
        $afterOwn = DB::table('colony_buildings')
            ->where(['colony_id' => $this->colonyIdBart, 'building_id' => 27])
            ->value('ap_spend');

        $this->assertEquals(1, $afterOwn, 'Colony 1 (Bart\'s) must be updated');
    }

    /**
     * There is no colony_id parameter in the URL — the route signature
     * is /techtree/{type}/{id}/{order}. This confirms by design that
     * colony selection is server-side only (session-based).
     */
    public function testTechtreeRouteHasNoColonyIdParameter(): void
    {
        $route = route('techtree.action', ['type' => 'building', 'id' => 27, 'order' => 'add']);
        $this->assertStringNotContainsString('colony', $route);
    }
}
