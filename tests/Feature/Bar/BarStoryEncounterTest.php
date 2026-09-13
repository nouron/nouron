<?php

namespace Tests\Feature\Bar;

/**
 * BarService::pickStoryEncounter() tests (A36 — story_hook Cantina figures).
 *
 * Pure, deterministic, no DB state: the same colony/tick always rolls the
 * same outcome. No resource/Credits effect — flavor-only (Leitplanke §12).
 *
 * Covered scenarios:
 *  - test_returns_null_when_roll_misses
 *  - test_returns_a_configured_story_slug_when_roll_hits
 *  - test_is_deterministic_for_the_same_colony_and_tick
 *  - test_changes_across_ticks
 */

use App\Services\BarService;
use Tests\TestCase;

class BarStoryEncounterTest extends TestCase
{
    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->barService = $this->app->make(BarService::class);
    }

    public function test_returns_null_when_roll_misses(): void
    {
        config(['game.bar.story_encounter.chance' => 0.0]);

        $result = $this->barService->pickStoryEncounter(1, 10);

        $this->assertNull($result);
    }

    public function test_returns_a_configured_story_slug_when_roll_hits(): void
    {
        config(['game.bar.story_encounter.chance' => 1.0]);

        $result = $this->barService->pickStoryEncounter(1, 10);

        $this->assertNotNull($result);
        $this->assertContains($result, config('game.bar.story_encounter.slugs'));
    }

    public function test_is_deterministic_for_the_same_colony_and_tick(): void
    {
        config(['game.bar.story_encounter.chance' => 1.0]);

        $first = $this->barService->pickStoryEncounter(5, 42);
        $second = $this->barService->pickStoryEncounter(5, 42);

        $this->assertSame($first, $second);
    }

    public function test_changes_across_ticks(): void
    {
        config(['game.bar.story_encounter.chance' => 1.0]);

        $results = [];
        for ($tick = 1; $tick <= 20; $tick++) {
            $results[] = $this->barService->pickStoryEncounter(5, $tick);
        }

        $this->assertGreaterThan(1, count(array_unique($results)));
    }
}
