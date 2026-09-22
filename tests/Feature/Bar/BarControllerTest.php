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
 *    - test_index_shows_market_report_when_visit_announced
 *    - test_index_shows_category_line_only_at_rank_3
 *    - test_index_uses_tomorrow_wording_for_one_sol
 *    - test_index_hides_market_report_without_consul
 *
 *  ACCEPT
 *    - test_accept_returns_json_ok
 *    - test_accept_returns_error_for_nonexistent_offer
 *    - test_accept_returns_error_when_insufficient_resources
 *    - test_accept_does_not_allow_foreign_colony_offer
 *    - test_accept_forwards_character_slug_to_codex_progress
 *    - test_accept_without_character_slug_records_no_codex_progress
 */

use App\Models\User;
use App\Services\MerchantService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarControllerTest extends TestCase
{
    use RefreshDatabase;

    // ── Fixture constants ─────────────────────────────────────────────────────

    private const USER_ID_BART = 3;

    private const COLONY_ID_BART = 1;

    private const BAR_BUILDING_ID = 52;

    private const RES_REGOLITH = 3;

    private const RES_COMPOUNDS = 4;

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
            'colony_id' => self::COLONY_ID_BART,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 10,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 5,
            'expires_tick' => $expiresTick,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
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

    public function test_negotiate_requires_auth(): void
    {
        $response = $this->post(route('colony.bar.negotiate', ['offer' => 1]));
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
        $offers = $response->viewData('offers');

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

    // ── MARKTBERICHT (Konsul, A13) ────────────────────────────────────────────

    private function setConsulRank(?int $rank): void
    {
        DB::table('advisors')
            ->where('colony_id', self::COLONY_ID_BART)
            ->where('personell_id', 92)
            ->delete();

        if ($rank !== null) {
            DB::table('advisors')->insert([
                'colony_id' => self::COLONY_ID_BART,
                'personell_id' => 92,
                'user_id' => self::USER_ID_BART,
                'rank' => $rank,
                'active_ticks' => 0,
            ]);
        }
    }

    /** First tick at which Corvan's next visit starts (per the real spawn check). */
    private function corvanStartTick(): int
    {
        $service = $this->app->make(MerchantService::class);
        for ($tick = 15; $tick < 80; $tick++) {
            if ($service->shouldSpawn(self::COLONY_ID_BART, $tick)) {
                return $tick;
            }
        }
        $this->fail('no spawn tick found');
    }

    public function test_index_shows_market_report_when_visit_announced(): void
    {
        $this->setBarLevel(1);
        $this->setConsulRank(3);
        $start = $this->corvanStartTick();
        $this->mockTick($start - 2);

        $response = $this->actingAs($this->bart())->get(route('colony.bar'));

        $response->assertOk();
        $response->assertSee(__('colony.merchant_forecast_title'));
        $response->assertSee(__('colony.merchant_forecast_in_sols', ['sols' => 2]));
        $response->assertViewHas('merchantForecast', fn ($f) => $f['sols'] === 2 && $f['tick'] === $start);
    }

    public function test_index_shows_category_line_only_at_rank_3(): void
    {
        $this->setBarLevel(1);
        $start = $this->corvanStartTick();
        $this->mockTick($start - 1);

        $this->setConsulRank(2);
        $rank2 = $this->actingAs($this->bart())->get(route('colony.bar'));
        $rank2->assertSee(__('colony.merchant_forecast_title'));
        $rank2->assertDontSee(__('colony.merchant_forecast_inventory', ['categories' => '']));

        $this->setConsulRank(3);
        $rank3 = $this->actingAs($this->bart())->get(route('colony.bar'));
        $rank3->assertSee(__('colony.merchant_forecast_inventory', ['categories' => '']), false);
    }

    public function test_index_uses_tomorrow_wording_for_one_sol(): void
    {
        $this->setBarLevel(1);
        $this->setConsulRank(1);
        $this->mockTick($this->corvanStartTick() - 1);

        $response = $this->actingAs($this->bart())->get(route('colony.bar'));

        $response->assertSee(__('colony.merchant_forecast_tomorrow'));
        $response->assertDontSee(__('colony.merchant_forecast_in_sols', ['sols' => 1]));
    }

    public function test_index_hides_market_report_without_consul(): void
    {
        $this->setBarLevel(1);
        $this->setConsulRank(null);
        $this->mockTick($this->corvanStartTick() - 1);

        $response = $this->actingAs($this->bart())->get(route('colony.bar'));

        $response->assertOk();
        $response->assertDontSee(__('colony.merchant_forecast_title'));
        $response->assertViewHas('merchantForecast', null);
    }

    // ── ACCEPT ────────────────────────────────────────────────────────────────

    public function test_accept_response_includes_updated_balances_for_resourcebar_sync(): void
    {
        // Verbindliche Konvention (siehe CLAUDE.md/Owner-Feedback): jede
        // AJAX-Aktion mit AP-/Ressourcen-Änderung muss die Resourcebar live
        // syncen können — dafür müssen die neuen Salden in der JSON-Antwort
        // stehen, nicht nur die Trade-Deltas.
        $this->mockTick(10);
        $this->setBarLevel(1);
        $this->clearBarOffers();
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

        $response->assertOk()
            ->assertJsonStructure(['ok', 'give_resource_amount', 'get_resource_amount', 'ap_available']);
    }

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
            'colony_id' => 2,
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 10,
            'get_resource_id' => self::RES_COMPOUNDS,
            'get_amount' => 5,
            'expires_tick' => 9999,
            'is_accepted' => false,
            'created_at' => now(),
            'updated_at' => now(),
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

    /**
     * A42: the Blade dialog already knows which character a bar_trade offer
     * belongs to (A36's slot-based assignment) — the controller must forward
     * that slug from the request body to BarService::acceptOffer() so the
     * Charakter-Kodex actually credits the trade (see BarOfferCodexHookupTest
     * for the service-level guarantee).
     */
    public function test_accept_forwards_character_slug_to_codex_progress(): void
    {
        $this->mockTick(10);
        $this->setBarLevel(1);
        $this->clearBarOffers();
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]), [
                'character_slug' => 'prospector',
            ]);

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID_BART)
            ->where('character_slug', 'prospector')
            ->count());
    }

    public function test_accept_without_character_slug_records_no_codex_progress(): void
    {
        $this->mockTick(10);
        $this->setBarLevel(1);
        $this->clearBarOffers();
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

        $response->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(0, DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID_BART)
            ->count());
    }

    // ── NEGOTIATE ─────────────────────────────────────────────────────────────

    public function test_negotiate_returns_error_without_consul(): void
    {
        $this->mockTick(10);
        $this->clearBarOffers();
        DB::table('advisors')->where('colony_id', self::COLONY_ID_BART)->where('personell_id', 92)->delete();
        $this->setColonyResource(self::RES_REGOLITH, 100);

        $offerId = $this->insertValidOffer(9999);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.negotiate', ['offer' => $offerId]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_negotiate_returns_error_for_nonexistent_offer(): void
    {
        $this->mockTick(10);
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'personell_id' => 92],
            ['rank' => 2, 'user_id' => self::USER_ID_BART, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.negotiate', ['offer' => 9999]));

        $response->assertStatus(422)
            ->assertJson(['ok' => false])
            ->assertJsonStructure(['ok', 'error']);
    }

    public function test_negotiate_resolves_offer_when_consul_assigned(): void
    {
        $this->mockTick(10);
        $this->clearBarOffers();
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'personell_id' => 92],
            ['rank' => 3, 'user_id' => self::USER_ID_BART, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );
        $this->setColonyResource(self::RES_REGOLITH, 1000);
        $offerId = $this->insertValidOffer(9999);

        // Once past validation (offer found, consul available, resources/AP sufficient)
        // negotiateOffer() always resolves with ok=true — the win/loss roll only
        // decides `success`, both outcomes are a "resolved request", not an error.
        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.negotiate', ['offer' => $offerId]));

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'success']);
    }

    public function test_negotiate_response_always_includes_ap_available_for_resourcebar_sync(): void
    {
        // ap_available must be present even on a failed roll — AP is spent either way
        // (see BarService::negotiateOffer docblock) and the resourcebar needs to
        // reflect that regardless of win/loss.
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'personell_id' => 92],
            ['rank' => 1, 'user_id' => self::USER_ID_BART, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );

        for ($tick = 1; $tick <= 50; $tick++) {
            $this->mockTick($tick);
            $this->clearBarOffers();
            $this->setColonyResource(self::RES_REGOLITH, 1000);
            $offerId = $this->insertValidOffer($tick + 10);

            $response = $this->actingAs($this->bart())
                ->postJson(route('colony.bar.negotiate', ['offer' => $offerId]));

            $response->assertOk()->assertJsonStructure(['ok', 'success', 'ap_available']);
        }
    }

    public function test_negotiate_then_accept_two_step_flow_executes_trade_only_on_accept(): void
    {
        DB::table('advisors')->updateOrInsert(
            ['colony_id' => self::COLONY_ID_BART, 'personell_id' => 92],
            ['rank' => 3, 'user_id' => self::USER_ID_BART, 'active_ticks' => 0, 'unavailable_until_tick' => null]
        );

        $found = false;
        for ($tick = 1; $tick <= 50; $tick++) {
            $this->mockTick($tick);
            $this->clearBarOffers();
            $this->setColonyResource(self::RES_REGOLITH, 1000);
            $offerId = $this->insertValidOffer($tick + 10);

            $negotiateResponse = $this->actingAs($this->bart())
                ->postJson(route('colony.bar.negotiate', ['offer' => $offerId]));

            if ($negotiateResponse->json('ok') && $negotiateResponse->json('success')) {
                $found = true;

                // Resources must be untouched right after negotiate — only the offer's
                // terms improved, no trade executed yet.
                $this->assertEquals(1000, DB::table('colony_resources')
                    ->where('colony_id', self::COLONY_ID_BART)->where('resource_id', self::RES_REGOLITH)->value('amount'));

                $acceptResponse = $this->actingAs($this->bart())
                    ->postJson(route('colony.bar.accept', ['offer' => $offerId]));

                $acceptResponse->assertOk()->assertJson(['ok' => true]);

                $regolithAfter = (int) DB::table('colony_resources')
                    ->where('colony_id', self::COLONY_ID_BART)->where('resource_id', self::RES_REGOLITH)->value('amount');
                $this->assertLessThan(1000, $regolithAfter, 'Accepting the negotiated offer must finally execute the trade');
                break;
            }
        }

        $this->assertTrue($found, 'Expected at least one successful negotiation within 50 ticks at 85% chance');
    }
}
