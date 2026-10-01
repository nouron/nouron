<?php

namespace Tests\Feature\Console;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

/**
 * R3 (Closed-Beta-Vorbereitung): development commands wipe or rewrite game
 * state, and the seeder plays in test accounts with a known password hash.
 * None of them may ever run against the production database.
 */
class ProductionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function asProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function devCommands(): array
    {
        return [
            'db:reset' => ['db:reset', ['--force' => true]],
            'game:reset-player' => ['game:reset-player', ['user' => 'Bart', '--yes' => true, '--scenario' => 'fresh']],
            'colony:seed-demo' => ['colony:seed-demo', ['colony_id' => 1]],
            'game:playtest' => ['game:playtest', ['--seeds' => '1']],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    #[DataProvider('devCommands')]
    public function test_dev_command_refuses_to_run_in_production(string $command, array $args): void
    {
        $this->asProduction();
        $before = DB::table('colony_buildings')->count().'/'.DB::table('user')->count();

        $this->artisan($command, $args)
            ->expectsOutputToContain('production')
            ->assertExitCode(1);

        $this->assertSame($before, DB::table('colony_buildings')->count().'/'.DB::table('user')->count());
    }

    public function test_database_seeder_refuses_to_run_in_production(): void
    {
        $this->asProduction();

        $this->expectException(RuntimeException::class);
        $this->app->make(DatabaseSeeder::class)->run();
    }

    public function test_dev_command_still_runs_outside_production(): void
    {
        $this->artisan('colony:seed-demo', ['colony_id' => 1])->assertExitCode(0);
    }
}
