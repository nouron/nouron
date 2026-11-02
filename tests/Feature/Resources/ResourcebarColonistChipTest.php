<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * A15 (GDD §6 Kolonisten-Framing): the supply chip reads "KOL used / cap"
 * ("47 Kolonisten im Einsatz / 60 verfügbar") instead of the abstract "SUP free / cap".
 */
class ResourcebarColonistChipTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    /** Header data behind the chip (GET /api/v1/shell), drawn by ResourceBar.svelte. */
    private function shell(): TestResponse
    {
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        return $this->actingAs($user)->getJson('/api/v1/shell')->assertOk();
    }

    private function resourceBar(): string
    {
        return file_get_contents(base_path('frontend/src/lib/components/ResourceBar.svelte'));
    }

    public function test_chip_uses_kol_label_and_shows_colonists(): void
    {
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);

        $this->shell()->assertJsonPath('colonists_overview.colonists', $breakdown['cap']);

        $bar = $this->resourceBar();
        $this->assertStringContainsString("abbr: 'KOL'", $bar);
        $this->assertStringContainsString('amount: fmt(b ? (c?.colonists ?? b.cap)', $bar);
    }

    public function test_chip_is_flagged_when_workplaces_are_unfilled(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 1]);

        $unfilled = $this->shell()->json('colonists_overview.unfilled');

        $this->assertGreaterThan(0, $unfilled);
        // The chip turns to the warning style exactly when workplaces stay unfilled.
        $bar = $this->resourceBar();
        $this->assertStringContainsString('const unfilledNone = (c?.unfilled ?? 0) <= 0;', $bar);
        $this->assertStringContainsString("cls: `res-chip res-Sup\${unfilledNone ? '' : ' res-chip--warning'}`", $bar);
        $this->assertStringNotContainsString('res-chip--over', $bar);
    }

    public function test_chip_is_not_flagged_when_every_workplace_is_staffed(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 500]);

        $this->shell()
            ->assertJsonPath('colonists_overview.unfilled', 0)
            ->assertJsonPath('colonists_overview.colonists', 500);
    }

    public function test_popup_names_staffing_share_and_has_no_dismiss_action(): void
    {
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $workplaces = $breakdown['cap'] - $breakdown['free'];
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $workplaces - 8]);
        $share = app(ResourcesService::class)->staffingShare(self::COLONY_ID);

        $response = $this->shell()->assertJsonPath('colonists_overview.unfilled', 8);
        $this->assertEqualsWithDelta($share, $response->json('staffing_share'), 0.0001);

        $bar = $this->resourceBar();
        $this->assertStringContainsString("label: t('resources.popup_sup_unfilled'), value: c.unfilled, hidden: unfilledNone", $bar);
        $this->assertStringContainsString("t('resources.popup_sup_staffing_value', {", $bar);
        $this->assertStringContainsString('pct: Math.round((s.staffing_share ?? 1) * 100)', $bar);
        $this->assertStringNotContainsString('colonists/dismiss', $bar);
        $this->assertStringNotContainsString('overcapDismiss', $bar);
    }
}
