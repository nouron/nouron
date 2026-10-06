<?php

namespace Tests\Feature\Console;

use App\Console\Commands\Playtest;
use App\Console\Support\PlaytestDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\RunReport;
use Tests\TestCase;

/**
 * T30 step 0: game:playtest runs profile x opening x seed combinations, passes
 * PLAYTEST_OPENING to the children, and report files/JSON carry the opening so two
 * openings with the same seed never overwrite or get confused with each other.
 */
class PlaytestCommandOpeningsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $createdReports = [];

    protected function tearDown(): void
    {
        foreach ($this->createdReports as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    public function test_run_report_carries_the_opening_in_json_and_filename(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7, 'default', 'hangar');

        $data = $report->build($bot);
        $this->assertSame('hangar', $data['opening']);

        $path = $report->write($data);
        $this->createdReports[] = $path;
        $this->assertStringContainsString('default-hangar-7-', basename($path));
    }

    public function test_run_report_opening_defaults_to_auto(): void
    {
        $bot = BotSession::boot($this, seed: 7);

        $this->assertSame('auto', (new RunReport(7))->build($bot)['opening']);
    }

    public function test_unknown_opening_is_refused_before_the_database_is_touched(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldNotReceive('reset'));

        $this->artisan('game:playtest', ['--openings' => 'forge', '--seeds' => '1'])
            ->expectsOutputToContain('Unknown opening(s): forge. Allowed: auto, labor, hangar, cantina')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_each_opening_gets_its_own_child_with_playtest_opening(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--openings' => 'labor,hangar', '--seeds' => '1', '--concurrency' => 2])
            ->expectsOutputToContain('default/labor=1');

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_OPENING'] === 'labor', 1);
        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_OPENING'] === 'hangar', 1);
    }

    public function test_duplicate_profiles_openings_and_seeds_start_one_child_per_distinct_combination(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--profiles' => 'default,default', '--openings' => 'labor,labor', '--seeds' => '1,1', '--concurrency' => 2]);

        Process::assertRanTimes(fn (PendingProcess $p) => true, 1);
    }

    public function test_default_opening_is_auto(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1']);

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_OPENING'] === 'auto', 1);
    }

    public function test_latest_report_for_never_returns_another_openings_report(): void
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $labor = "{$dir}/default-labor-1-29990101_000000.json";
        $hangar = "{$dir}/default-hangar-1-29990101_000001.json";
        file_put_contents($labor, json_encode(['opening' => 'labor']));
        file_put_contents($hangar, json_encode(['opening' => 'hangar']));
        $this->createdReports = [$labor, $hangar];

        $command = $this->app->make(Playtest::class);
        $method = new \ReflectionMethod($command, 'latestReportFor');

        $this->assertSame(['opening' => 'labor'], $method->invoke($command, 'default', 'labor', '1'));
        $this->assertSame(['opening' => 'hangar'], $method->invoke($command, 'default', 'hangar', '1'));
        $this->assertNull($method->invoke($command, 'default', 'cantina', '1'));
    }

    // --- --until-sol (Task 5) ---

    public function test_until_sol_is_passed_to_every_child(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--openings' => 'labor,hangar', '--seeds' => '1', '--until-sol' => '20']);

        Process::assertRanTimes(fn (PendingProcess $p) => ($p->environment['PLAYTEST_UNTIL_SOL'] ?? null) === '20', 2);
    }

    public function test_without_until_sol_the_env_variable_is_not_set(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1']);

        Process::assertRanTimes(fn (PendingProcess $p) => ! array_key_exists('PLAYTEST_UNTIL_SOL', $p->environment), 1);
    }

    #[DataProvider('invalidUntilSol')]
    public function test_invalid_until_sol_is_refused_before_the_database_is_touched(string $value): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldNotReceive('reset'));

        $this->artisan('game:playtest', ['--until-sol' => $value, '--seeds' => '1'])
            ->expectsOutputToContain('Invalid --until-sol')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public static function invalidUntilSol(): array
    {
        return [['0'], ['abc'], ['500'], ['4'], ['101'], ['12.5'], ['-3']];
    }

    public function test_run_report_marks_truncated_runs_only(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7);

        $this->assertSame(20, $report->build($bot, 20)['truncated_at_sol']);
        $this->assertArrayNotHasKey('truncated_at_sol', $report->build($bot));
    }
}
