<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotProfile;

/** Spec 2.9: base weights plus what the profile dials and the opening derive. */
class BotProfileWeightsTest extends TestCase
{
    public function test_default_profile_uses_the_base_weights(): void
    {
        $w = BotProfile::named('default')->weights();

        $this->assertSame(0.95, $w['gamma']);
        $this->assertSame(40, $w['horizon_max']);
        $this->assertSame(0.04, $w['price']['credits']);
        $this->assertSame(0.0, $w['w_objective']);
        $this->assertEqualsWithDelta(1.1, $w['credit_reserve_factor'], 1e-9);
        $this->assertSame(0.0, $w['opening_bias'], 'auto opening has no bias');
    }

    public function test_a_named_opening_keeps_the_bias(): void
    {
        $this->assertSame(30.0, BotProfile::named('default')->withOpening('hangar')->weights()['opening_bias']);
    }

    public function test_thrifty_doubles_the_credit_price_and_raises_the_reserve(): void
    {
        $w = BotProfile::named('thrifty')->weights();

        $this->assertSame(0.08, $w['price']['credits']);
        $this->assertEqualsWithDelta(1.5, $w['credit_reserve_factor'], 1e-9);
    }

    public function test_focus_and_eager_pull_towards_objectives(): void
    {
        $this->assertSame(150.0, BotProfile::named('focus')->weights()['w_objective']);
        $eager = BotProfile::named('eager')->weights();
        $this->assertSame(150.0, $eager['w_objective']);
        $this->assertSame(0.04, $eager['price']['credits']);
    }
}
