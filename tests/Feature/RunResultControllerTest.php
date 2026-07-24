<?php

namespace Tests\Feature;

use App\Models\Run;
use App\Models\RunObjective;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunResultControllerTest extends TestCase
{
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
        $response->assertViewHas('score');
        $response->assertViewHas('objectives');
    }

    public function test_active_run_redirects_to_colony_view(): void
    {
        $run = $this->makeRun('active');

        $response = $this->actingAs($this->user())->get(route('run.result', ['id' => $run->id]));

        $response->assertRedirect(route('colony.view'));
    }

    public function test_other_users_run_is_forbidden(): void
    {
        $otherUserId = 1; // Homer
        $run = Run::create([
            'user_id' => $otherUserId,
            'colony_id' => 2,
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

    public function test_admin_dev_preview_shows_result_for_active_run(): void
    {
        $run = $this->makeRun('active');
        $admin = $this->user();
        $admin->role = 'admin';
        $admin->save();

        $response = $this->actingAs($admin)
            ->get(route('run.result', ['id' => $run->id]).'?preview=1&outcome=failed');

        $response->assertOk();
        $response->assertViewHas('run', fn (Run $r) => $r->status === 'failed');
    }
}
