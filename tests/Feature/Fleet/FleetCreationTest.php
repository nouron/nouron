<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for fleet creation and deletion.
 *
 * Fixture summary (from TestSeeder / testdata.sqlite.sql):
 *   User 0 (Homer)  → colony 2 — can create fleet
 *   User 3 (Bart)   → colony 1
 */
class FleetCreationTest extends TestCase
{
    use RefreshDatabase;

    // Homer (user_id=0) can create fleet
    protected int $homerUserId  = 0;

    // Bart (user_id=3)
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

    public function test_create_fleet_happy_path(): void
    {
        $countBefore = Fleet::where('user_id', $this->homerUserId)->count();

        $this->actingAs($this->makeUser($this->homerUserId))
            ->post(route('fleet.store'), ['fleet' => 'Homer-Alpha'])
            ->assertRedirect(route('fleet.index'));

        $this->assertEquals($countBefore + 1, Fleet::where('user_id', $this->homerUserId)->count());
        $this->assertDatabaseHas('fleets', ['fleet' => 'Homer-Alpha', 'user_id' => $this->homerUserId]);
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

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): \App\Models\User
    {
        return \App\Models\User::where('user_id', $userId)->firstOrFail();
    }
}
