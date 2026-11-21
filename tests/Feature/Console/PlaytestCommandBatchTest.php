<?php

namespace Tests\Feature\Console;

use App\Console\Support\PlaytestDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * T58d (owner 2026-11-20): a bot batch shows its progress, keeps its reports in its own
 * folder (--batch, so batches never mix by timestamp) and can be resumed: started again
 * with the same batch name, runs that already have a report are skipped.
 */
class PlaytestCommandBatchTest extends TestCase
{
    use RefreshDatabase;

    private string $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batch = 'test-batch-'.uniqid();
        Process::fake();
        $this->partialMock(PlaytestDatabase::class, fn ($mock) => $mock->shouldReceive('reset')->andReturn('nouron_playtest'));
    }

    protected function tearDown(): void
    {
        $dir = storage_path('logs/playtest/'.$this->batch);
        array_map('unlink', glob($dir.'/*') ?: []);
        @rmdir($dir);
        parent::tearDown();
    }

    public function test_children_write_their_reports_into_the_batch_folder(): void
    {
        $this->artisan('game:playtest', ['--seeds' => '1', '--batch' => $this->batch]);

        $dir = storage_path('logs/playtest/'.$this->batch);
        Process::assertRanTimes(fn (PendingProcess $p) => ($p->environment['PLAYTEST_REPORT_DIR'] ?? null) === $dir, 1);
    }

    public function test_runs_with_a_report_in_the_batch_folder_are_skipped(): void
    {
        $dir = storage_path('logs/playtest/'.$this->batch);
        mkdir($dir, 0755, true);
        file_put_contents("{$dir}/default.utility-labor-1-20991231_000000.json", json_encode(['opening' => 'labor']));

        $this->artisan('game:playtest', ['--engine' => 'utility', '--openings' => 'labor,hangar', '--seeds' => '1', '--batch' => $this->batch])
            ->expectsOutputToContain('1 von 2 Läufen schon fertig');

        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_OPENING'] === 'hangar', 1);
        Process::assertRanTimes(fn (PendingProcess $p) => $p->environment['PLAYTEST_OPENING'] === 'labor', 0);
    }

    public function test_every_finished_run_prints_a_progress_line(): void
    {
        $this->artisan('game:playtest', ['--openings' => 'labor,hangar', '--seeds' => '1', '--batch' => $this->batch])
            ->expectsOutputToContain('[1/2]')
            ->expectsOutputToContain('[2/2]');
    }

    public function test_the_batch_name_and_evaluation_hint_are_printed(): void
    {
        $this->artisan('game:playtest', ['--seeds' => '1', '--batch' => $this->batch])
            ->expectsOutputToContain('storage/logs/playtest/'.$this->batch);
    }
}
