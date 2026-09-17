<?php

namespace Tests\Feature\User;

/**
 * UserController::codex() feature tests (Charakter-Kodex screen, A42).
 *
 * User-persistent, account-bound view of the Cantina cast's unlocked lore
 * entries — not colony-/run-scoped, so it lives under /user, not /colony.
 *
 * Covered scenarios:
 *  - test_codex_requires_auth
 *  - test_codex_shows_page
 *  - test_codex_marks_unlocked_entries
 */

use App\Models\User;
use App\Services\CharacterCodexService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCodexControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID_BART = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    public function test_codex_requires_auth(): void
    {
        $response = $this->get(route('user.codex'));
        $response->assertRedirect(route('login'));
    }

    public function test_codex_shows_page(): void
    {
        $response = $this->actingAs($this->bart())->get(route('user.codex'));

        $response->assertOk();
        $response->assertViewIs('user.codex');
        $response->assertViewHasAll(['characters']);
    }

    public function test_codex_marks_unlocked_entries(): void
    {
        $this->app->make(CharacterCodexService::class)->recordProgress(self::USER_ID_BART, 'veteran');

        $response = $this->actingAs($this->bart())->get(route('user.codex'));

        $response->assertOk();
        $characters = $response->viewData('characters');
        $veteran = collect($characters)->firstWhere('slug', 'veteran');

        $this->assertNotNull($veteran);
        $this->assertSame([1], $veteran['unlocked']);
    }
}
