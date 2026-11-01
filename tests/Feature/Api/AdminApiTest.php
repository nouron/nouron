<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Admin screens for the SvelteKit frontend (ADR 0006 stage 5c): feedback overview and debug bar.
 * Fixture: Bart (user 3, admin) with active run 1 at Sol 5; Marge (user 1, player) without a run.
 */
class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function as(int $userId): void
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs(User::findOrFail($userId));
    }

    public function test_shell_says_whether_the_user_is_admin(): void
    {
        $this->as(3);
        $this->getJson('/api/v1/shell')->assertJson(['is_admin' => true]);
        $this->as(1);
        $this->getJson('/api/v1/shell')->assertJson(['is_admin' => false]);
    }

    public function test_players_cannot_open_admin_endpoints(): void
    {
        $this->as(1);
        $this->getJson('/api/v1/admin/feedback')->assertForbidden();
        $this->getJson('/api/v1/admin/debug')->assertForbidden();
    }

    public function test_admin_sees_feedback_newest_first_with_context(): void
    {
        $this->as(1);
        $this->postJson('/api/v1/feedback', ['category' => 'bug', 'message' => 'Erster Eintrag', 'page' => '/colony']);
        $this->postJson('/api/v1/feedback', ['category' => 'idea', 'message' => 'Bar-Text abgeschnitten', 'page' => '/cantina']);

        $this->as(3);
        $this->getJson('/api/v1/admin/feedback')
            ->assertOk()
            ->assertJsonPath('data.0.message', 'Bar-Text abgeschnitten')
            ->assertJsonPath('data.0.username', 'Marge')
            ->assertJsonPath('data.0.category', __('feedback.category_idea'))
            ->assertJsonPath('data.0.page', '/cantina')
            ->assertJsonStructure(['data' => [['created_at', 'run_id', 'sol']], 'current_page', 'last_page']);
    }

    public function test_admin_debug_shows_run_bypass_and_config(): void
    {
        $this->as(3);
        $this->getJson('/api/v1/admin/debug')
            ->assertOk()
            ->assertJson([
                'run' => ['id' => 1, 'current_tick' => 5, 'status' => 'active'],
                'tick_limit' => config('game.run.tick_limit'),
                'bypass' => config('game.bypass'),
                'env' => app()->environment(),
            ])
            ->assertJsonStructure(['config' => ['Tick', 'Supply', 'Credits', 'Trust Events']]);
    }
}
