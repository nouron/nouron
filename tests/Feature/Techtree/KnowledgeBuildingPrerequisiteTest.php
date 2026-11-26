<?php

namespace Tests\Feature\Techtree;

use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Owner 2026-11-26: every knowledge needs exactly one building, each a different one; the
 * building unlocks its knowledge and the knowledge strengthens that building. The lab is no
 * gate any more (it only adds pace and exact numbers).
 */
class KnowledgeBuildingPrerequisiteTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED = [
        90 => 25, // construction  → command center
        91 => 44, // cartography   → hangar
        92 => 27, // geology       → harvester
        93 => 41, // agronomy      → Agrardom
        94 => 46, // health        → infirmary
        95 => 52, // trade         → Cantina
        96 => 53, // defense       → security hub
    ];

    public function test_each_knowledge_needs_exactly_its_own_building_at_level_one(): void
    {
        $this->app->make(TestSeeder::class)->run();
        $rows = DB::table('researches')->where('purpose', 'knowledge')->orderBy('id')->get()->keyBy('id');

        $this->assertSame(array_keys(self::EXPECTED), $rows->keys()->map(fn ($id) => (int) $id)->all());
        foreach (self::EXPECTED as $id => $buildingId) {
            $this->assertSame($buildingId, (int) $rows[$id]->required_building_id, "knowledge {$id}");
            $this->assertSame(1, (int) $rows[$id]->required_building_level, "knowledge {$id}");
            $this->assertNull($rows[$id]->required_building2_id, "knowledge {$id} has a second building");
        }
        $this->assertSame(count(self::EXPECTED), count(array_unique(self::EXPECTED)), 'each knowledge a different building');
    }
}
