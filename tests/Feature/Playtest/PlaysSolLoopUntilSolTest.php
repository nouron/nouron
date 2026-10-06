<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T30 step 0 (Task 5): --until-sol stops the loop at the start of Sol N
 * (Sols 0..N-1 fully played; a booted run starts at Sol 0) while the run is still active.
 */
class PlaysSolLoopUntilSolTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_stop_at_sol_ends_the_loop_after_exactly_sols_0_to_n_minus_1(): void
    {
        $bot = BotSession::boot($this, 1);
        $played = [];

        $this->playSolsUntil($bot, [], self::stopAtSol(5), afterAction: function (BotSession $b) use (&$played) {
            $played[] = $b->sol;
        });

        $this->assertSame([0, 1, 2, 3, 4], $played);
        $this->assertSame(5, $bot->sol);
        $this->assertTrue($bot->isActive());
    }

    public function test_without_a_stop_condition_the_loop_is_unchanged(): void
    {
        $bot = BotSession::boot($this, 1);
        $played = 0;

        $this->playSolsUntil($bot, [], fn (BotSession $b) => $b->sol >= 3, afterAction: function () use (&$played) {
            $played++;
        });

        $this->assertSame(3, $played);
    }
}
