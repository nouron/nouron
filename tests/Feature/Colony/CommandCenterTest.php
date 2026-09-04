<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function test_index_loads_successfully(): void
    {
        $this->actingAs($this->bart())
            ->get(route('colony.command_center'))
            ->assertOk()
            ->assertViewIs('colony.command_center');
    }

    public function test_nav_link_is_always_accessible_no_lock_gate(): void
    {
        // Unlike Hangar/Cantina, the Kommandozentrale route has no build-gate —
        // it must be reachable even on a fresh colony with no path buildings.
        $this->actingAs($this->bart())
            ->get(route('colony.command_center'))
            ->assertOk();
    }

    public function test_phase_progress_widget_shows_criteria(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertOk();
        $response->assertViewHas('phaseProgress', function ($phaseProgress) {
            return $phaseProgress['phase'] === 1 && count($phaseProgress['criteria']) === 3;
        });
    }

    public function test_stipend_tiers_are_passed_to_view(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('stipendTiers', function ($tiers) {
            return array_keys($tiers) === ['small', 'medium', 'large'];
        });
    }

    public function test_maintenance_widget_shows_no_buildings_when_none_damaged(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('damagedBuildings', fn ($buildings) => $buildings->isEmpty());
    }

    public function test_maintenance_widget_lists_critically_damaged_buildings(): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 27)
            ->update(['status_points' => 2]);

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('damagedBuildings', function ($buildings) {
            return $buildings->count() === 1 && $buildings->first()['status_points'] === 2;
        });
    }

    public function test_net_balance_widget_shows_no_data_before_first_sol_ends(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('lastSolDeltas', fn ($deltas) => $deltas === null);
    }

    public function test_net_balance_widget_shows_cached_deltas_after_sol_ends(): void
    {
        Cache::put('colony:'.self::COLONY_ID.':last_sol_deltas', [
            'regolith' => 10, 'werkstoffe' => 0, 'organika' => -5, 'credits' => 40, 'trust' => 2,
        ], now()->addDay());

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('lastSolDeltas', fn ($deltas) => $deltas['credits'] === 40 && $deltas['trust'] === 2);
    }

    public function test_advisors_widget_shows_active_advisor(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('advisors', function ($advisors) {
            return $advisors->count() === 1 && $advisors->first()['rank_name'] === 'Junior';
        });
    }

    public function test_advisors_widget_renders_advisor_name_not_just_rank(): void
    {
        // Regression: nested double-quoted array access inside a <x-entity-chip label="{{ ... }}">
        // attribute broke Blade's component-tag compiler, leaving the whole tag uncompiled
        // and its label text invisible — only the "(Junior)" rank suffix rendered.
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));
        $advisorName = $response->viewData('advisors')->first()['name'];

        $response->assertDontSee('x-entity-chip', false);
        $response->assertSee($advisorName);
    }

    public function test_trust_events_widget_shows_recent_events(): void
    {
        DB::table('trust_events')->insert([
            ['colony_id' => self::COLONY_ID, 'tick' => 5, 'event_type' => 'stipend_small'],
            ['colony_id' => self::COLONY_ID, 'tick' => 4, 'event_type' => 'well_fed'],
        ]);

        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('trustEvents', function ($events) {
            return $events->count() === 2 && $events->first()['tick'] === 5;
        });
    }

    public function test_run_progress_widget_shows_sol_and_nexus_debt(): void
    {
        $response = $this->actingAs($this->bart())->get(route('colony.command_center'));

        $response->assertViewHas('currentSol');
        $response->assertViewHas('solLimit');
        $response->assertViewHas('nexusDebt');
    }
}
