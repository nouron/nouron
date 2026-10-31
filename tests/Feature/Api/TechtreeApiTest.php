<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Techtree JSON API for the SvelteKit pilot (ADR 0006 stage 1): same controller
 * and gate logic as the Blade screen, reached with a Sanctum bearer token.
 *
 * Fixture colony 1 (Bart, user 3): sciencelab 31 built, active started run.
 */
class TechtreeApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        config(['game.knowledge_ap_cap_per_sol.base' => null]);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('advisors')->insert([
            'user_id' => self::USER_ID, 'personell_id' => 35, 'colony_id' => self::COLONY_ID,
            'rank' => 3, 'active_ticks' => 0, 'unavailable_until_tick' => null,
        ]);
    }

    public function test_techtree_requires_token(): void
    {
        $this->getJson('/api/v1/techtree')->assertUnauthorized();
    }

    public function test_techtree_returns_phases_and_edges(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $response = $this->getJson('/api/v1/techtree')->assertOk();

        $response->assertJsonStructure([
            'phases' => [0 => ['cc_level', 'items']],
            'edges',
            'first_visit',
        ]);
        $item = collect($response->json('phases'))->flatMap(fn ($p) => $p['items'])->firstWhere('type', 'research');
        $this->assertNotNull($item);
        foreach (['id', 'name', 'status', 'grid_row', 'grid_col', 'ap_spend', 'ap_for_levelup'] as $field) {
            $this->assertArrayHasKey($field, $item);
        }
    }

    public function test_techtree_without_sciencelab_answers_locked(): void
    {
        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'building_id' => 31])->update(['level' => 0]);
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/techtree')
            ->assertStatus(409)
            ->assertJson(['error' => 'techtree_locked']);
    }

    public function test_order_invests_ap_in_own_colony(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 0, 'status_points' => 20]
        );
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->postJson('/api/v1/techtree/research/91/order', ['order' => 'add', 'ap' => 1])
            ->assertOk()
            ->assertJson(['success' => true, 'tech' => ['ap_spend' => 1]]);
    }

    public function test_locale_returns_lang_groups_as_json(): void
    {
        $this->getJson('/api/v1/locale/de')
            ->assertOk()
            ->assertJsonPath('techtree.status_available', trans('techtree.status_available', [], 'de'));
    }

    public function test_locale_rejects_unknown_language(): void
    {
        $this->getJson('/api/v1/locale/xx')->assertNotFound();
        $this->getJson('/api/v1/locale/..')->assertNotFound();
    }
}
