<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\AdvisorService;
use App\Services\ResourcesService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * "Wegschicken" (GDD §6 "Überkapazität — Konsequenzen", A14 Owner decision
 * 2026-09-24): an AP action that sends every homeless colonist away at once —
 * same state as the departure (overcap_departed += homeless), streak ends,
 * trust event colonists_dismissed instead of colonists_left. Only available
 * while colonists are homeless.
 *
 * Setup: the stored cap is set to workplaces − 7 → 7 homeless colonists.
 * Fixture colony 1 (user 3).
 */
class OvercapDismissTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const AP_COST = 8;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.ap.base' => 30,
            'game.bypass.ap_checks' => false,
            'game.overcap.dismiss_ap_cost' => self::AP_COST,
        ]);
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();
        DB::table('colony_log')->where('user', self::USER_ID)->delete();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function makeHomeless(int $homeless): void
    {
        $breakdown = $this->app->make(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $workplaces = $breakdown['cap'] - $breakdown['free'];
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => $workplaces - $homeless]);
        DB::table('glx_colonies')->where('id', self::COLONY_ID)->update(['overcap_streak' => 2, 'overcap_departed' => 0]);
    }

    private function dismiss(array $payload = [])
    {
        return $this->actingAs($this->user())->postJson(route('colony.colonists.dismiss'), $payload);
    }

    private function ap(): int
    {
        return $this->app->make(AdvisorService::class)->getAvailableActionPoints(self::COLONY_ID);
    }

    private function colony(int $id = self::COLONY_ID): object
    {
        return DB::table('glx_colonies')->where('id', $id)->first();
    }

    public function test_dismissing_sends_the_homeless_away_and_charges_ap(): void
    {
        $this->makeHomeless(7);
        $apBefore = $this->ap();
        $tick = $this->app->make(TickService::class)->getTickCount();

        $response = $this->dismiss();

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('dismissed', 7)
            ->assertJsonPath('apAvailable', $apBefore - self::AP_COST)
            ->assertJsonPath('colonists.homeless', 0)
            ->assertJsonPath('colonists.departed', 7);

        $this->assertSame($apBefore - self::AP_COST, $this->ap(), 'AP from the shared pool');
        $this->assertSame(7, (int) $this->colony()->overcap_departed);
        $this->assertSame(0, (int) $this->colony()->overcap_streak, 'the streak ends immediately');
        $this->assertSame(0, $this->app->make(ResourcesService::class)->colonistStatus(self::COLONY_ID)['homeless']);

        $events = DB::table('trust_events')->where('colony_id', self::COLONY_ID)->pluck('event_type', 'tick')->all();
        $this->assertSame([$tick + 1 => 'colonists_dismissed'], $events, 'milder event, no colonists_left');

        $log = DB::table('colony_log')->where('user', self::USER_ID)->where('event', 'colony.colonists_dismissed')->first();
        $this->assertNotNull($log);
        $this->assertSame(7, json_decode($log->parameters, true)['count']);
    }

    public function test_not_available_without_homeless_colonists(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 999]);
        $apBefore = $this->ap();

        $this->dismiss()->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'no_homeless_colonists');

        $this->assertSame($apBefore, $this->ap());
        $this->assertSame(0, DB::table('trust_events')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_rejected_with_too_few_ap(): void
    {
        $this->makeHomeless(7);
        $this->app->make(AdvisorService::class)->lockActionPoints(self::COLONY_ID, $this->ap() - (self::AP_COST - 1));

        $this->dismiss()->assertStatus(422)->assertJsonPath('error', 'ap_limit');

        $this->assertSame(0, (int) $this->colony()->overcap_departed);
        $this->assertSame(2, (int) $this->colony()->overcap_streak);
        $this->assertSame(0, DB::table('trust_events')->where('colony_id', self::COLONY_ID)->count());
    }

    public function test_a_foreign_colony_cannot_be_targeted(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 999]);
        $foreign = $this->createForeignColony([25 => 1, 31 => 1]); // cap 0, sciencelab workplaces → homeless

        $this->dismiss(['colony_id' => $foreign['colony_id']])
            ->assertStatus(422)->assertJsonPath('error', 'no_homeless_colonists');

        $this->assertSame(0, (int) $this->colony($foreign['colony_id'])->overcap_departed);
        $this->assertGreaterThan(0, $this->app->make(ResourcesService::class)->colonistStatus($foreign['colony_id'])['homeless']);
    }

    public function test_guests_cannot_dismiss(): void
    {
        $this->postJson(route('colony.colonists.dismiss'))->assertUnauthorized();
    }
}
