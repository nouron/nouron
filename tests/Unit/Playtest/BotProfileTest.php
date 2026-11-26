<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotProfile;

class BotProfileTest extends TestCase
{
    public function test_default_profile_has_zero_savings_aggressiveness(): void
    {
        $profile = new BotProfile;

        $this->assertSame('default', $profile->name);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
    }

    public function test_named_default_is_the_casual_player(): void
    {
        // Spec 2026-11-26 (Teil A): `default` = "gelegentlich" — looks at the objectives now and then.
        $profile = BotProfile::named('default');

        $this->assertSame('default', $profile->name);
        $this->assertSame(0.3, $profile->objectiveFocus);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
        // utility engine: the dial scales w_objective continuously (no Sol gating needed there).
        $this->assertEqualsWithDelta(0.3 * BotProfile::named('focus')->weights()['w_objective'], $profile->weights()['w_objective'], 1e-9);
    }

    public function test_named_aimless_keeps_the_old_default_behaviour(): void
    {
        $profile = BotProfile::named('aimless');

        $this->assertSame('aimless', $profile->name);
        $this->assertSame(0.0, $profile->objectiveFocus);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
        $this->assertFalse($profile->exploreOnlyWhenNeeded);
    }

    public function test_dial_active_on_sol_spreads_the_share_evenly_without_chance(): void
    {
        $active = fn (float $f): array => array_values(array_filter(range(1, 10), fn (int $sol) => BotProfile::dialActiveOnSol($f, $sol)));

        $this->assertCount(3, $active(0.3));
        $this->assertSame([4, 7, 10], $active(0.3), 'evenly spread, never clustered');
        $this->assertSame(range(1, 10), $active(1.0));
        $this->assertSame([], $active(0.0));
        $this->assertCount(30, array_filter(range(1, 100), fn (int $sol) => BotProfile::dialActiveOnSol(0.3, $sol)));
    }

    public function test_named_thrifty_has_maximum_savings_aggressiveness(): void
    {
        $profile = BotProfile::named('thrifty');

        $this->assertSame('thrifty', $profile->name);
        $this->assertSame(1.0, $profile->savingsAggressiveness);
    }

    public function test_named_eager_targets_objectives_without_the_savings_brake(): void
    {
        $profile = BotProfile::named('eager');

        $this->assertSame('eager', $profile->name);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
        $this->assertSame(1.0, $profile->objectiveFocus);
    }

    public function test_named_rejects_unknown_profile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BotProfile::named('nonexistent');
    }

    public function test_opening_defaults_to_auto(): void
    {
        $this->assertSame('auto', BotProfile::named('default')->opening);
    }

    public function test_with_opening_keeps_all_other_dials(): void
    {
        $focus = BotProfile::named('focus');
        $hangar = $focus->withOpening('hangar');

        $this->assertSame('hangar', $hangar->opening);
        $this->assertSame('focus', $hangar->name);
        $this->assertSame($focus->savingsAggressiveness, $hangar->savingsAggressiveness);
        $this->assertSame($focus->objectiveFocus, $hangar->objectiveFocus);
        $this->assertSame('auto', $focus->opening, 'original must stay unchanged (readonly copy)');
    }

    public function test_unknown_opening_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BotProfile::named('default')->withOpening('forge');
    }

    public function test_engine_defaults_to_legacy(): void
    {
        $this->assertSame('legacy', BotProfile::named('default')->engine);
    }

    public function test_with_engine_keeps_all_other_dials(): void
    {
        $focus = BotProfile::named('focus')->withOpening('cantina');
        $utility = $focus->withEngine('utility');

        $this->assertSame('utility', $utility->engine);
        $this->assertSame(['focus', 1.0, 1.0, 'cantina'], [$utility->name, $utility->savingsAggressiveness, $utility->objectiveFocus, $utility->opening]);
        $this->assertSame('legacy', $focus->engine);
    }

    public function test_with_opening_keeps_the_engine(): void
    {
        $this->assertSame('utility', BotProfile::named('default')->withEngine('utility')->withOpening('hangar')->engine);
    }

    public function test_unknown_engine_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BotProfile::named('default')->withEngine('magic');
    }

    public function test_explore_only_when_needed_is_off_by_default_and_on_for_lean_explore(): void
    {
        $this->assertFalse(BotProfile::named('default')->exploreOnlyWhenNeeded);
        $lean = BotProfile::named('lean-explore');
        $this->assertSame('lean-explore', $lean->name);
        $this->assertTrue($lean->exploreOnlyWhenNeeded);
        $this->assertSame(0.0, $lean->savingsAggressiveness);
    }

    /** Owner 2026-11-26: `lean-explore` is an offshoot of `default` and shares its objective focus. */
    public function test_lean_explore_shares_the_objective_focus_of_default(): void
    {
        $this->assertSame(0.3, BotProfile::named('lean-explore')->objectiveFocus);
        $this->assertSame(BotProfile::named('default')->objectiveFocus, BotProfile::named('lean-explore')->objectiveFocus);
    }

    public function test_with_opening_and_engine_keep_explore_only_when_needed(): void
    {
        $lean = BotProfile::named('lean-explore');

        $this->assertTrue($lean->withOpening('hangar')->exploreOnlyWhenNeeded);
        $this->assertTrue($lean->withEngine('utility')->exploreOnlyWhenNeeded);
    }
}
