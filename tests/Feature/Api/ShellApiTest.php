<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * App-shell data for the SvelteKit frontend (ADR 0006 stage 2): the same header
 * data (resource bar, nav gates, hint, notices) the Blade layout composer shares.
 *
 * Fixture: Bart (user 3), colony 1, active started run, sciencelab built.
 */
class ShellApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_shell_requires_token(): void
    {
        $this->getJson('/api/v1/shell')->assertUnauthorized();
    }

    public function test_shell_returns_header_data(): void
    {
        Sanctum::actingAs(User::find(3));

        $response = $this->getJson('/api/v1/shell')->assertOk();

        $response->assertJsonStructure([
            'username', 'resource_bar_possessions', 'current_sol', 'sol_limit', 'nexus_debt', 'nexus_debt_max',
            'in_active_run', 'bar_built', 'hangar_built', 'sciencelab_built', 'unread_nexus_count',
            'trust', 'colony_ap', 'supply_breakdown', 'colonists_overview', 'staffing_share', 'resource_flow',
            'hunger_penalty', 'ap_breakdown', 'active_hint', 'active_encounter_notices',
        ]);
        $response->assertJsonStructure(['trust' => ['band', 'ap', 'bands', 'lab', 'sources', 'total']]);
        $response->assertJsonPath('in_active_run', true);
        $response->assertJsonPath('sciencelab_built', true);
        // Bart's colony has a science lab: the number is visible (T59 F3).
        $this->assertSame(
            (int) DB::table('colony_resources')->where(['colony_id' => 1, 'resource_id' => 12])->value('amount'),
            $response->json('trust.total')
        );
    }

    public function test_shell_without_run_has_no_run_data(): void
    {
        DB::table('runs')->where('user_id', 3)->update(['status' => 'finished']);
        Sanctum::actingAs(User::find(3));

        $this->getJson('/api/v1/shell')
            ->assertOk()
            ->assertJsonPath('in_active_run', false)
            ->assertJsonPath('current_sol', null)
            ->assertJsonPath('sciencelab_built', false);
    }

    public function test_shell_finds_the_colony_like_the_controllers_do(): void
    {
        // Same lookup as ResolvesActiveColony: a player's only colony counts even without is_primary.
        DB::table('glx_colonies')->where('user_id', 3)->update(['is_primary' => 0]);
        Sanctum::actingAs(User::find(3));

        $this->getJson('/api/v1/shell')
            ->assertOk()
            ->assertJsonPath('trust.total', (int) DB::table('colony_resources')->where(['colony_id' => 1, 'resource_id' => 12])->value('amount'));
    }

    public function test_shell_works_for_a_user_without_a_colony(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/shell')->assertOk()->assertJsonPath('in_active_run', false);
    }
}
