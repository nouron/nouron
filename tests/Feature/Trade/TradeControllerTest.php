<?php

namespace Tests\Feature\Trade;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * HTTP-Tests für TradeController.
 *
 * Testdaten (Simpsons-Fixture via TestSeeder):
 *   - User 3 (Bart)  — Kolonie 1 "Springfield"
 *   - User 0 (Homer) — Kolonie 2 "Shelbyville"
 *   - 5 Rohstoff-Angebote (2× Kolonie 1, 3× Kolonie 2)
 *   - 10 Forschungs-Angebote (2× Kolonie 1, 8× Kolonie 2)
 */
class TradeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $bart;
    private User $homer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->bart  = User::find(3);
        $this->homer = User::find(0);
    }

    // ── GET /trade/resources ──────────────────────────────────────────────────

    public function test_resources_redirects_guest(): void
    {
        $this->get(route('trade.resources'))->assertRedirect(route('login'));
    }

    public function test_resources_loads_for_authenticated_user(): void
    {
        $this->actingAs($this->bart)
            ->get(route('trade.resources'))
            ->assertOk()
            ->assertViewIs('trade.resources')
            ->assertViewHas('offers')
            ->assertViewHas('resources')
            ->assertViewHas('user_id', 3)
            ->assertViewHas('myColonies');
    }

    public function test_resources_shows_all_offers_by_default(): void
    {
        $response = $this->actingAs($this->bart)
            ->get(route('trade.resources'));

        $offers = $response->viewData('offers');
        $this->assertCount(5, $offers);
    }

    public function test_resources_filters_by_colony_id(): void
    {
        $response = $this->actingAs($this->bart)
            ->get(route('trade.resources', ['colony_id' => 1]));

        $offers = $response->viewData('offers');
        $this->assertCount(2, $offers);
        $this->assertTrue($offers->every(fn($o) => $o->colony_id === 1));
    }

    public function test_resources_filters_by_direction(): void
    {
        $response = $this->actingAs($this->bart)
            ->get(route('trade.resources', ['direction' => 1]));

        $offers = $response->viewData('offers');
        $this->assertTrue($offers->every(fn($o) => $o->direction === 1));
    }

    public function test_resources_filters_by_direction_zero(): void
    {
        // direction=0 must not be treated as "no filter" (falsy-value edge case)
        $response = $this->actingAs($this->bart)
            ->get(route('trade.resources', ['direction' => 0]));

        $offers = $response->viewData('offers');
        $this->assertGreaterThan(0, $offers->count());
        $this->assertTrue($offers->every(fn($o) => $o->direction === 0));
    }

    // ── POST /trade/offer/resource ────────────────────────────────────────────

    public function test_add_resource_offer_redirects_guest(): void
    {
        $this->post(route('trade.offer.resource'), [])->assertRedirect(route('login'));
    }

    public function test_add_resource_offer_fails_validation_on_missing_fields(): void
    {
        $this->actingAs($this->bart)
            ->post(route('trade.offer.resource'), [])
            ->assertSessionHasErrors(['colony_id', 'direction', 'resource_id', 'amount', 'price']);
    }

    public function test_add_resource_offer_succeeds_for_owner(): void
    {
        $countBefore = DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->count();
        $this->assertSame(0, $countBefore);

        $this->actingAs($this->bart)
            ->post(route('trade.offer.resource'), [
                'colony_id'   => 1,
                'direction'   => 1,
                'resource_id' => 3,
                'amount'      => 50,
                'price'       => 5,
            ])
            ->assertRedirect(route('trade.resources'))
            ->assertSessionHas('success');

        $this->assertSame(1, DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 1)->where('resource_id', 3)
            ->count());
    }

    public function test_add_resource_offer_updates_existing(): void
    {
        // Seed data: colony 1, direction=0, resource_id=10 already exists (amount=4)
        $this->actingAs($this->bart)
            ->post(route('trade.offer.resource'), [
                'colony_id'   => 1,
                'direction'   => 0,
                'resource_id' => 10,
                'amount'      => 999,
                'price'       => 7,
            ])
            ->assertRedirect(route('trade.resources'))
            ->assertSessionHas('success');

        // Still exactly one row, but with the updated amount
        $this->assertSame(1, DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 10)
            ->count());
        $this->assertSame(999, (int) DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 10)
            ->value('amount'));
    }

    public function test_add_resource_offer_fails_for_non_owner(): void
    {
        // Bart tries to post an offer for Homer's colony 2
        $this->actingAs($this->bart)
            ->post(route('trade.offer.resource'), [
                'colony_id'   => 2,
                'direction'   => 1,
                'resource_id' => 3,
                'amount'      => 50,
                'price'       => 5,
            ])
            ->assertRedirect(route('trade.resources'))
            ->assertSessionHas('error');
    }

    // ── POST /trade/offer/remove ──────────────────────────────────────────────

    public function test_remove_offer_redirects_guest(): void
    {
        $this->post(route('trade.offer.remove'), [])->assertRedirect(route('login'));
    }

    public function test_remove_resource_offer_succeeds(): void
    {
        // Seed data: colony 1, direction=0, resource_id=8 (lho) belongs to Bart
        $this->assertSame(1, DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 8)
            ->count());

        $response = $this->actingAs($this->bart)
            ->postJson(route('trade.offer.remove'), [
                'colony_id'   => 1,
                'direction'   => 0,
                'resource_id' => 8,
            ]);

        $response->assertOk()->assertJson(['result' => true]);

        $this->assertSame(0, DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 8)
            ->count());
    }

    public function test_remove_resource_offer_fails_for_non_owner(): void
    {
        // Homer tries to remove Bart's offer
        $response = $this->actingAs($this->homer)
            ->postJson(route('trade.offer.remove'), [
                'colony_id'   => 1,
                'direction'   => 0,
                'resource_id' => 8,
            ]);

        $response->assertOk()->assertJson(['result' => false]);

        $this->assertSame(1, DB::table('trade_resources')
            ->where('colony_id', 1)->where('direction', 0)->where('resource_id', 8)
            ->count());
    }

    public function test_remove_offer_returns_validation_error_without_resource_id(): void
    {
        // resource_id is now required; omitting it must return 422
        $response = $this->actingAs($this->bart)
            ->postJson(route('trade.offer.remove'), [
                'colony_id' => 1,
                'direction' => 0,
            ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['resource_id']);
    }
}
