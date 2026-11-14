<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\Techtree\KnowledgeEffectDescriptionService;
use App\Services\Techtree\ResearchService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
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

    private function withoutLab(int $level = 0): void
    {
        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'building_id' => 31])->update(['level' => $level]);
        Sanctum::actingAs(User::find(self::USER_ID));
    }

    /** @return array<int, array<string, mixed>> */
    private function items(TestResponse $response): array
    {
        return collect($response->json('phases'))->flatMap(fn ($p) => $p['items'])->all();
    }

    public function test_techtree_without_sciencelab_answers_preview(): void
    {
        $this->withoutLab();

        $this->getJson('/api/v1/techtree')->assertOk()->assertJson(['preview' => true]);
    }

    public function test_techtree_with_sciencelab_is_not_preview(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/techtree')->assertOk()->assertJson(['preview' => false]);
    }

    public function test_preview_has_same_nodes_and_edges_as_full_view(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        $full = $this->getJson('/api/v1/techtree')->assertOk();
        $this->withoutLab();
        $preview = $this->getJson('/api/v1/techtree')->assertOk();

        $keys = fn ($r) => collect($this->items($r))->map(fn ($i) => $i['type'].':'.$i['id'])->sort()->values()->all();
        $this->assertSame($keys($full), $keys($preview));
        // `met` follows the colony's lab level, which this test changes; the edge set itself must match.
        $strip = fn ($r) => array_map(fn ($e) => array_diff_key($e, ['met' => 1]), $r->json('edges'));
        $this->assertSame($strip($full), $strip($preview));
    }

    public function test_preview_knowledge_is_locked_and_stripped(): void
    {
        DB::table('colony_researches')->where('colony_id', self::COLONY_ID)->delete();
        $this->withoutLab();

        $research = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->where('type', 'research');
        $this->assertNotEmpty($research);
        foreach ($research as $item) {
            $this->assertSame('locked', $item['status']);
            $this->assertSame('sciencelab', $item['lock_reason']);
            $this->assertNull($item['description']);
            $this->assertSame('', $item['required_desc']);
            $this->assertSame([], $item['required_list']);
            $this->assertSame([], $item['unlocks_next_level']);
            $this->assertSame([], $item['effects_current_level']);
            $this->assertNull($item['max_level']);
            $this->assertNull($item['ap_cap']);
            $this->assertSame(0, $item['ap_spend']);
            $this->assertSame(0, $item['ap_for_levelup']);
            $this->assertSame(0, $item['ap_available']);
            $this->assertNull($item['rough_progress']);
            $this->assertFalse($item['bonus_progress']);
            $this->assertNotSame('', $item['name']);
        }
    }

    // ── Stille Forschung (spec 2026-11-11-tomas-rat-rueckmeldung, sentence 26) ──

    public function test_preview_shows_rough_bonus_progress_without_numbers(): void
    {
        config(['game.knowledge_rough_progress_bands' => [0.34, 0.67]]);
        $this->withoutLab();
        $threshold = app(ResearchService::class)->knowledgeLevelupCost(self::COLONY_ID, 90);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 90],
            ['level' => 0, 'ap_spend' => intdiv($threshold, 2), 'status_points' => 20]
        );

        $item = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->first(fn ($i) => $i['type'] === 'research' && $i['id'] === 90);

        $this->assertSame(2, $item['rough_progress']);
        $this->assertSame(__('knowledge.rough_progress_2'), $item['rough_progress_label']);
        $this->assertTrue($item['bonus_progress']);
        $this->assertSame(0, $item['ap_spend']);
        $this->assertSame(0, $item['ap_for_levelup']);
    }

    public function test_preview_shows_the_effect_of_the_reached_level_only(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 90],
            ['level' => 2, 'ap_spend' => 0, 'status_points' => 20]
        );
        $this->withoutLab();

        $item = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->first(fn ($i) => $i['type'] === 'research' && $i['id'] === 90);

        $expected = app(KnowledgeEffectDescriptionService::class)->effectsAtLevel('construction', 2);
        $this->assertNotEmpty($expected);
        $this->assertSame($expected, $item['effects_current_level']);
        $this->assertSame([], $item['unlocks_next_level']);
        $this->assertFalse($item['bonus_progress']);
    }

    public function test_preview_keeps_building_ship_advisor_items_unchanged(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        $full = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->where('type', '!=', 'research')->reject(fn ($i) => $i['type'] === 'building' && $i['id'] === 31);
        $this->withoutLab();
        $preview = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->where('type', '!=', 'research')->reject(fn ($i) => $i['type'] === 'building' && $i['id'] === 31);

        $norm = fn ($c) => $c->map(fn ($i) => $i['type'].':'.$i['id'])->combine($c->map(fn ($i) => collect($i)->except(['status', 'required_desc', 'required_list'])->all()));
        // Lab-dependent statuses of non-knowledge items may legitimately differ; every other field must match.
        $this->assertEquals($norm($full)->sortKeys()->all(), $norm($preview)->sortKeys()->all());
        foreach ($preview as $item) {
            $this->assertArrayNotHasKey('lock_reason', $item);
        }
    }

    public function test_preview_keeps_reached_knowledge_level(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 2, 'ap_spend' => 0, 'status_points' => 20]
        );
        $this->withoutLab();

        $item = collect($this->items($this->getJson('/api/v1/techtree')->assertOk()))->first(fn ($i) => $i['type'] === 'research' && $i['id'] === 91);
        $this->assertSame(2, $item['level']);
        $this->assertSame('built', $item['status']);
        $this->assertSame('sciencelab', $item['lock_reason']);
    }

    public function test_preview_does_not_consume_first_visit(): void
    {
        $this->withoutLab();
        $this->getJson('/api/v1/techtree')->assertOk()->assertJson(['first_visit' => false]);

        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'building_id' => 31])->update(['level' => 1]);
        $this->getJson('/api/v1/techtree')->assertOk()->assertJson(['preview' => false, 'first_visit' => true]);
    }

    public function test_order_without_lab_answers_lab_required_and_changes_nothing(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => 91],
            ['level' => 0, 'ap_spend' => 0, 'status_points' => 20]
        );
        $this->withoutLab();

        $response = $this->postJson('/api/v1/techtree/research/91/order', ['order' => 'add', 'ap' => 1])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'error' => 'lab_required']);
        $this->assertNotSame('techtree.error_lab_required', $response->json('message'));
        $this->assertNotSame('', $response->json('message'));
        $this->assertSame(0, (int) DB::table('colony_researches')->where(['colony_id' => self::COLONY_ID, 'research_id' => 91])->value('ap_spend'));
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
