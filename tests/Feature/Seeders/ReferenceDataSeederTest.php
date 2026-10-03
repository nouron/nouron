<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReferenceDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_reference_data_without_any_player_data(): void
    {
        $this->seed(ReferenceDataSeeder::class);

        $this->assertSame(13, DB::table('buildings')->count());
        $this->assertSame(6, DB::table('resources')->count());
        $this->assertSame(0, DB::table('user')->count());
        $this->assertSame(0, DB::table('glx_colonies')->count());
        // config values are applied (SyncConfig runs inside the seeder): command center
        $this->assertSame((int) config('buildings.commandCenter.max_level'), (int) DB::table('buildings')->where('id', 25)->value('max_level'));
    }

    public function test_second_run_is_idempotent_and_keeps_player_data(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $before = DB::table('building_costs')->orderBy('building_id')->orderBy('resource_id')->get()->toArray();

        DB::table('user')->insert([
            'user_id' => 20,
            'username' => 'Seeder Player',
            'display_name' => '',
            'role' => 'player',
            'password' => bcrypt('secret'),
            'email' => 'seeder-player@nouron.de',
            'activated' => 1,
            'activation_key' => '',
        ]);
        DB::table('glx_colonies')->insert([
            'id' => 2,
            'name' => 'Seeder Colony',
            'user_id' => 20,
            'is_primary' => 1,
        ]);
        DB::table('colony_buildings')->insert([
            'colony_id' => 2,
            'building_id' => 25,
            'level' => 1,
            'status_points' => 20,
            'ap_spend' => 0,
        ]);

        $this->seed(ReferenceDataSeeder::class);

        $this->assertEquals($before, DB::table('building_costs')->orderBy('building_id')->orderBy('resource_id')->get()->toArray());
        $this->assertSame(1, DB::table('colony_buildings')->count());
        $this->assertSame(1, DB::table('user')->count());
        $this->assertSame(1, DB::table('glx_colonies')->count());
        $this->assertSame(13, DB::table('buildings')->count());
    }

    public function test_second_run_restores_drifted_reference_values(): void
    {
        $this->seed(ReferenceDataSeeder::class);
        $original = DB::table('buildings')->where('id', 25)->value('name');

        DB::table('buildings')->where('id', 25)->update(['name' => 'drifted_name']);
        $this->seed(ReferenceDataSeeder::class);

        $this->assertSame($original, DB::table('buildings')->where('id', 25)->value('name'));
    }
}
