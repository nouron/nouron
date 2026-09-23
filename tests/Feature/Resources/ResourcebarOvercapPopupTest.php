<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 stage 1 visibility: the colonist chip popup shows the colonist deficit plus
 * either the remaining grace period or the current trust penalty; the trust chip
 * popup lists the over-capacity penalty (and the hunger penalty) as own rows.
 *
 * Over-cap: user_resources.supply (the stored cap) lowered to 1. Config under
 * test: grace 5, base 2, step 1, cap 4.
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
            'game.overcap.grace_sols' => 5,
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

    private function setState(int $cap, int $overcapStreak, int $hungerStreak = 0): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $cap]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)
            ->update(['overcap_streak' => $overcapStreak, 'hunger_streak' => $hungerStreak]);
    }

    public function test_colonist_popup_shows_deficit_and_remaining_grace(): void
    {
        $this->setState(1, 2);
        $deficit = -app(ResourcesService::class)->getFreeSupply(self::COLONY_ID);

        $chip = $this->supChip($this->html());

        $this->assertStringContainsString(__('resources.popup_sup_overcap_deficit'), $chip);
        $this->assertStringContainsString('<span class="res-popup-label">'.__('resources.popup_sup_overcap_deficit').'</span><span>'.$deficit.'</span>', $chip);
        // streak 2 of grace 5 → trust penalty starts with the 4th Sol change from now
        $this->assertStringContainsString(__('resources.popup_sup_overcap_grace'), $chip);
        $this->assertStringContainsString(__('resources.popup_sup_overcap_grace_value', ['sols' => 4]), $chip);
        $this->assertStringNotContainsString(__('resources.popup_sup_overcap_trust'), $chip);
    }

    public function test_colonist_popup_shows_trust_penalty_after_grace(): void
    {
        $this->setState(1, 7); // grace 5 → streak 7 = −3

        $chip = $this->supChip($this->html());

        $this->assertStringContainsString(__('resources.popup_sup_overcap_trust'), $chip);
        $this->assertStringContainsString('<span>-3</span>', $chip);
        $this->assertStringNotContainsString(__('resources.popup_sup_overcap_grace'), $chip);
    }

    public function test_colonist_popup_has_no_overcap_rows_within_cap(): void
    {
        $this->setState(500, 0);

        $chip = $this->supChip($this->html());

        $this->assertStringNotContainsString(__('resources.popup_sup_overcap_deficit'), $chip);
    }

    public function test_trust_popup_lists_overcap_and_hunger_penalties_as_own_rows(): void
    {
        $this->setState(1, 8, 3); // overcap −4 (cap), hunger 3 → −(2+2) = −4

        $chip = $this->trustChip($this->html());

        $this->assertStringContainsString(__('resources.popup_trust_overcap'), $chip);
        $this->assertStringContainsString(__('resources.popup_trust_hunger'), $chip);
        $this->assertSame(2, substr_count($chip, '<span>-4</span>'));
    }

    public function test_trust_popup_has_no_penalty_rows_without_streaks(): void
    {
        $this->setState(500, 0, 0);

        $chip = $this->trustChip($this->html());

        $this->assertStringNotContainsString(__('resources.popup_trust_overcap'), $chip);
        $this->assertStringNotContainsString(__('resources.popup_trust_hunger'), $chip);
    }
}
