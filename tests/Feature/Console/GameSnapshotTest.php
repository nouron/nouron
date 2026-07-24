<?php

namespace Tests\Feature\Console;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3; // Bart

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        Storage::fake('local');
    }

    public function test_unknown_action_fails(): void
    {
        $this->artisan('game:snapshot', ['action' => 'nuke', 'user' => 'bart'])
            ->expectsOutputToContain('Unknown action: nuke')
            ->assertExitCode(1);
    }

    public function test_unknown_user_by_name_fails(): void
    {
        $this->artisan('game:snapshot', ['action' => 'list', 'user' => 'nobody'])
            ->expectsOutputToContain('User not found: nobody')
            ->assertExitCode(1);
    }

    public function test_resolves_user_by_numeric_id(): void
    {
        $this->artisan('game:snapshot', ['action' => 'list', 'user' => (string) self::USER_ID])
            ->assertExitCode(0);
    }

    public function test_list_with_no_snapshots(): void
    {
        $this->artisan('game:snapshot', ['action' => 'list', 'user' => 'bart'])
            ->expectsOutputToContain("Keine Snapshots für 'Bart'.")
            ->assertExitCode(0);
    }

    public function test_save_then_list_shows_the_snapshot(): void
    {
        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'pre-blocker'])
            ->expectsOutputToContain("Snapshot 'pre-blocker' gespeichert")
            ->assertExitCode(0);

        Storage::assertExists('snapshots/'.self::USER_ID.'/pre-blocker.json');

        $this->artisan('game:snapshot', ['action' => 'list', 'user' => 'bart'])
            ->assertExitCode(0);
    }

    public function test_save_overwrite_requires_confirmation_and_aborts_on_no(): void
    {
        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'x'])->assertExitCode(0);

        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'x'])
            ->expectsConfirmation("Snapshot 'x' existiert bereits — überschreiben?", 'no')
            ->expectsOutputToContain('Aborted.')
            ->assertExitCode(0);
    }

    public function test_save_overwrite_with_yes_flag_skips_confirmation(): void
    {
        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'x'])->assertExitCode(0);

        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'x', '--yes' => true])
            ->expectsOutputToContain("Snapshot 'x' gespeichert")
            ->assertExitCode(0);
    }

    public function test_restore_missing_snapshot_fails(): void
    {
        $this->artisan('game:snapshot', ['action' => 'restore', 'user' => 'bart', 'label' => 'ghost'])
            ->expectsOutputToContain('Snapshot not found: ghost')
            ->assertExitCode(1);
    }

    public function test_restore_roundtrip_recreates_saved_state(): void
    {
        $originalRegolith = (int) DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 3)->value('amount');

        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'checkpoint', '--yes' => true])
            ->assertExitCode(0);

        // Mutate live state after the snapshot was taken.
        DB::table('colony_resources')->where('colony_id', 1)->where('resource_id', 3)->update(['amount' => 999999]);

        $this->artisan('game:snapshot', ['action' => 'restore', 'user' => 'bart', 'label' => 'checkpoint', '--yes' => true])
            ->expectsOutputToContain("Snapshot 'checkpoint' wiederhergestellt")
            ->assertExitCode(0);

        $restored = (int) DB::table('colony_resources')
            ->where('colony_id', 1)->where('resource_id', 3)->value('amount');
        $this->assertSame($originalRegolith, $restored);
    }

    public function test_restore_requires_confirmation_and_aborts_on_no(): void
    {
        $this->artisan('game:snapshot', ['action' => 'save', 'user' => 'bart', 'label' => 'checkpoint', '--yes' => true])
            ->assertExitCode(0);

        $this->artisan('game:snapshot', ['action' => 'restore', 'user' => 'bart', 'label' => 'checkpoint'])
            ->expectsConfirmation(
                "Aktuellen Spielstand von 'Bart' durch Snapshot 'checkpoint' (Sol 5) ersetzen?",
                'no'
            )
            ->expectsOutputToContain('Aborted.')
            ->assertExitCode(0);
    }
}
