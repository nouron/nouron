<?php

namespace Tests\Unit\Playtest;

use PHPUnit\Framework\TestCase;
use Tests\Feature\Playtest\BotEngine;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotStrategy;

class BotEngineTest extends TestCase
{
    public function test_legacy_engine_returns_the_legacy_rule_list(): void
    {
        $profile = BotProfile::named('default');

        $this->assertSame(array_column(BotStrategy::default($profile), 'name'), array_column(BotEngine::rules($profile), 'name'));
    }

    public function test_utility_engine_returns_the_single_collector_rule(): void
    {
        $this->assertSame(['utility'], array_column(BotEngine::rules(BotProfile::named('default')->withEngine('utility')), 'name'));
    }
}
