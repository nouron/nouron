<?php

namespace Tests\Feature\Feedback;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R15 (closed beta): in-game feedback. Players send free text with a category;
 * the server attaches the context itself (user, active run, Sol, page, user
 * agent) so testers don't have to describe where they were. The owner reads
 * everything in an admin overview.
 *
 * Fixture: Bart (user 3, role admin) with active run 1 at current_tick 5;
 * Marge (user 1, role player) without a run.
 */
class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::findOrFail(3);
    }

    private function marge(): User
    {
        return User::findOrFail(1);
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category' => 'bug',
            'message' => 'Der Sol-Button reagiert nach dem Bericht nicht mehr.',
            'page' => '/colony/view',
        ], $overrides);
    }

    // ── store ──────────────────────────────────────────────────────────────

    public function test_guest_cannot_send_feedback(): void
    {
        $this->postJson(route('feedback.store'), $this->payload())->assertUnauthorized();
        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_feedback_is_stored_with_server_side_context(): void
    {
        DB::table('runs')->where('id', 1)->update(['current_tick' => 41]);

        $this->actingAs($this->bart())
            ->withHeader('User-Agent', 'BetaBrowser/1.0')
            ->postJson(route('feedback.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('feedback', [
            'user_id' => 3,
            'run_id' => 1,
            'sol' => 42, // current_tick 41 → Sol 42 (same clock as the Sol chip)
            'category' => 'bug',
            'message' => 'Der Sol-Button reagiert nach dem Bericht nicht mehr.',
            'page' => '/colony/view',
            'user_agent' => 'BetaBrowser/1.0',
        ]);
    }

    public function test_feedback_without_active_run_has_no_run_context(): void
    {
        $this->actingAs($this->marge())
            ->postJson(route('feedback.store'), $this->payload(['category' => 'idea']))
            ->assertCreated();

        $this->assertDatabaseHas('feedback', ['user_id' => 1, 'run_id' => null, 'sol' => null, 'category' => 'idea']);
    }

    public function test_client_cannot_inject_foreign_context(): void
    {
        $this->actingAs($this->marge())
            ->postJson(route('feedback.store'), $this->payload(['user_id' => 3, 'run_id' => 1, 'sol' => 99]))
            ->assertCreated();

        $this->assertDatabaseHas('feedback', ['user_id' => 1, 'run_id' => null, 'sol' => null]);
    }

    public function test_page_is_stored_as_path_only(): void
    {
        $this->actingAs($this->bart())
            ->postJson(route('feedback.store'), $this->payload(['page' => 'https://evil.example/colony/bar?x=1']))
            ->assertCreated();

        $this->assertDatabaseHas('feedback', ['page' => '/colony/bar']);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->actingAs($this->bart())
            ->postJson(route('feedback.store'), $this->payload(['category' => 'spam']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }

    public function test_empty_or_overlong_message_is_rejected(): void
    {
        $this->actingAs($this->bart())
            ->postJson(route('feedback.store'), $this->payload(['message' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->actingAs($this->bart())
            ->postJson(route('feedback.store'), $this->payload(['message' => str_repeat('a', 2001)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->assertDatabaseCount('feedback', 0);
    }

    public function test_sending_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->bart())->postJson(route('feedback.store'), $this->payload())->assertCreated();
        }

        $this->actingAs($this->bart())->postJson(route('feedback.store'), $this->payload())->assertStatus(429);
    }

    // ── admin overview ─────────────────────────────────────────────────────

    public function test_admin_sees_the_feedback_overview(): void
    {
        $this->actingAs($this->marge())->postJson(route('feedback.store'), $this->payload(['message' => 'Bar-Text abgeschnitten']));

        $this->actingAs($this->bart())
            ->get(route('admin.feedback'))
            ->assertOk()
            ->assertSee('Bar-Text abgeschnitten')
            ->assertSee('Marge');
    }

    public function test_player_cannot_open_the_feedback_overview(): void
    {
        $this->actingAs($this->marge())->get(route('admin.feedback'))->assertForbidden();
    }

    // ── UI ─────────────────────────────────────────────────────────────────

    public function test_feedback_button_is_on_pages_for_logged_in_users(): void
    {
        $this->actingAs($this->marge())
            ->get(route('lobby'))
            ->assertOk()
            ->assertSee('data-feedback-dialog', false);
    }

    public function test_feedback_button_is_in_the_colony_layout(): void
    {
        $this->actingAs($this->bart())
            ->get(route('colony.view'))
            ->assertOk()
            ->assertSee('data-feedback-dialog', false);
    }
}
