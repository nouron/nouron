<?php

namespace Tests\Feature\Bar;

/**
 * BarController feature tests.
 *
 * Covered scenarios:
 *  AUTH GUARD
 *    - test_index_requires_auth
 *    - test_accept_requires_auth
 *
 *  INDEX
 *    - test_index_shows_bar_page
 *    - test_index_shows_bar_page_when_bar_not_built
 *
 *  ACCEPT
 *    - test_accept_returns_json_ok
 *    - test_accept_returns_error_for_nonexistent_offer
 *    - test_accept_returns_error_when_insufficient_resources
 *    - test_accept_does_not_allow_foreign_colony_offer
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const USER_ID_BART    = 3;
    private const COLONY_ID_BART  = 1;
    private const BAR_BUILDING_ID = 52;
    private const RES_REGOLITH    = 3;
    private const RES_COMPOUNDS   = 4;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    /** Pin the TickService to a fixed tick so offer expiry is deterministic. */
    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    private function setBarLevel(int $level): void
    {
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID_BART)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => $level]);
    }

    private function clearBarOffers(): void
    {
        DB::table('bar_offers')->where('colony_id', self::COLONY_ID_BART)->delete();
    }

    /**
     * Insert a valid (non-expired, non-accepted) offer for Springfield and return its id.
     */
    private function insertValidOffer(int $expiresTick = 9999): int
    {
        return DB::table('bar_offers')->insertGetId([
            'colony_id'        => self::COLONY_ID_BART,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount'      => 10,
            'get_resource_id'  => self::RES_COMPOUNDS,
            'get_amount'       => 5,
            'expires_tick'     => $expiresTick,
            'is_accepted'      => false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_index_requires_auth(): void
    {
        $response = $this->get(route('colony.bar'));
        $response->assertRedirect(route('login'));
    }

    public function test_accept_requires_auth(): void
    {
        $response = $this->post(route('colony.bar.accept', ['offer' => 1]));
        $response->assertRedirect(route('login'));
    }

    // ── INDEX ─────────────────────────────────────────────────────────────────

    public function test_index_shows_bar_page(): void
    {
        $this->mockTick(1);
        $this->setBarLevel(1);

        $response = $this->actingAs($this->bart())
            ->get(route('colony.bar'));

        $response->assertOk();
        $response->assertViewIs('colony.bar');
        $response->assertViewHasAll(['colony', 'offers', 'barLevel', 'currentSol']);
    }

    public function test_index_shows_bar_page_when_bar_not_built(): void
    {
        $this->mockTick(1);
        $this->setBarLevel(0);

        $response = $this->actingAs($this->bart())
            ->get(route('colony.bar'));

        $response->assertOk();
        $response->assertViewIs('colony.bar');

        // barLevel must be 0 and offers must be empty
        $barLevel = $response->viewData('barLevel');
        $offers   = $response->viewData('offers');

        $this->assertEquals(0, $barLevel);
        $this->assertCount(0, $offers);
    }

    public function test_index_passes_active_offers_to_view(): void
    {
        $this->mockTick(10);
        $this->setBarLevel(1);
        $this->clearBarOffers();
        $this->insertValidOffer(9999); // expires far in the future

        $response = $this->actingAs($this->bart())
            ->get(route('colony.bar'));

        $response->assertOk();
        $offers = $response->viewData('offers');
        $this->assertCount(1, $offers, 'Index must pass the active offer to the view');
    }

    // ── ACCEPT ────────────────────────────────────────────────────────────────

    public function test_accept_returns_json_ok(): void
    {
        $this->mockTick(10);
        $this->setBarLevel(1);
        $this->clearBarOffers();

        // Give the player enough regolith to afford the offer
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

        $response->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_accept_returns_error_for_nonexistent_offer(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => 9999]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_accept_returns_error_when_insufficient_resources(): void
    {
        $this->mockTick(10);
        $this->clearBarOffers();

        // Player has zero regolith but the offer costs 10 regolith
        $this->setColonyResource(self::RES_REGOLITH, 0);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_accept_does_not_allow_foreign_colony_offer(): void
    {
        $this->mockTick(10);
        $this->clearBarOffers();

        // Insert an offer for Shelbyville (colony_id=2) — Bart's colony is 1
        $foreignOfferId = DB::table('bar_offers')->insertGetId([
            'colony_id'        => 2,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount'      => 10,
            'get_resource_id'  => self::RES_COMPOUNDS,
            'get_amount'       => 5,
            'expires_tick'     => 9999,
            'is_accepted'      => false,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $foreignOfferId]));

        // The BarService looks up offer by id AND colony_id → offer not found for Bart's colony
        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_accept_returns_error_for_expired_offer(): void
    {
        $this->mockTick(100);
        $this->clearBarOffers();

        // Insert an offer that expired at tick 100 (service: expires_tick <= tick)
        $expiredOfferId = $this->insertValidOffer(100);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $expiredOfferId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_accept_returns_ok_status_code_200(): void
    {
        $this->mockTick(10);
        $this->clearBarOffers();
        $this->setColonyResource(self::RES_REGOLITH, 500);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

        // HTTP 200 on success, 422 on failure
        $response->assertStatus(200);
    }
}
