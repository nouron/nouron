<?php

namespace Tests\Unit\Playtest;

use Tests\Feature\Playtest\BotProfile;
use Tests\TestCase;

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

    public function test_named_rejects_unknown_profile(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        BotProfile::named('nonexistent');
    }
}
