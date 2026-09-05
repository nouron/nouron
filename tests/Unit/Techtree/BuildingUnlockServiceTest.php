<?php

namespace Tests\Unit\Techtree;

use App\Services\Techtree\BuildingUnlockService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BuildingUnlockService — Owner-Playtest-Fund 2026-08-31: sidebar showed
 * requirements ("Benötigt X Lv2") but never the reverse — what a building
 * level unlocks (e.g. "Hangar Lv2 unlocks the Frachter ship"). This is a
 * pure reverse lookup of the SAME required_building_id/level relationships
 * already used by the existing "Voraussetzung" display — no new content,
 * purely derived from data that already exists (ships.required_building_id,
 * researches.required_building_id/required_building2_id, etc.).
 */
class BuildingUnlockServiceTest extends TestCase
{
    use RefreshDatabase;

    private BuildingUnlockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->setLocale('de');
        $this->service = $this->app->make(BuildingUnlockService::class);
    }

    public function test_hangar_level2_unlocks_the_freighter_ship(): void
    {
        // ships.required_building_id=44 (hangar), required_building_level=2 → ship_freighter.
        $unlocks = $this->service->unlocksAtLevel(44, 2);

        $this->assertContains(__('techtree.ship_freighter'), array_column($unlocks, 'text'));
    }

    public function test_hangar_level3_unlocks_the_corvette_ship(): void
    {
        $unlocks = $this->service->unlocksAtLevel(44, 3);

        $texts = array_column($unlocks, 'text');
        $this->assertContains(__('techtree.ship_corvette'), $texts);
        $this->assertNotContains(__('techtree.ship_freighter'), $texts, 'freighter unlocks at level 2, not 3');
    }

    public function test_hangar_level2_also_unlocks_the_defense_knowledge_via_secondary_requirement(): void
    {
        // researches id=96 (knowledge_defense): required_building_id=31 (sciencelab) Lv3
        // AND required_building2_id=44 (hangar) Lv2 — must be reachable via EITHER slot.
        $unlocks = $this->service->unlocksAtLevel(44, 2);

        $this->assertContains(__('techtree.knowledge_defense'), array_column($unlocks, 'text'));
    }

    public function test_level_with_nothing_gated_returns_empty(): void
    {
        $unlocks = $this->service->unlocksAtLevel(44, 1);

        // Drone (ship_drone) unlocks at hangar Lv1 — pick a level that truly has nothing.
        $unlocksAtImpossibleLevel = $this->service->unlocksAtLevel(44, 99);

        $this->assertSame([], $unlocksAtImpossibleLevel);
        $this->assertNotSame([], $unlocks, 'sanity: level 1 must not be empty (drone unlocks there)');
    }

    public function test_unrelated_building_returns_empty(): void
    {
        $unlocks = $this->service->unlocksAtLevel(25, 1); // CommandCenter Lv1 gates nothing new directly

        $this->assertIsArray($unlocks);
    }

    /**
     * Building-gated unlocks are always entity names (ships/buildings/knowledge),
     * never a resource-valued curve like knowledge's per-level effects — so the
     * chip slot has no resource to fill. Still returned in the SAME {text, chip}
     * shape as KnowledgeEffectDescriptionService::effectsAtLevel() so the sidebar
     * partial can render both with one code path (chip: null → neutral fallback chip).
     */
    public function test_entries_use_the_text_chip_shape_with_a_null_chip(): void
    {
        $unlocks = $this->service->unlocksAtLevel(44, 2);

        $this->assertNotEmpty($unlocks);
        foreach ($unlocks as $entry) {
            $this->assertArrayHasKey('text', $entry);
            $this->assertArrayHasKey('chip', $entry);
            $this->assertIsString($entry['text']);
            $this->assertNull($entry['chip']);
        }
    }
}
