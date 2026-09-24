<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 visibility (GDD §6 "Überkapazität — Konsequenzen", §13.4): while colonists
 * are homeless the colonist chip popup shows their number, the current trust
 * penalty, the Sols until departure and the "Wegschicken" button (AP cost as
 * chip). After a departure it shows the unfilled workplaces and the staffing
 * share instead. The trust chip popup lists the over-capacity penalty (and the
 * hunger penalty) as own rows.
 *
 * State: stored cap = workplaces − homeless − departed. Config under test:
 * deadline 3, base 2, step 1, cap 4, dismiss 8 AP.
 */
class ResourcebarOvercapPopupTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.overcap.departure_after_sols' => 3,
            'game.overcap.dismiss_ap_cost' => 8,
            'game.overcap.trust_base_malus' => 2,
            'game.overcap.trust_step' => 1,
            'game.overcap.trust_cap' => 4,
        ]);
    }

    private function html(): string
    {
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        return $this->actingAs($user)->get(route('nexusdb.index'))->assertOk()->getContent();
    }

    /** HTML of the colonist (KOL) chip, up to the trust chip. */
    private function supChip(string $html): string
    {
        $start = strpos($html, 'res-chip res-Sup');
        $end = strpos($html, 'id="resbar-ap-trust"');
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    /** HTML of the trust chip, up to the AP chip. */
    private function trustChip(string $html): string
    {
        $start = strpos($html, 'id="resbar-ap-trust"');
        $end = strpos($html, 'id="resbar-ap"', (int) $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    private function setState(int $homeless, int $overcapStreak = 0, int $hungerStreak = 0, int $departed = 0): void
    {
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $workplaces = $breakdown['cap'] - $breakdown['free'];
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $workplaces - $homeless - $departed]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update([
            'overcap_streak' => $overcapStreak,
            'hunger_streak' => $hungerStreak,
            'overcap_departed' => $departed,
        ]);
    }

    private function row(string $labelKey, string|int $value): string
    {
        return '<span class="res-popup-label">'.__($labelKey).'</span><span>'.$value.'</span>';
    }

    public function test_colonist_popup_shows_homeless_malus_and_departure(): void
    {
        $this->setState(6, 2); // streak 2 → −3, departure in 3 + 1 − 2 = 2 Sol

        $chip = $this->supChip($this->html());

        $this->assertStringContainsString($this->row('resources.popup_sup_overcap_homeless', 6), $chip);
        $this->assertStringContainsString($this->row('resources.popup_sup_overcap_trust', -3), $chip);
        $this->assertStringContainsString(
            $this->row('resources.popup_sup_overcap_departure', __('resources.popup_sup_overcap_departure_value', ['sols' => 2])),
            $chip
        );
        $this->assertStringNotContainsString(
            '<span class="res-popup-label">'.__('resources.popup_sup_understaffed').'</span><span>',
            $chip,
            'no understaffing row before anyone departed'
        );
    }

    public function test_colonist_popup_offers_dismissal_with_ap_cost_chip_while_homeless(): void
    {
        $this->setState(6, 1);

        $chip = $this->supChip($this->html());

        $this->assertStringContainsString(__('resources.popup_sup_dismiss'), $chip);
        $this->assertStringContainsString(e(route('colony.colonists.dismiss')), $chip);
        $this->assertMatchesRegularExpression('/class="ap-chip ap-cost-chip[^"]*"[^>]*>8 AP</', $chip, 'AP cost shown as chip');
        $this->assertStringContainsString('x-data="overcapDismiss()"', $chip, 'Alpine component, no jQuery');
    }

    public function test_colonist_popup_shows_understaffing_after_departure(): void
    {
        $this->setState(0, 0, 0, 8);
        $pct = (int) round(app(ResourcesService::class)->staffingShare(self::COLONY_ID) * 100);

        $chip = $this->supChip($this->html());

        $this->assertStringContainsString($this->row('resources.popup_sup_understaffed', 8), $chip);
        $this->assertStringContainsString(
            $this->row('resources.popup_sup_staffing', __('resources.popup_sup_staffing_value', ['pct' => $pct])),
            $chip
        );
        $this->assertStringNotContainsString(__('resources.popup_sup_overcap_homeless'), $chip);
        $this->assertStringNotContainsString(__('resources.popup_sup_dismiss'), $chip, 'no dismissal without homeless colonists');
    }

    public function test_colonist_popup_has_no_overcap_rows_within_cap(): void
    {
        $this->setState(-50);

        $chip = $this->supChip($this->html());

        $this->assertStringNotContainsString(__('resources.popup_sup_overcap_homeless'), $chip);
        $this->assertStringNotContainsString(__('resources.popup_sup_understaffed'), $chip);
        $this->assertStringNotContainsString(__('resources.popup_sup_dismiss'), $chip);
    }

    public function test_trust_popup_lists_overcap_and_hunger_penalties_as_own_rows(): void
    {
        $this->setState(6, 3, 3); // overcap streak 3 → −4 (cap), hunger 3 → −(2+2) = −4

        $chip = $this->trustChip($this->html());

        $this->assertStringContainsString(__('resources.popup_trust_overcap'), $chip);
        $this->assertStringContainsString(__('resources.popup_trust_hunger'), $chip);
        $this->assertSame(2, substr_count($chip, '<span>-4</span>'));
    }

    public function test_trust_popup_has_no_penalty_rows_without_streaks(): void
    {
        $this->setState(-50);

        $chip = $this->trustChip($this->html());

        $this->assertStringNotContainsString(__('resources.popup_trust_overcap'), $chip);
        $this->assertStringNotContainsString(__('resources.popup_trust_hunger'), $chip);
    }
}
