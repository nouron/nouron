<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T43-G8 (Kolonisten-Modell): "N Kolonisten, davon M beschäftigt" — one source
 * (ResourcesService::colonistsOverview()) for header bar, Sol report, Kommandozentrale
 * and building sidebar.
 *
 *   colonists = housing (the stored cap)
 *   employed  = min(colonists, workplaces)
 *   free      = colonists − employed
 *   unfilled  = max(0, workplaces − colonists)
 */
class ColonistsOverviewTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function workplaces(): int
    {
        $b = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);

        return $b['cap'] - $b['free'];
    }

    private function setCap(int $cap): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $cap]);
    }

    public function test_free_housing_means_employed_equals_workplaces(): void
    {
        $wp = $this->workplaces();
        $this->setCap($wp + 10);

        $o = app(ResourcesService::class)->colonistsOverview(self::COLONY_ID);

        $this->assertSame(['colonists' => $wp + 10, 'employed' => $wp, 'free' => 10, 'unfilled' => 0], $o);
    }

    public function test_workplaces_above_housing_are_unfilled(): void
    {
        $wp = $this->workplaces();
        $this->setCap($wp - 4);

        $o = app(ResourcesService::class)->colonistsOverview(self::COLONY_ID);

        $this->assertSame(['colonists' => $wp - 4, 'employed' => $wp - 4, 'free' => 0, 'unfilled' => 4], $o);
    }

    public function test_header_chip_shows_colonists_and_popup_names_employed_and_free(): void
    {
        $wp = $this->workplaces();
        $this->setCap($wp + 10);
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        $this->actingAs($user)->getJson('/api/v1/shell')
            ->assertOk()
            ->assertJsonPath('colonists_overview', ['colonists' => $wp + 10, 'employed' => $wp, 'free' => 10, 'unfilled' => 0]);

        // The header chip shows the colonist count under the KOL label; its popup names employed and free.
        $bar = file_get_contents(base_path('frontend/src/lib/components/ResourceBar.svelte'));
        $this->assertStringContainsString("abbr: 'KOL'", $bar);
        $this->assertStringContainsString('c?.colonists ?? b.cap', $bar);
        $this->assertStringContainsString("t('resources.popup_sup_colonists'), value: c.colonists", $bar);
        $this->assertStringContainsString("t('resources.popup_sup_employed'), value: c.employed", $bar);
        $this->assertStringContainsString("t('resources.popup_sup_free'), value: c.free", $bar);
    }

    public function test_command_center_shows_colonists_card(): void
    {
        $wp = $this->workplaces();
        $this->setCap($wp + 10);
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        $this->actingAs($user)->getJson(route('colony.command_center'))
            ->assertOk()
            ->assertJsonPath('colonists', ['colonists' => $wp + 10, 'employed' => $wp, 'free' => 10, 'unfilled' => 0]);

        $page = file_get_contents(base_path('frontend/src/routes/(game)/command-center/+page.svelte'));
        $this->assertStringContainsString("t('command_center.widget_colonists_title')", $page);
        $this->assertStringContainsString("t('command_center.widget_colonists_line', { colonists: d.colonists.colonists, employed: d.colonists.employed })", $page);
        $this->assertStringContainsString("t('command_center.widget_colonists_free', { free: d.colonists.free })", $page);
    }

    public function test_command_center_names_unfilled_workplaces(): void
    {
        $wp = $this->workplaces();
        $this->setCap($wp - 3);
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        $this->actingAs($user)->getJson(route('colony.command_center'))
            ->assertOk()
            ->assertJsonPath('colonists.unfilled', 3);

        $page = file_get_contents(base_path('frontend/src/routes/(game)/command-center/+page.svelte'));
        $this->assertStringContainsString("t('command_center.widget_colonists_unfilled', { count: d.colonists.unfilled })", $page);
    }

    public function test_hexview_sidebar_says_needs_colonists_not_kol(): void
    {
        $page = file_get_contents(base_path('frontend/src/routes/(game)/colony/+page.svelte'));

        $this->assertStringNotContainsString('} KOL`', $page);
        $this->assertStringContainsString("t('colony.building_needs_colonists', { count: b.supply_cost ?? 0 })", $page);
    }
}
