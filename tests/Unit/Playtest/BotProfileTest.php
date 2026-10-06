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

    public function test_named_default_matches_default_constructor(): void
    {
        $profile = BotProfile::named('default');

        $this->assertSame('default', $profile->name);
        $this->assertSame(0.0, $profile->savingsAggressiveness);
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
}
