<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\RunObjective;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesForeignColony;
use Tests\TestCase;

class RunResultControllerTest extends TestCase
{
    use CreatesForeignColony;
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function user(): User
    {
        return User::where('user_id', self::USER_ID)->firstOrFail();
    }

    private function makeRun(string $status): Run
    {
        $run = Run::create([
            'user_id' => self::USER_ID,
            'colony_id' => self::COLONY_ID,
            'current_tick' => 20,
            'status' => $status,
            'phase' => 2,
        ]);

        RunObjective::create([
            'run_id' => $run->id,
            'task_key' => 'task_senior_advisors',
            'target_value' => 5,
            'current_value' => 5,
        ]);

        return $run;
    }

    public function test_completed_run_shows_result_screen(): void
    {
        $run = $this->makeRun('completed');

        $response = $this->actingAs($this->user())->get(route('run.result', ['id' => $run->id]));

        $response->assertOk();
        $response->assertJsonPath('id', $run->id);
        $response->assertJsonPath('status', 'completed');
        $response->assertJsonStructure(['score', 'objectives' => [['label', 'current', 'target', 'pct', 'done']]]);
        $response->assertJsonPath('objectives.0.pct', 100);
    }

    public function test_active_run_is_rejected_as_not_finished(): void
    {
        $run = $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('run.result', ['id' => $run->id]));

        $response->assertStatus(409);
        $response->assertJson(['ok' => false, 'error' => 'run_not_finished']);
    }

    public function test_other_users_run_is_forbidden(): void
    {
        $foreign = $this->createForeignColony();
        $run = Run::create([
            'user_id' => $foreign['user_id'],
            'colony_id' => $foreign['colony_id'],
            'current_tick' => 20,
            'status' => 'completed',
            'phase' => 2,
        ]);

        $response = $this->actingAs($this->user())->get(route('run.result', ['id' => $run->id]));

        $response->assertForbidden();
    }

    public function test_unknown_run_returns_404(): void
    {
        $response = $this->actingAs($this->user())->get(route('run.result', ['id' => 999999]));

        $response->assertNotFound();
    }
}
