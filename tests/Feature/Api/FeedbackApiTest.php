<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** In-game feedback from the SvelteKit frontend (ADR 0006 stage 2e): same FeedbackController as Blade. */
class FeedbackApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_feedback_requires_token(): void
    {
        $this->postJson('/api/v1/feedback', ['category' => 'bug', 'message' => 'x'])->assertUnauthorized();
    }

    public function test_feedback_is_stored_with_run_and_page(): void
    {
        Sanctum::actingAs(User::find(3));

        $this->postJson('/api/v1/feedback', [
            'category' => 'idea',
            'message' => 'Mehr Sterne',
            'page' => '/techtree',
        ])->assertCreated();

        $this->assertDatabaseHas('feedback', [
            'user_id' => 3,
            'category' => 'idea',
            'message' => 'Mehr Sterne',
            'page' => '/techtree',
        ]);
    }
}
