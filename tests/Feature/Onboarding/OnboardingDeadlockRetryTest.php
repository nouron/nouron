<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Services\EventService;
use App\Services\OnboardingService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use PDOException;
use Tests\Concerns\UsesParallelTestDatabase;
use Tests\TestCase;

/**
 * R5b: concurrent registrations (and new runs) deadlock on MySQL — found with 8
 * parallel playtest bots, 7 died with SQLSTATE 40001 / 1213 inside
 * setupNewPlayer(). Both onboarding transactions must retry on a deadlock.
 *
 * Laravel only retries a TOP-LEVEL transaction (a nested one rethrows), so this
 * test cannot use RefreshDatabase's wrapping transaction: it migrates the test
 * database itself and marks it dirty afterwards, so the next RefreshDatabase
 * test re-migrates instead of seeing the committed rows.
 */
class OnboardingDeadlockRetryTest extends TestCase
{
    use UsesParallelTestDatabase;

    private int $calls = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useParallelTestDatabase();
        $this->artisan('migrate:fresh');
        app(ReferenceDataSeeder::class)->run();
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    /** First Nexus briefing of the transaction fails with a MySQL deadlock, later ones succeed. */
    private function deadlockOnce(): void
    {
        $real = app(EventService::class);
        $mock = Mockery::mock(EventService::class);
        $mock->shouldReceive('createNexusBriefing')->andReturnUsing(function (...$args) use ($real) {
            if ($this->calls++ === 0) {
                throw new QueryException('mysql', 'insert into `colony_log` ...', [], self::deadlockPdoException());
            }
            $real->createNexusBriefing(...$args);
        });
        $this->app->instance(EventService::class, $mock);
    }

    /** Shaped like pdo_mysql's: SQLSTATE as code, driver error code in errorInfo. */
    private static function deadlockPdoException(): PDOException
    {
        $e = new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction');
        $e->errorInfo = ['40001', 1213, 'Deadlock found when trying to get lock; try restarting transaction'];
        (new \ReflectionProperty(\Exception::class, 'code'))->setValue($e, '40001');

        return $e;
    }

    private function newUser(string $name): int
    {
        return (int) User::create([
            'username' => $name,
            'display_name' => $name,
            'role' => 'player',
            'password' => 'secret',
            'email' => "{$name}@example.invalid",
            'activated' => 1,
            'activation_key' => '',
        ])->user_id;
    }

    public function test_setup_new_player_retries_after_a_deadlock(): void
    {
        $userId = $this->newUser('retry_setup');
        $this->deadlockOnce();

        Log::spy();

        app(OnboardingService::class)->setupNewPlayer($userId);

        $this->assertSame(2, $this->calls, 'The deadlocked attempt must be retried once');
        // Only the error code and attempt are logged — a QueryException message carries
        // the SQL with its bindings (e-mail, password hash).
        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message, $context) => $message === 'db deadlock retry'
            && $context === ['attempt' => 1, 'sqlstate' => '40001', 'driver_code' => 1213]);
        $this->assertSame(1, DB::table('glx_colonies')->where('user_id', $userId)->count(), 'The rolled-back attempt must not leave a second colony');
        $this->assertSame(1, DB::table('runs')->where('user_id', $userId)->count());
    }

    /** Registration wraps setupNewPlayer() in its own (outer) transaction — that one must retry. */
    public function test_registration_retries_after_a_deadlock(): void
    {
        config(['auth.invite_code' => null]);
        $this->deadlockOnce();

        $this->post('/register', [
            'username' => 'retry_register',
            'email' => 'retry_register@example.invalid',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasNoErrors();

        $userIds = DB::table('user')->where('username', 'retry_register')->pluck('user_id');
        $this->assertCount(1, $userIds);
        $this->assertSame(1, DB::table('glx_colonies')->where('user_id', $userIds[0])->count());
        $this->assertSame(2, $this->calls);
    }

    public function test_reset_colony_retries_after_a_deadlock(): void
    {
        $userId = $this->newUser('retry_reset');
        $colonyId = (int) app(OnboardingService::class)->setupNewPlayer($userId)->id;
        $this->deadlockOnce();

        app(OnboardingService::class)->resetColonyToSol1($userId, $colonyId, rngSeed: 3);

        $this->assertSame(2, $this->calls, 'The deadlocked attempt must be retried once');
        $this->assertSame(1, DB::table('runs')->where('user_id', $userId)->where('status', 'active')->count());
        $this->assertSame(3, (int) DB::table('runs')->where('user_id', $userId)->where('status', 'active')->value('rng_seed'));
    }
}
