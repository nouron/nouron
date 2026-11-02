<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Read-only pages for the SvelteKit frontend (ADR 0006 stage 4e): Protokoll, Nexus-DB,
 * Codex — same controllers and data as the Blade pages.
 */
class ReadPagesApiTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_pages_require_token(): void
    {
        foreach (['/api/v1/comm-log', '/api/v1/nexus-db', '/api/v1/codex'] as $path) {
            $this->getJson($path)->assertUnauthorized();
        }
    }

    public function test_comm_log_returns_display_ready_entries(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));
        DB::table('colony_log')->insert([
            ['user' => self::USER_ID, 'tick' => 3, 'event' => 'colony.tile_explored', 'area' => 'colony', 'parameters' => '{}', 'is_read' => true],
            ['user' => self::USER_ID, 'tick' => 4, 'event' => 'run.nexus_warning_sol30', 'area' => 'nexus', 'parameters' => '{}', 'is_read' => false],
        ]);

        $log = $this->getJson('/api/v1/comm-log')->assertOk();
        $log->assertJsonPath('tab', 'log')->assertJsonStructure(['entries' => [0 => ['sol', 'event', 'icon', 'text', 'segments']], 'unreadCount']);
        $this->assertGreaterThanOrEqual(1, $log->json('unreadCount'));

        $nexus = $this->getJson('/api/v1/comm-log?tab=nexus')->assertOk();
        $nexus->assertJsonPath('tab', 'nexus')->assertJsonStructure(['entries' => [0 => ['sol', 'title', 'body', 'badge', 'badge_class']]]);
        // Opening the Nexus tab marks it read, like the Blade page.
        $this->assertSame(0, $this->getJson('/api/v1/comm-log')->json('unreadCount'));
    }

    public function test_nexus_db_and_codex(): void
    {
        Sanctum::actingAs(User::find(self::USER_ID));

        $this->getJson('/api/v1/nexus-db')->assertOk()->assertJsonStructure(['first_visit']);
        $this->getJson('/api/v1/codex')->assertOk()->assertJsonStructure(['characters' => [0 => ['slug', 'unlocked', 'max_entries', 'role']]]);
    }
}
