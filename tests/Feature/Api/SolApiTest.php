<?php

namespace Tests\Feature\Api;

use App\Models\Run;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Ending a Sol from the SvelteKit frontend (ADR 0006 stage 2d): same SolController
 * as the Blade Sol button, reached with a Sanctum bearer token.
 */
class SolApiTest extends TestCase
{
    use RefreshDatabase;

    private const BART_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_sol_endpoints_require_token(): void
    {
        $this->getJson('/api/v1/sol/remaining-ap')->assertUnauthorized();
        $this->postJson('/api/v1/sol/next')->assertUnauthorized();
    }

    public function test_remaining_ap_reports_total(): void
    {
        Sanctum::actingAs(User::find(self::BART_ID));

        $this->getJson('/api/v1/sol/remaining-ap')->assertOk()->assertJsonStructure(['total']);
    }

    public function test_next_advances_the_run_and_returns_the_report(): void
    {
        Sanctum::actingAs(User::find(self::BART_ID));
        $tickBefore = (int) Run::where('user_id', self::BART_ID)->where('status', 'active')->value('current_tick');

        $this->postJson('/api/v1/sol/next')
            ->assertOk()
            ->assertJsonStructure(['completed_sol', 'next_sol', 'groups']);

        $this->assertSame(
            $tickBefore + 1,
            (int) Run::where('user_id', self::BART_ID)->where('status', 'active')->value('current_tick')
        );
    }

    public function test_next_while_another_advance_runs_answers_409(): void
    {
        Sanctum::actingAs(User::find(self::BART_ID));
        $runId = Run::where('user_id', self::BART_ID)->where('status', 'active')->value('id');
        $lock = Cache::lock("run:{$runId}:sol-advance", 120);
        $lock->get();

        $this->postJson('/api/v1/sol/next')->assertStatus(409);

        $lock->release();
    }
}
