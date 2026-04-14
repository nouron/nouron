<?php

namespace Tests\Feature\Fleet;

use App\Models\Fleet;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for fleet creation and deletion.
 *
 * Advisors are colony-scoped AP providers (Option B) — no commander required
 * to create a fleet. Any authenticated user with a colony can create fleets.
 */
class FleetCreationTest extends TestCase
{
    use RefreshDatabase;

    protected int $homerUserId  = 0;
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

    public function test_create_fleet_bart_happy_path(): void
    {
        $countBefore = Fleet::where('user_id', $this->bartUserId)->count();

        $this->actingAs($this->makeUser($this->bartUserId))
            ->post(route('fleet.store'), ['fleet' => 'Bart-Flotte'])
            ->assertRedirect(route('fleet.index'));

        $this->assertEquals($countBefore + 1, Fleet::where('user_id', $this->bartUserId)->count());
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
