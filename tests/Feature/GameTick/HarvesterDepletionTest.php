<?php

namespace Tests\Feature\GameTick;

use App\Console\Commands\GameTick;
use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Harvester depletion mechanic (GDD §4c "Erschöpfungskurve und Umzugstakt",
 * freigegeben 2026-08-03) and the geology Kenntnis bonus (GDD §13.7).
 *
 *   Ertrag = Frischwert × (0,5 + 0,5 × Restvorkommen / resource_max)
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), harvester building_id=27.
 */
class HarvesterDepletionTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private const HARVESTER_ID = 27;

    private const RES_REGOLITH = 3;

    private const TRUST_RES_ID = 12;

    private const GEOLOGY_RESEARCH_ID = 92;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Neutral trust → production multiplier 1.0.
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::TRUST_RES_ID],
            ['amount' => 0]
        );
        DB::table('trust_events')->where('colony_id', self::COLONY_ID)->delete();

        DB::table('colony_researches')
            ->where('colony_id', self::COLONY_ID)
            ->where('research_id', self::GEOLOGY_RESEARCH_ID)
            ->delete();

        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => self::RES_REGOLITH],
            ['amount' => 0]
        );

        // Single Harvester instance on a regolith_normal tile (fresh_yield 18, resource_max 300).
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 1],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => 3, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => 3, 'r' => 0, 'ring' => 3,
            'tile_type' => 'regolith_normal', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 300, 'resource_max' => 300,
        ]);
    }

    private function makeUser(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function regolithAmount(): int
    {
        return (int) DB::table('colony_resources')
            ->where('colony_id', self::COLONY_ID)
            ->where('resource_id', self::RES_REGOLITH)
            ->value('amount');
    }

    private function tileRemaining(): int
    {
        return (int) DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)
            ->value('resource_amount');
    }

    // ── Teil A — pure formula ────────────────────────────────────────────────

    public function test_harvester_yield_at_full_reserves_equals_fresh_value(): void
    {
        // regolith_normal: fresh 23, resource_max 300, remaining 300 → ratio 1.0 → 23.
        $this->assertSame(23, GameTick::harvesterYield('regolith_normal', 300, 300, 0));
    }

    public function test_harvester_yield_near_depletion_approaches_half_fresh_value(): void
    {
        // remaining=10/300 → ratio ~0.033 → 23*(0.5+0.5*0.033) ≈ 11.88 → round 12.
        $this->assertSame(12, GameTick::harvesterYield('regolith_normal', 10, 300, 0));
    }

    public function test_harvester_yield_is_zero_when_exhausted(): void
    {
        $this->assertSame(0, GameTick::harvesterYield('regolith_normal', 0, 300, 0));
    }

    public function test_harvester_yield_never_returns_negative_for_over_cap_remaining(): void
    {
        // Legacy tile with resource_amount > resource_max (pre-2026-08-03 seed) — ratio
        // clamps at 1.0, never exceeds the fresh value.
        $this->assertSame(23, GameTick::harvesterYield('regolith_normal', 500, 300, 0));
    }

    // ── Teil A — integration via game:tick ──────────────────────────────────

    public function test_tick_credits_regolith_from_tile_at_full_reserves(): void
    {
        Artisan::call('game:tick', ['--tick' => 20001]);

        $this->assertSame(23, $this->regolithAmount());
        $this->assertSame(300 - 23, $this->tileRemaining());
    }

    public function test_tick_credits_reduced_yield_near_depletion(): void
    {
        DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)
            ->update(['resource_amount' => 10]);

        Artisan::call('game:tick', ['--tick' => 20002]);

        $this->assertSame(12, $this->regolithAmount());
        $this->assertSame(0, $this->tileRemaining(), 'yield (12) exceeds remaining (10) — clamps to 0, never negative');
    }

    public function test_tick_credits_nothing_once_tile_exhausted(): void
    {
        DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)
            ->update(['resource_amount' => 0]);

        Artisan::call('game:tick', ['--tick' => 20003]);

        $this->assertSame(0, $this->regolithAmount());
        $this->assertSame(0, $this->tileRemaining());
    }

    public function test_tick_clamps_stale_resource_max_down_to_current_config(): void
    {
        // Legacy tile seeded before the 500/300/160 reduction (old regolith_normal max: 500).
        DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)
            ->update(['resource_amount' => 500, 'resource_max' => 500]);

        Artisan::call('game:tick', ['--tick' => 20004]);

        $this->assertSame(23, $this->regolithAmount(), 'yield must not exceed fresh value even with a stale over-cap remaining');
        $this->assertSame(
            300,
            (int) DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)->value('resource_max'),
            'resource_max must be clamped down to the current config value'
        );
    }

    // ── Teil D — geology Kenntnis bonus ──────────────────────────────────────

    public function test_harvester_yield_pure_function_adds_geology_bonus(): void
    {
        $this->assertSame(23 + 3, GameTick::harvesterYield('regolith_normal', 300, 300, 1));
        $this->assertSame(23 + 6, GameTick::harvesterYield('regolith_normal', 300, 300, 2));
        $this->assertSame(23 + 8, GameTick::harvesterYield('regolith_normal', 300, 300, 3));
        $this->assertSame(23 + 12, GameTick::harvesterYield('regolith_normal', 300, 300, 5));
        // Cap: level beyond the configured curve does not add further bonus.
        $this->assertSame(23 + 12, GameTick::harvesterYield('regolith_normal', 300, 300, 99));
    }

    public function test_tick_applies_geology_bonus_once_per_colony_not_per_instance(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::GEOLOGY_RESEARCH_ID],
            ['level' => 2, 'status_points' => 20, 'ap_spend' => 0]
        );

        // Second Harvester instance on a regolith_poor tile (fresh 15, max 160).
        DB::table('colony_tiles')->where('colony_id', self::COLONY_ID)->where('q', -3)->where('r', 0)->delete();
        DB::table('colony_tiles')->insert([
            'colony_id' => self::COLONY_ID, 'q' => -3, 'r' => 0, 'ring' => 3,
            'tile_type' => 'regolith_poor', 'is_explored' => 1, 'is_colony_zone' => 0, 'is_deep_scanned' => 0,
            'resource_amount' => 160, 'resource_max' => 160,
        ]);
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => self::HARVESTER_ID, 'instance_id' => 2],
            ['level' => 1, 'status_points' => 16, 'ap_spend' => 0, 'tile_x' => -3, 'tile_y' => 0, 'pending_until_tick' => null]
        );

        Artisan::call('game:tick', ['--tick' => 20005]);

        // Without any bonus: 23 (normal, instance 1) + 15 (poor, instance 2) = 38.
        // Geology level 2 bonus is +6 applied ONCE per colony → 44 total.
        // (If it were applied per instance instead, the total would be 38 + 12 = 50.)
        $this->assertSame(44, $this->regolithAmount());
    }

    public function test_geology_bonus_not_credited_when_no_harvester_active(): void
    {
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::GEOLOGY_RESEARCH_ID],
            ['level' => 2, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('colony_tiles')
            ->where('colony_id', self::COLONY_ID)->where('q', 3)->where('r', 0)
            ->update(['resource_amount' => 0]);

        Artisan::call('game:tick', ['--tick' => 20006]);

        $this->assertSame(0, $this->regolithAmount(), 'no active harvester → no yield, no flat geology trickle');
    }
}
