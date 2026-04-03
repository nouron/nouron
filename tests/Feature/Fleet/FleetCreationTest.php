<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests for fleet creation, deletion, and the commander requirement.
 *
 * Fixture summary (from TestSeeder / testdata.sqlite.sql):
 *   User 0 (Homer)  → colony 2, pilot advisors (personell_id=89, ids 67-85) available at colony
 *   User 3 (Bart)   → colony 1, ALL pilot advisors are assigned to fleets (no pilots at colony)
 */
class FleetCreationTest extends TestCase
{
    use RefreshDatabase;

    // Homer (user_id=0) has pilot advisors at colony → can create fleet
    protected int $homerUserId  = 0;
    protected int $homerColonyId = 2;

    // Bart (user_id=3) has no pilot advisors at colony
    protected int $bartUserId   = 3;

    protected function setUp(): void
    {
        parent::setUp();
        config(['game.dev_mode' => true]);
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Store (POST /fleet) ──────────────────────────────────────────────────

    public function test_create_fleet_requires_auth(): void
    {
        $this->post(route('fleet.store'), ['fleet' => 'Alpha'])
            ->assertRedirect(route('login'));
    }

    public function test_create_fleet_requires_commander(): void
    {
        // Bart has no pilot at colony → should fail
        $this->actingAs($this->makeUser($this->bartUserId))
            ->post(route('fleet.store'), ['fleet' => 'Bart-Flotte'])
            ->assertRedirect()
            ->assertSessionHasErrors('fleet');

        $this->assertDatabaseMissing('fleets', ['fleet' => 'Bart-Flotte']);
    }

    public function test_create_fleet_happy_path(): void
    {
        $countBefore = Fleet::where('user_id', $this->homerUserId)->count();

        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => 'Homer-Alpha'])
            ->assertRedirect(route('fleet.index'));

        $this->assertEquals($countBefore + 1, Fleet::where('user_id', $this->homerUserId)->count());
        $this->assertDatabaseHas('fleets', ['fleet' => 'Homer-Alpha', 'user_id' => $this->homerUserId]);
    }

    public function test_create_fleet_assigns_commander(): void
    {
        // Before: pilot advisor 67 is at colony 2 (fleet_id=NULL)
        $this->assertDatabaseHas('advisors', ['id' => 67, 'colony_id' => $this->homerColonyId, 'fleet_id' => null]);

        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => 'Homer-Beta']);

        $fleet = Fleet::where('fleet', 'Homer-Beta')->first();
        $this->assertNotNull($fleet);

        // One pilot advisor should now be assigned to the new fleet
        $assigned = DB::table('advisors')
            ->where('fleet_id', $fleet->id)
            ->where('is_commander', 1)
            ->count();
        $this->assertEquals(1, $assigned);
    }

    public function test_create_fleet_validates_name_required(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => ''])
            ->assertSessionHasErrors('fleet');
    }

    // ── Destroy (DELETE /fleet/{id}) ─────────────────────────────────────────

    public function test_destroy_requires_auth(): void
    {
        $this->delete(route('fleet.destroy', 8))
            ->assertRedirect(route('login'));
    }

    public function test_destroy_forbidden_for_wrong_user(): void
    {
        // Fleet 8 belongs to user 0; Bart (user 3) tries to delete it
        $this->actingAs($this->makeUser($this->bartUserId))
            ->delete(route('fleet.destroy', 8))
            ->assertForbidden();

        $this->assertDatabaseHas('fleets', ['id' => 8]);
    }

    public function test_destroy_happy_path(): void
    {
        // Create a fleet first so we can delete it cleanly
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => 'ToDelete']);

        $fleet = Fleet::where('fleet', 'ToDelete')->first();
        $this->assertNotNull($fleet);

        $this->actingAs($this->makeUser($this->homerUserId))
            ->delete(route('fleet.destroy', $fleet->id))
            ->assertRedirect(route('fleet.index'));

        $this->assertDatabaseMissing('fleets', ['id' => $fleet->id]);
    }

    public function test_destroy_returns_commander_to_colony(): void
    {
        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => 'ReturnFleet']);

        $fleet = Fleet::where('fleet', 'ReturnFleet')->first();
        $advisor = DB::table('advisors')->where('fleet_id', $fleet->id)->where('is_commander', 1)->first();
        $this->assertNotNull($advisor);

        $this->actingAs($this->makeUser($this->homerUserId))
            ->delete(route('fleet.destroy', $fleet->id));

        // Commander should be back at colony
        $this->assertDatabaseHas('advisors', [
            'id'           => $advisor->id,
            'fleet_id'     => null,
            'colony_id'    => $this->homerColonyId,
            'is_commander' => 0,
        ]);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): \App\Models\User
    {
        return \App\Models\User::where('user_id', $userId)->firstOrFail();
    }
}
