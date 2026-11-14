<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * CommandCenterTest — the Kommandozentrale dashboard (Owner-Konzept 2026-07-10).
 * Bundles 7 widgets: Phasenziele, Kolonisten-Zulage, Run-Fortschritt, Wartungsstau,
 * Netto-Sol-Bilanz, Berater-Kurzübersicht, Vertrauens-Ereignisse.
 *
 * Fixture: Bart (user_id=3) owns colony_id=1 (Springfield).
 */
class CommandCenterTest extends TestCase
{
    use RefreshDatabase;

    private const BART_USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::BART_USER_ID);
    }

    private function ccPage(): string
    {
        return file_get_contents(base_path('frontend/src/routes/(game)/command-center/+page.svelte'));
    }

    private function getData(): TestResponse
    {
        return $this->actingAs($this->bart())->getJson(route('colony.command_center'));
    }

    public function test_index_loads_successfully(): void
    {
        $this->getData()
            ->assertOk()
            ->assertJsonStructure([
                'colonists', 'phaseProgress', 'stipendTiers', 'solLimit', 'currentSol', 'nexusDebt',
                'damagedBuildings', 'lastSolDeltas', 'advisors', 'trustEvents', 'trustEventsSum', 'nexusImport',
            ]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson(route('colony.command_center'))->assertUnauthorized();
    }

    public function test_nav_link_is_always_accessible_no_lock_gate(): void
    {
        // Unlike Hangar/Cantina, the Kommandozentrale route has no build-gate —
        // it must be reachable even on a fresh colony with no path buildings.
        $this->getData()->assertOk();
    }

    public function test_phase_progress_widget_shows_criteria(): void
    {
        $response = $this->getData();

        $response->assertOk();
        $response->assertJsonPath('phaseProgress.phase', 1);
        $response->assertJsonCount(4, 'phaseProgress.criteria');
    }

    public function test_stipend_tiers_are_passed_to_view(): void
    {
        $tiers = collect($this->getData()->json('stipendTiers'));

        $this->assertSame(['small', 'medium', 'large'], $tiers->pluck('key')->all());
        // The tier's trust value is the configured trust event of that tier.
        $small = $tiers->firstWhere('key', 'small');
        $this->assertSame((int) config('game.stipend.tiers.small.cost'), $small['cost']);
        $this->assertSame((int) config('game.trust.events.'.config('game.stipend.tiers.small.event_key')), $small['trust']);
    }

    public function test_maintenance_widget_shows_no_buildings_when_none_damaged(): void
    {
        $this->getData()->assertJsonCount(0, 'damagedBuildings');
    }

    public function test_maintenance_widget_lists_critically_damaged_buildings(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 27)
            ->update(['status_points' => 2]);

        $response = $this->getData();

        $response->assertJsonCount(1, 'damagedBuildings');
        $response->assertJsonPath('damagedBuildings.0.status_points', 2);
    }

    /**
     * A43: the building chip in the maintenance widget deep-links to the tile of
     * exactly that instance (GDD entity-chips.md, building link target).
     */
    public function test_maintenance_widget_chip_links_to_the_instance_tile(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 27)
            ->update(['status_points' => 2, 'tile_x' => 1, 'tile_y' => 0]);

        // The data carries building + instance, the page builds the tile link from them.
        $response = $this->getData();
        $response->assertJsonPath('damagedBuildings.0.building_id', 27);
        $response->assertJsonPath('damagedBuildings.0.instance_id', 1);

        $page = $this->ccPage();
        $this->assertStringContainsString('link: `/colony?building=${b.building_id}&instance=${b.instance_id}`', $page);
        $this->assertStringContainsString("t('entity_chip.label_tile_link')", $page);
    }

    public function test_net_balance_widget_shows_no_data_before_first_sol_ends(): void
    {
        $this->getData()->assertJsonPath('lastSolDeltas', null);
    }

    public function test_net_balance_widget_shows_cached_deltas_after_sol_ends(): void
    {
        Cache::put('colony:'.self::COLONY_ID.':last_sol_deltas', [
            'regolith' => 10, 'werkstoffe' => 0, 'organika' => -5, 'credits' => 40, 'trust' => 2,
        ], now()->addDay());

        $response = $this->getData();

        $response->assertJsonPath('lastSolDeltas.credits', 40);
        $response->assertJsonPath('lastSolDeltas.trust', 2);
    }

    public function test_advisors_widget_shows_active_advisor(): void
    {
        $response = $this->getData();

        $response->assertJsonCount(1, 'advisors');
        $response->assertJsonPath('advisors.0.rank_name', 'Junior');
    }

    public function test_advisors_widget_renders_advisor_name_not_just_rank(): void
    {
        // Regression (Blade era): the advisor chip lost its label and only the rank
        // suffix rendered. Now: the data carries the translated name, the page uses it as label.
        $name = $this->getData()->json('advisors.0.name');

        $this->assertIsString($name);
        $this->assertNotSame('?', $name);
        $this->assertStringNotContainsString('advisors.', $name);
        $this->assertStringContainsString('label={a.name}', $this->ccPage());
    }

    public function test_trust_events_widget_shows_events_acting_next_sol(): void
    {
        $next = app(TickService::class)->getTickCount() + 1;
        DB::table('trust_events')->insert([
            ['colony_id' => self::COLONY_ID, 'tick' => $next, 'event_type' => 'stipend_small'],
            ['colony_id' => self::COLONY_ID, 'tick' => $next - 1, 'event_type' => 'well_fed'], // 1-Sol profile: already over
        ]);

        $response = $this->getData();

        $response->assertJsonCount(1, 'trustEvents');
        $response->assertJsonPath('trustEvents.0.tick', $next);
        $response->assertJsonPath('trustEventsSum', (int) config('game.trust.events.stipend_small'));
    }

    public function test_trust_events_widget_shows_fading_aftereffect_and_following_values(): void
    {
        config([
            'game.trust.event_profiles.test' => [100, 50, 25],
            'game.trust.event_profile.research_level_up' => 'test',
            'game.trust.events.research_level_up' => 2, // pinned: 50 % = 1, 25 % = 1
        ]);
        $next = app(TickService::class)->getTickCount() + 1;
        DB::table('trust_events')->insert([
            ['colony_id' => self::COLONY_ID, 'tick' => $next - 1, 'event_type' => 'research_level_up'],
        ]);

        $response = $this->getData();

        $response->assertJsonCount(1, 'trustEvents');
        $response->assertJsonPath('trustEvents.0.delta', 1);
        $response->assertJsonPath('trustEvents.0.following', [1]);
        $response->assertJsonPath('trustEventsSum', 1);
    }

    public function test_run_progress_widget_shows_sol_and_nexus_debt(): void
    {
        $response = $this->getData();

        $this->assertIsInt($response->json('currentSol'));
        $this->assertIsInt($response->json('solLimit'));
        $this->assertIsInt($response->json('nexusDebt'));
    }

    /**
     * Widget 9 (F5/A28, 2026-09-11): delayed Nexus import for Regolith/Organika.
     * Prices come from config('game.economy.delayed_import_price'), keyed by
     * resource_id (3=Regolith, 5=Organika).
     */
    public function test_delayed_import_widget_shows_prices_and_route(): void
    {
        $response = $this->getData();

        // No trading post / trade knowledge on the fixture colony -> price == base price.
        $response->assertJsonPath('nexusImport.resources.3.price', (int) config('game.economy.delayed_import_price.3'));
        $response->assertJsonPath('nexusImport.resources.5.price', (int) config('game.economy.delayed_import_price.5'));
        $response->assertJsonPath('nexusImport.resources.4.price', (int) config('game.economy.compound_import_price'));

        $page = $this->ccPage();
        $this->assertStringContainsString("t('colony.nexus_import_delayed_title')", $page);
        $this->assertStringContainsString("'/colony/nexus/import-delayed'", $page);
        $this->assertStringEndsWith('/colony/nexus/import-delayed', route('colony.nexus.import-delayed'));
    }
}
