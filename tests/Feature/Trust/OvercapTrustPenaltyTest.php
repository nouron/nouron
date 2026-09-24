<?php

namespace Tests\Feature\Trust;

use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A14 (GDD §6 "Überkapazität — Konsequenzen"): trust summand derived from
 * glx_colonies.overcap_streak. No grace period — the penalty applies from the
 * first Sol with homeless colonists.
 *
 *   streak 0                       → 0
 *   streak 1                       → −trust_base_malus
 *   each further Sol               → −trust_step more
 *   capped at                      → −trust_cap
 *
 * Independent of the hunger penalty — both apply in full, no shared cap
 * (Owner decision 2026-09-23).
 *
 * Fixture: Colony 1 (Springfield). Config under test: base 2, step 1, cap 4.
 */
class OvercapTrustPenaltyTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TICK = 12700;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        config([
            'game.overcap.trust_base_malus' => 2,
            'game.overcap.trust_step' => 1,
            'game.overcap.trust_cap' => 4,
            'game.food.hunger_base_malus' => 2,
            'game.food.hunger_step' => 1,
            'game.food.hunger_cap' => 8,
        ]);
    }

    private function service(): TrustService
    {
        return $this->app->make(TrustService::class);
    }

    private function setStreaks(int $overcap, int $hunger = 0): void
    {
        DB::table('glx_colonies')->where('id', self::COLONY_ID)
            ->update(['overcap_streak' => $overcap, 'hunger_streak' => $hunger]);
    }

    private function trustWith(int $overcap, int $hunger = 0): int
    {
        $this->setStreaks($overcap, $hunger);

        return $this->service()->calculateTrust(self::COLONY_ID, self::TICK);
    }

    public function test_penalty_formula_over_the_streak(): void
    {
        $expected = [0 => 0, 1 => -2, 2 => -3, 3 => -4, 4 => -4, 30 => -4];

        foreach ($expected as $streak => $penalty) {
            $this->setStreaks($streak);
            $this->assertSame($penalty, $this->service()->overcapPenalty(self::COLONY_ID), "streak {$streak}");
        }
    }

    public function test_trust_drops_from_the_first_sol_and_escalates_to_cap(): void
    {
        $baseline = $this->trustWith(0);

        $this->assertSame($baseline - 2, $this->trustWith(1), 'first Sol with homeless colonists → base malus');
        $this->assertSame($baseline - 3, $this->trustWith(2), '+step per further Sol');
        $this->assertSame($baseline - 4, $this->trustWith(12), 'capped at trust_cap');
    }

    public function test_penalty_vanishes_immediately_when_streak_resets(): void
    {
        $baseline = $this->trustWith(0);
        $this->trustWith(10);

        $this->assertSame($baseline, $this->trustWith(0), 'no carry-over once the streak is 0');
    }

    public function test_overcap_and_hunger_penalties_stack_without_shared_cap(): void
    {
        $baseline = $this->trustWith(0, 0);

        // hunger streak 10 → −8 (hunger cap), overcap streak 20 → −4 (overcap cap)
        $this->assertSame($baseline - 8, $this->trustWith(0, 10));
        $this->assertSame($baseline - 4, $this->trustWith(20, 0));
        $this->assertSame($baseline - 12, $this->trustWith(20, 10), 'both summands apply in full');
    }
}
