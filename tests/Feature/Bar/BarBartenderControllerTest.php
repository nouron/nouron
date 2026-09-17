<?php

namespace Tests\Feature\Bar;

/**
 * BarController::talkToBartender() feature tests (Cantina-Barkeeper Tomas, A40).
 *
 * Covered scenarios:
 *  AUTH GUARD
 *    - test_talk_requires_auth
 *
 *  TALK
 *    - test_talk_returns_ok_with_success_payload
 *    - test_talk_returns_cooldown_error_within_same_tick
 *    - test_talk_returns_validation_error_without_knowledge_id
 */

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarBartenderControllerTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID_BART = 3;

    private const KNOWLEDGE_ID = 96; // defense

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function bart(): User
    {
        return User::find(self::USER_ID_BART);
    }

    private function mockTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
    }

    public function test_talk_requires_auth(): void
    {
        $response = $this->postJson(route('colony.bar.talk-to-bartender'), ['knowledge_id' => self::KNOWLEDGE_ID]);
        $response->assertStatus(401);
    }

    public function test_talk_returns_ok_with_success_payload(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.talk-to-bartender'), ['knowledge_id' => self::KNOWLEDGE_ID]);

        $response->assertOk()
            ->assertJson(['ok' => true])
            ->assertJsonStructure(['ok', 'ap_added', 'interaction_count']);
    }

    public function test_talk_returns_cooldown_error_within_same_tick(): void
    {
        $this->mockTick(10);

        $this->actingAs($this->bart())
            ->postJson(route('colony.bar.talk-to-bartender'), ['knowledge_id' => self::KNOWLEDGE_ID]);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.talk-to-bartender'), ['knowledge_id' => self::KNOWLEDGE_ID]);

        $response->assertStatus(422)
            ->assertJson(['ok' => false, 'error' => 'cooldown_active']);
    }

    public function test_talk_returns_validation_error_without_knowledge_id(): void
    {
        $this->mockTick(10);

        $response = $this->actingAs($this->bart())
            ->postJson(route('colony.bar.talk-to-bartender'), []);

        $response->assertStatus(422);
    }
}
