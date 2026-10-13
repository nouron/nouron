<?php

namespace Tests\Feature\Console;

use App\Console\Commands\Playtest;
use App\Console\Support\PlaytestDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\RunReport;
use Tests\TestCase;

/** Spec S1: game:playtest --engine passes PLAYTEST_ENGINE; reports of two engines never collide. */
class PlaytestCommandEngineTest extends TestCase
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

    public function test_unknown_engine_is_refused_before_the_database_is_touched(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldNotReceive('reset'));

        $this->artisan('game:playtest', ['--engine' => 'magic', '--seeds' => '1'])
            ->expectsOutputToContain('Unknown engine: magic')
            ->assertFailed();

        Process::assertNothingRan();
    }

    public function test_engine_defaults_to_legacy(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--seeds' => '1']);

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_ENGINE'] === 'legacy', 1);
    }

    public function test_utility_engine_is_passed_to_every_child(): void
    {
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->once()->andReturn('nouron_playtest'));

        $this->artisan('game:playtest', ['--engine' => 'utility', '--openings' => 'labor,hangar', '--seeds' => '1'])
            ->expectsOutputToContain('default.utility/labor=1');

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_ENGINE'] === 'utility', 2);
    }

    public function test_file_prefix_keeps_legacy_names_unchanged(): void
    {
        $this->assertSame('default', RunReport::filePrefix('default', 'legacy'));
        $this->assertSame('thrifty.utility', RunReport::filePrefix('thrifty', 'utility'));
    }

    public function test_run_report_carries_the_engine_in_json_and_filename(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7, 'default', 'hangar', 'utility');

        $data = $report->build($bot);
        $this->assertSame('utility', $data['engine']);

        $path = $report->write($data);
        $this->createdReports[] = $path;
        $this->assertStringStartsWith('default.utility-hangar-7-', basename($path));
    }

    public function test_legacy_report_name_and_engine_default(): void
    {
        $bot = BotSession::boot($this, seed: 7);
        $report = new RunReport(7, 'default', 'hangar');

        $data = $report->build($bot);
        $this->assertSame('legacy', $data['engine']);
        $path = $report->write($data);
        $this->createdReports[] = $path;
        $this->assertStringStartsWith('default-hangar-7-', basename($path));
    }

    public function test_latest_report_for_never_mixes_engines(): void
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // Seed 987655 never occurs in real runs.
        $legacy = "{$dir}/default-labor-987655-29990101_000000.json";
        $utility = "{$dir}/default.utility-labor-987655-29990101_000001.json";
        file_put_contents($legacy, json_encode(['engine' => 'legacy']));
        file_put_contents($utility, json_encode(['engine' => 'utility']));
        $this->createdReports = [$legacy, $utility];

        $command = $this->app->make(Playtest::class);
        $method = new \ReflectionMethod($command, 'latestReportFor');

        $this->assertSame(['engine' => 'legacy'], $method->invoke($command, 'default', 'labor', '987655'));
        $this->assertSame(['engine' => 'legacy'], $method->invoke($command, 'default', 'labor', '987655', 'legacy'));
        $this->assertSame(['engine' => 'utility'], $method->invoke($command, 'default', 'labor', '987655', 'utility'));
    }
}
