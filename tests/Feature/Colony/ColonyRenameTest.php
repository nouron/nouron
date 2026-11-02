<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MED-3: Colony rename — PATCH colony.rename, JSON API.
 *
 * Covers:
 * - Happy path: valid name → colony name updated in DB, {ok:true}
 * - Success message: the lobby page shows lobby.rename_success after {ok:true}
 * - HTML injection guard: names containing <, > or { } must be rejected (422)
 * - XSS attempt: <script>…</script> in name → validation error, DB unchanged
 * - Name too short (< 2 chars) → validation error
 * - Name too long (> 50 chars) → validation error
 * - Empty name → validation error
 * - Unauthenticated request → 401
 *
 * Fixture: Colony 1 (Springfield) belongs to Bart (user_id=3).
 */
class ColonyRenameTest extends TestCase
{
    use RefreshDatabase;

    protected int $bartUserId = 3;

    protected int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    // ── Auth ─────────────────────────────────────────────────────────────────

    public function test_rename_requires_auth(): void
    {
        $this->patchJson(route('colony.rename'), ['name' => 'NewName'])
            ->assertUnauthorized();

        // DB unchanged
        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'Springfield']);
    }

    // ── Happy path ───────────────────────────────────────────────────────────

    public function test_rename_happy_path_updates_db(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => 'New Springfield'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'New Springfield']);
    }

    public function test_rename_answers_ok_json(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => 'Renamed'])
            ->assertOk()
            ->assertExactJson(['ok' => true]);
    }

    // ── Success message ──────────────────────────────────────────────────────

    public function test_rename_success_message_is_shown_by_the_lobby_page(): void
    {
        $lobby = file_get_contents(base_path('frontend/src/routes/(infra)/lobby/+page.svelte'));

        $this->assertStringContainsString("'/colony/name', 'PATCH'", $lobby);
        $this->assertStringEndsWith('/colony/name', route('colony.rename'));
        $this->assertStringContainsString("t('lobby.rename_success')", $lobby);
    }

    public function test_rename_success_message_contains_meaningful_text(): void
    {
        $this->app->setLocale('de');

        $this->assertStringContainsStringIgnoringCase(
            'aktualisiert',
            __('lobby.rename_success'),
            'Success message should confirm the update.'
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
            ->patchJson(route('colony.rename'), ['name' => '<script>alert(1)</script>'])
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'Springfield']);
    }

    /**
     * A name starting with < (opening angle bracket) must be rejected.
     */
    public function test_rename_rejects_opening_angle_bracket(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => '<Evil'])
            ->assertJsonValidationErrors('name');
    }

    /**
     * A name containing > must be rejected.
     */
    public function test_rename_rejects_closing_angle_bracket(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => 'Evil>Colony'])
            ->assertJsonValidationErrors('name');
    }

    /**
     * A name containing curly braces must be rejected (template injection guard).
     */
    public function test_rename_rejects_curly_braces(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => '{{evil}}'])
            ->assertJsonValidationErrors('name');
    }

    /**
     * A name containing square brackets must be rejected.
     */
    public function test_rename_rejects_square_brackets(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => '[injection]'])
            ->assertJsonValidationErrors('name');
    }

    // ── Boundary values ──────────────────────────────────────────────────────

    public function test_rename_rejects_empty_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => ''])
            ->assertJsonValidationErrors('name');
    }

    public function test_rename_rejects_single_character_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => 'X'])
            ->assertJsonValidationErrors('name');
    }

    public function test_rename_accepts_minimum_length_name(): void
    {
        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => 'AB'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => 'AB']);
    }

    public function test_rename_rejects_name_over_50_chars(): void
    {
        $tooLong = str_repeat('A', 51);

        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => $tooLong])
            ->assertJsonValidationErrors('name');
    }

    public function test_rename_accepts_name_at_exactly_50_chars(): void
    {
        $exactly50 = str_repeat('B', 50);

        $this->actingAs($this->makeUser($this->bartUserId))
            ->patchJson(route('colony.rename'), ['name' => $exactly50])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('glx_colonies', ['id' => $this->colonyId, 'name' => $exactly50]);
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function makeUser(int $userId): User
    {
        return User::where('user_id', $userId)->firstOrFail();
    }
}
