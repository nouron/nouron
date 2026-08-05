<?php

namespace Tests\Feature\Colony;

/**
 * CorporateContactController feature tests.
 *
 * POST /colony/corporate-contact/buy-harvester — Orin's harvester offer purchase
 * (GDD §4c "Harvester-Zweitinstanz: Bezugsquelle", Weg A, freigegeben 2026-08-05).
 *
 * Covered scenarios:
 *   - test_buy_harvester_requires_auth
 *   - test_buy_harvester_returns_ok_true_for_valid_purchase
 *   - test_buy_harvester_returns_422_when_no_offer_active
 *   - test_get_offer_requires_auth
 *   - test_get_offer_returns_offer_on_a_hit_tick
 *   - test_get_offer_returns_null_offer_when_none_active
 */
use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorporateContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;   // Bart

    private const COLONY_ID = 1; // Springfield

    private const CC_ID = 25;

    private const HARVESTER_ID = 27;

    // Deterministic hit tick for colony_id=1 (see CorporateContactServiceTest).
    private const OFFER_HIT_TICK = 71;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::CC_ID, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)->where('building_id', self::HARVESTER_ID)->where('instance_id', 2)
            ->delete();
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0]
        );

        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 1000]);

        $this->app->instance(TickService::class, new TickService(self::OFFER_HIT_TICK));
    }

    private function makeUser(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    public function test_buy_harvester_requires_auth(): void
    {
        $response = $this->postJson(route('colony.corporate-contact.buy-harvester'));

        $response->assertStatus(401);
    }

    public function test_buy_harvester_returns_ok_true_for_valid_purchase(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.corporate-contact.buy-harvester'));

        $response->assertOk()->assertJsonPath('ok', true);
    }

    public function test_buy_harvester_returns_422_when_no_offer_active(): void
    {
        // A tick with no appearance at all (see CorporateContactServiceTest).
        $this->app->instance(TickService::class, new TickService(1));

        $response = $this->actingAs($this->makeUser())
            ->postJson(route('colony.corporate-contact.buy-harvester'));

        $response->assertStatus(422)->assertJsonPath('ok', false);
    }

    public function test_get_offer_requires_auth(): void
    {
        $response = $this->getJson(route('colony.corporate-contact.offer'));

        $response->assertStatus(401);
    }

    public function test_get_offer_returns_offer_on_a_hit_tick(): void
    {
        $response = $this->actingAs($this->makeUser())
            ->getJson(route('colony.corporate-contact.offer'));

        $response->assertOk()->assertJsonPath('offer.price', fn ($price) => $price >= 400 && $price <= 800);
    }

    public function test_get_offer_returns_null_offer_when_none_active(): void
    {
        $this->app->instance(TickService::class, new TickService(1));

        $response = $this->actingAs($this->makeUser())
            ->getJson(route('colony.corporate-contact.offer'));

        $response->assertOk()->assertJsonPath('offer', null);
    }
}
