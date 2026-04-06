<?php

namespace Tests\Feature\Colony;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MED-3: Colony rename — input validation and flash messenger.
 *
 * Covers:
 * - Happy path: valid name → colony name updated in DB, redirect to colony.index
 * - Flash success message appears after successful rename
 * - HTML injection guard: names containing <, > or { } must be rejected
 * - XSS attempt: <script>…</script> in name → validation error, DB unchanged
 * - Name too short (< 2 chars) → validation error
 * - Name too long (> 50 chars) → validation error
 * - Empty name → validation error
 * - Unauthenticated request → redirect to login
 *
 * Fixture: Colony 1 (Springfield) belongs to Bart (user_id=3).
 */
class ColonyRenameTest extends TestCase
{
    use RefreshDatabase;

    protected int $bartUserId = 3;
    protected int $colonyId   = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_rename_requires_auth(): void
    {
        $this->patch(route('colony.rename'), ['name' => 'NewName'])
            ->assertRedirect(route('login'));

        // DB unchanged
        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'Springfield']);
    }

    // ── Happy path ───────────────────────────────────────────────────────────

    public function test_rename_happy_path_updates_db(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'New Springfield'])
            ->assertRedirect(route('colony.index'));

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'New Springfield']);
    }

    public function test_rename_redirects_to_colony_index(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'Renamed'])
            ->assertRedirect(route('colony.index'));
    }

    // ── Flash messenger ──────────────────────────────────────────────────────

    public function test_rename_shows_success_flash_message(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'Renamed Colony'])
            ->assertSessionHas('success');
    }

    public function test_rename_success_flash_contains_meaningful_text(): void
    {
        $response = $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'Renamed Colony']);

        $this->assertStringContainsStringIgnoringCase(
            'aktualisiert',
            session('success'),
            'Flash message should confirm the update.'
        );
    }

    // ── MED-3: HTML injection guard ──────────────────────────────────────────

    /**
     * A name containing <script>…</script> must be rejected with a validation error.
     * The DB must remain unchanged.
     */
    public function test_rename_rejects_script_tag(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => '<script>alert(1)</script>'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'Springfield']);
    }

    /**
     * A name starting with < (opening angle bracket) must be rejected.
     */
    public function test_rename_rejects_opening_angle_bracket(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => '<Evil'])
            ->assertSessionHasErrors('name');
    }

    /**
     * A name containing > must be rejected.
     */
    public function test_rename_rejects_closing_angle_bracket(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'Evil>Colony'])
            ->assertSessionHasErrors('name');
    }

    /**
     * A name containing curly braces must be rejected (template injection guard).
     */
    public function test_rename_rejects_curly_braces(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => '{{evil}}'])
            ->assertSessionHasErrors('name');
    }

    /**
     * A name containing square brackets must be rejected.
     */
    public function test_rename_rejects_square_brackets(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => '[injection]'])
            ->assertSessionHasErrors('name');
    }

    // ── Boundary values ──────────────────────────────────────────────────────

    public function test_rename_rejects_empty_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_rename_rejects_single_character_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'X'])
            ->assertSessionHasErrors('name');
    }

    public function test_rename_accepts_minimum_length_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => 'AB'])
            ->assertRedirect(route('colony.index'))
            ->assertSessionMissing('errors');

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'AB']);
    }

    public function test_rename_rejects_name_over_50_chars(): void
    {
        $tooLong = str_repeat('A', 51);

        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => $tooLong])
            ->assertSessionHasErrors('name');
    }

    public function test_rename_accepts_name_at_exactly_50_chars(): void
    {
        $exactly50 = str_repeat('B', 50);

        $this->actingAs($this->makeUser($this->bartUserId))
            ->patch(route('colony.rename'), ['name' => $exactly50])
            ->assertRedirect(route('colony.index'));

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => $exactly50]);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): \App\Models\User
    {
        return \App\Models\User::where('user_id', $userId)->firstOrFail();
    }
}
