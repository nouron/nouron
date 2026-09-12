<?php

namespace Tests\Feature\GameTick;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GameTick step 10b — Cantina-Begegnungspool contract payouts (A35).
 *
 * An accepted 'contract' encounter pays credits_amount every tick from
 * acceptance up to and including ends_tick, then stops (resolved=true).
 *
 * Covered scenarios:
 *  - Active contract pays credits_amount on a tick within its window
 *  - Contract is marked resolved once its ends_tick has been paid
 *  - A resolved contract no longer pays on later ticks
 */
class GameTickBarEncounterTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;   // Bart

    private const COLONY_ID = 1;   // Springfield

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        // Neutralize passive Credits generation and advisor upkeep so the
        // assertions below isolate the contract payout in isolation.
        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', 25) // command center
            ->update(['level' => 0]);
        DB::table('advisors')->where('colony_id', self::COLONY_ID)->delete();
    }

    private function getCredits(): int
    {
        return (int) DB::table('user_resources')
            ->where('user_id', self::USER_ID)
            ->value('credits');
    }

    private function insertContract(int $endsTick, bool $resolved = false): int
    {
        return DB::table('bar_encounters')->insertGetId([
            'colony_id' => self::COLONY_ID,
            'type' => 'contract',
            'credits_amount' => 15,
            'duration_ticks' => 3,
            'expires_tick' => 1,
            'is_accepted' => true,
            'resolved' => $resolved,
            'ends_tick' => $endsTick,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_active_contract_pays_credits_within_its_window(): void
    {
        $before = $this->getCredits();
        $this->insertContract(endsTick: 11500);

        Artisan::call('game:tick', ['--tick' => 11498]);

        $this->assertSame($before + 15, $this->getCredits());
    }

    public function test_contract_is_resolved_once_ends_tick_has_been_paid(): void
    {
        $id = $this->insertContract(endsTick: 11498);

        Artisan::call('game:tick', ['--tick' => 11498]);

        $this->assertTrue((bool) DB::table('bar_encounters')->where('id', $id)->value('resolved'));
    }

    public function test_resolved_contract_no_longer_pays(): void
    {
        $this->insertContract(endsTick: 11498, resolved: true);
        $before = $this->getCredits();

        Artisan::call('game:tick', ['--tick' => 11499]);

        $this->assertSame($before, $this->getCredits());
    }
}
