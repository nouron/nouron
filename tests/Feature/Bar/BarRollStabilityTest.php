<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\BarService;
use App\Services\OnboardingService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * R5b fix round 1: wager and concern rolls must not depend on the row's
 * position among the colony's rows — expired encounters/concerns are deleted
 * during play, which shifted that position (and with it the roll).
 */
class BarRollStabilityTest extends TestCase
{
    use RefreshDatabase;

    private const TICK = 10;

    private int $colonyId;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        app(ReferenceDataSeeder::class)->run();
        $this->userId = (int) User::create([
            'username' => 'roller', 'display_name' => 'roller', 'role' => 'player',
            'password' => 'secret', 'email' => 'roller@example.invalid', 'activated' => 1, 'activation_key' => '',
        ])->user_id;
        $this->colonyId = (int) app(OnboardingService::class)->setupNewPlayer($this->userId)->id;
        app(OnboardingService::class)->resetColonyToSol1($this->userId, $this->colonyId, rngSeed: 4242);
        DB::table('colony_resources')->where('colony_id', $this->colonyId)->update(['amount' => 10000]);
        DB::table('user_resources')->where('user_id', $this->userId)->update(['credits' => 100000]);
        config(['game.bypass.ap_checks' => true]);
    }

    /** Smallest permille p in 1..1000 at which $succeeds(p) holds, found by bisection (roll = p - 1). */
    private function threshold(callable $succeeds): int
    {
        [$lo, $hi] = [1, 1000];
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            DB::beginTransaction();
            $ok = $succeeds($mid);
            DB::rollBack();
            $ok ? $hi = $mid : $lo = $mid + 1;
        }

        return $lo;
    }

    public function test_wager_roll_is_unaffected_by_deleting_an_older_expired_encounter(): void
    {
        $row = ['colony_id' => $this->colonyId, 'type' => 'wager', 'give_resource_id' => 1, 'give_amount' => 10, 'win_chance' => 0.5, 'credits_amount' => 20];
        DB::table('bar_encounters')->insert([...$row, 'expires_tick' => 3]); // older, expired
        $wagerId = DB::table('bar_encounters')->insertGetId([...$row, 'expires_tick' => self::TICK + 2]);
        $roll = fn () => $this->threshold(function (int $permille) use ($wagerId) {
            DB::table('bar_encounters')->where('id', $wagerId)->update(['win_chance' => $permille / 1000]);

            return app(BarService::class)->acceptEncounter($this->colonyId, $wagerId, $this->userId, self::TICK)['won'];
        });

        $before = $roll();
        DB::table('bar_encounters')->where('expires_tick', 3)->delete(); // what expiry does during play

        $this->assertSame($before, $roll());
    }

    public function test_concern_roll_is_unaffected_by_deleting_an_older_expired_concern(): void
    {
        $row = ['colony_id' => $this->colonyId, 'character_slug' => 'preacher'];
        DB::table('bar_concerns')->insert([...$row, 'created_tick' => 1, 'expires_tick' => 3]); // older, expired
        $concernId = DB::table('bar_concerns')->insertGetId([...$row, 'created_tick' => self::TICK, 'expires_tick' => self::TICK + 2]);
        $roll = fn () => $this->threshold(function (int $permille) use ($concernId) {
            config(['game.bar.concern.success_chance.preacher' => $permille / 1000]);

            return app(BarService::class)->resolveConcern($this->colonyId, $concernId, $this->userId, self::TICK)['success'];
        });

        $before = $roll();
        DB::table('bar_concerns')->where('created_tick', 1)->delete();

        $this->assertSame($before, $roll());
    }
}
