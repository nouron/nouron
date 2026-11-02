<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Onboarding hint dismiss for the SvelteKit frontend (ADR 0006 stage 2c): same
 * ColonyController::dismissHint as the Blade hint bar, also confirms first-visit popups.
 */
class HintApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_dismiss_requires_token(): void
    {
        $this->postJson('/api/v1/hint/dismiss', ['hint_key' => 'hint_1'])->assertUnauthorized();
    }

    public function test_dismiss_returns_next_hint(): void
    {
        Sanctum::actingAs(User::find(3));

        $this->postJson('/api/v1/hint/dismiss', ['hint_key' => 'hint_1'])
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['hint']);
    }

    public function test_dismissing_the_visit_key_ends_the_techtree_first_visit(): void
    {
        Sanctum::actingAs(User::find(3));
        $this->assertTrue($this->getJson('/api/v1/techtree')->json('first_visit'));

        $this->postJson('/api/v1/hint/dismiss', ['hint_key' => 'visit_techtree'])->assertOk();

        $this->assertFalse($this->getJson('/api/v1/techtree')->json('first_visit'));
    }
}
