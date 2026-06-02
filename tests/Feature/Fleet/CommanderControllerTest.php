<?php

namespace Tests\Feature\Fleet;

/**
 * HTTP endpoint tests for commander assignment routes.
 *
 * Covered scenarios:
 *   ASSIGN  POST /fleet/{id}/commander/assign
 *     - assign_commander_redirects_to_fleet_config_on_success
 *     - assign_commander_flashes_commander_success_session_key
 *     - assign_commander_returns_error_when_no_pilot_advisor
 *     - assign_commander_requires_authentication
 *     - assign_commander_rejects_foreign_fleet
 *
 *   REMOVE  POST /fleet/{id}/commander/remove
 *     - remove_commander_redirects_on_success
 *     - remove_commander_flashes_commander_success_session_key
 *     - remove_commander_requires_authentication
 */

use App\Models\Advisor;
use App\Models\Fleet;
use App\Models\User;
use App\Services\Techtree\PersonellService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommanderControllerTest extends TestCase
{
    use RefreshDatabase;

    // Bart (user_id=3) owns colony 1 (Springfield)
    protected int $bartUserId   = 3;
    protected int $bartColonyId = 1;

    // Homer (user_id=0) owns colony 2 (Shelbyville)
    protected int $homerUserId  = 0;

    protected int $pilotPersonellId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->pilotPersonellId = PersonellService::idFor('pilot');

        // Remove all colony-bound advisors for Bart so tests start from a clean state
        Advisor::where('colony_id', $this->bartColonyId)->delete();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function user(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }

    private function createFleet(int $userId, int $x = 6828, int $y = 3016): Fleet
    {
        $fleet = new Fleet(['fleet' => 'Test Fleet', 'x' => $x, 'y' => $y]);
        $fleet->user_id = $userId;
        $fleet->save();
        return $fleet;
    }

    private function insertPilotOnColony(int $colonyId, array $overrides = []): Advisor
    {
        return Advisor::create(array_merge([
            'user_id'               => $this->bartUserId,
            'personell_id'          => $this->pilotPersonellId,
            'colony_id'             => $colonyId,
            'rank'                  => 1,
            'active_ticks'          => 0,
            'unavailable_until_tick' => null,
            'fleet_id'              => null,
            'is_commander'          => 0,
        ], $overrides));
    }

    // ── ASSIGN ────────────────────────────────────────────────────────────────

    public function test_assign_commander_redirects_to_fleet_config_on_success(): void
    {
        $this->insertPilotOnColony($this->bartColonyId);
        $fleet = $this->createFleet($this->bartUserId);

        $response = $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.assign', $fleet->id));

        $response->assertRedirect(route('fleet.config', $fleet->id));
    }

    public function test_assign_commander_flashes_commander_success_session_key(): void
    {
        $this->insertPilotOnColony($this->bartColonyId);
        $fleet = $this->createFleet($this->bartUserId);

        $response = $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.assign', $fleet->id));

        $response->assertSessionHas('commander_success');
    }

    public function test_assign_commander_returns_error_when_no_pilot_advisor(): void
    {
        // No pilot advisor on Bart's colony
        $fleet = $this->createFleet($this->bartUserId);

        $response = $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.assign', $fleet->id));

        $response->assertRedirect();
        $response->assertSessionHasErrors('commander');
    }

    public function test_assign_commander_requires_authentication(): void
    {
        $fleet = $this->createFleet($this->bartUserId);

        $this->post(route('fleet.commander.assign', $fleet->id))
            ->assertRedirect(route('login'));
    }

    public function test_assign_commander_rejects_foreign_fleet(): void
    {
        // Fleet belongs to Homer — Bart must get 403
        $homerFleet = $this->createFleet($this->homerUserId);

        $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.assign', $homerFleet->id))
            ->assertForbidden();
    }

    // ── REMOVE ────────────────────────────────────────────────────────────────

    public function test_remove_commander_redirects_on_success(): void
    {
        $fleet = $this->createFleet($this->bartUserId);

        // Insert pilot already assigned as commander on the fleet
        $this->insertPilotOnColony($this->bartColonyId, [
            'colony_id'    => null,
            'fleet_id'     => $fleet->id,
            'is_commander' => 1,
        ]);

        $response = $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.remove', $fleet->id));

        $response->assertRedirect(route('fleet.config', $fleet->id));
    }

    public function test_remove_commander_flashes_commander_success_session_key(): void
    {
        $fleet = $this->createFleet($this->bartUserId);

        $this->insertPilotOnColony($this->bartColonyId, [
            'colony_id'    => null,
            'fleet_id'     => $fleet->id,
            'is_commander' => 1,
        ]);

        $response = $this->actingAs($this->user($this->bartUserId))
            ->post(route('fleet.commander.remove', $fleet->id));

        $response->assertSessionHas('commander_success');
    }

    public function test_remove_commander_requires_authentication(): void
    {
        $fleet = $this->createFleet($this->bartUserId);

        $this->post(route('fleet.commander.remove', $fleet->id))
            ->assertRedirect(route('login'));
    }
}
