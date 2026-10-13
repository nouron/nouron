<?php

namespace Tests\Feature\Playtest\Utility;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Playtest\BotEngine;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\PlaysSolLoop;
use Tests\TestCase;

/** Spec 4 invariants for the utility engine — no balance assertions (the bot is an instrument). */
class UtilityEngineSmokeTest extends TestCase
{
    use PlaysSolLoop;
    use RefreshDatabase;

    public function test_utility_engine_plays_twelve_sols_without_rejection_loops(): void
    {
        $profile = BotProfile::named('default')->withOpening('hangar')->withEngine('utility');
        $bot = BotSession::boot($this, 4242);

        $this->playSolsUntil($bot, BotEngine::rules($profile), self::stopAtSol(12));

        $this->assertTrue($bot->isActive(), 'run must still be active at Sol 12: '.$bot->failReason());
        $migrated = ['invest_cc', 'invest_production', 'research_knowledge'];
        $rejected = array_values(array_filter($bot->log, fn (array $e): bool => in_array($e['rule'], $migrated, true) && ! $e['ok']));
        $this->assertLessThanOrEqual(2, count($rejected), 'utility sources must only offer what the server accepts: '.json_encode($rejected));
        $this->assertNotContains('agrardom_required', array_column($bot->log, 'error'));
        $this->assertNotEmpty(array_filter($bot->decisions, fn (array $d): bool => $d['source'] === 'utility' && $d['ok']), 'migrated sources must decide');
    }
}
