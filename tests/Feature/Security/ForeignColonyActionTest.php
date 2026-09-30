<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

/**
 * R19 — multiplayer isolation: a player must not act on another player's
 * Cantina concerns, information encounters, merchant visits or pending ships
 * by guessing their row ids. Covers the routes the R19 audit found without a
 * cross-user test (the other id-parameter routes already have one).
 *
 * Fixture: Bart (user 3) → colony 1 with an active run (TestSeeder);
 * a second player gets colony 2 via CreatesForeignColony.
 */
class ForeignColonyActionTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private User $bart;

    private int $foreignColonyId;

    private int $foreignUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->bart = User::where('user_id', 3)->firstOrFail();

        $foreign = $this->createForeignColony([25 => 1, 52 => 1, 44 => 2]);
        $this->foreignColonyId = $foreign['colony_id'];
        $this->foreignUserId = $foreign['user_id'];
    }

    public function test_cannot_resolve_foreign_cantina_concern(): void
    {
        $concernId = DB::table('bar_concerns')->insertGetId([
            'colony_id' => $this->foreignColonyId,
            'character_slug' => 'deva',
            'created_tick' => 1,
            'expires_tick' => 2000000000,
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($this->bart)
            ->postJson(route('colony.bar.resolve-concern', ['concern' => $concernId]), ['choice' => 'accept']);

        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertFalse((bool) DB::table('bar_concerns')->where('id', $concernId)->value('is_resolved'));
    }

    public function test_cannot_resolve_foreign_information_encounter(): void
    {
        $encounterId = DB::table('bar_information_encounters')->insertGetId([
            'colony_id' => $this->foreignColonyId,
            'character_slug' => 'lenn',
            'outcome_key' => 'storm_forecast',
            'created_tick' => 1,
            'expires_tick' => 2000000000,
            'is_resolved' => false,
        ]);

        $response = $this->actingAs($this->bart)
            ->postJson(route('colony.bar.resolve-information', ['encounter' => $encounterId]), ['choice' => 'accept']);

        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertFalse((bool) DB::table('bar_information_encounters')->where('id', $encounterId)->value('is_resolved'));
    }

    public function test_cannot_buy_from_foreign_merchant_visit(): void
    {
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => $this->foreignColonyId,
            'tick_start' => 0,
            'tick_end' => 2000000000,
            'was_visited' => false,
        ]);
        $itemId = DB::table('merchant_items')->insertGetId([
            'visit_id' => $visitId,
            'item_type' => 'resource',
            'label' => 'Organika',
            'cost_credits' => 1,
            'payload' => json_encode(['resource_id' => 5, 'amount' => 10]),
            'sold' => false,
        ]);
        $creditsBefore = (int) DB::table('user_resources')->where('user_id', 3)->value('credits');

        $response = $this->actingAs($this->bart)
            ->postJson(route('colony.merchant.buy', ['itemId' => $itemId]));

        $this->assertGreaterThanOrEqual(400, $response->status());
        $this->assertFalse((bool) DB::table('merchant_items')->where('id', $itemId)->value('sold'));
        $this->assertSame($creditsBefore, (int) DB::table('user_resources')->where('user_id', 3)->value('credits'));
    }

    public function test_cannot_mark_foreign_merchant_visit_as_opened(): void
    {
        $visitId = DB::table('merchant_visits')->insertGetId([
            'colony_id' => $this->foreignColonyId,
            'tick_start' => 0,
            'tick_end' => 2000000000,
            'was_visited' => false,
        ]);

        $this->actingAs($this->bart)
            ->postJson(route('colony.merchant.open', ['visitId' => $visitId]));

        $this->assertFalse((bool) DB::table('merchant_visits')->where('id', $visitId)->value('was_visited'));
    }

    public function test_cannot_assign_foreign_pending_ship_to_own_hangar(): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => 1, 'building_id' => 44, 'instance_id' => 50,
            'level' => 2, 'status_points' => 20, 'ap_spend' => 0,
        ]);
        $shipRowId = DB::table('colony_ships')->insertGetId([
            'colony_id' => $this->foreignColonyId,
            'ship_id' => 85,
            'level' => 0,
            'status_points' => 10,
            'ap_spend' => 0,
            'hangar_instance_id' => null,
            'ship_state' => 'pending',
            'pending_until_tick' => 999,
        ]);

        $this->actingAs($this->bart)
            ->postJson(route('colony.hangar.assign'), ['ship_row_id' => $shipRowId, 'instance_id' => 50])
            ->assertStatus(422);

        $ship = DB::table('colony_ships')->where('id', $shipRowId)->first();
        $this->assertSame($this->foreignColonyId, (int) $ship->colony_id);
        $this->assertNull($ship->hangar_instance_id);
    }
}
