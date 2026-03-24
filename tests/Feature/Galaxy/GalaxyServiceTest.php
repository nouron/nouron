<?php

namespace Tests\Feature\Galaxy;

use App\Models\GlxSystem;
use App\Models\GlxSystemObject;
use App\Services\GalaxyService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Feature tests for GalaxyService.
 *
 * Uses the canonical Simpsons test fixtures (via TestSeeder):
 *   - glx_systems:        18 systems; ID 1 "test" at (6800, 3000), type_id=1
 *   - glx_system_objects: objects at various coords, IDs 1–18
 *     - ID 1 "test"  at (6828, 3016)  → inside system 1 bounding box
 *     - ID 2 "test2" at (6801, 2998)  → inside system 1 bounding box
 *   - glx_colonies:
 *     - ID 1 "Springfield" → system_object_id=1, user_id=3 (Bart)
 *     - ID 2 "Shelbyville" → system_object_id=1, user_id=0 (Homer)
 */
class GalaxyServiceTest extends TestCase
{
    use RefreshDatabase;

    private GalaxyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(GalaxyService::class);
    }

    // ── getSystems ────────────────────────────────────────────────────────────

    public function test_get_systems_returns_collection(): void
    {
        $systems = $this->service->getSystems();
        $this->assertInstanceOf(Collection::class, $systems);
        $this->assertGreaterThanOrEqual(2, $systems->count());
    }

    public function test_get_systems_items_are_glx_system_models(): void
    {
        $systems = $this->service->getSystems();
        $this->assertInstanceOf(GlxSystem::class, $systems->first());
    }

    // ── getSystem ─────────────────────────────────────────────────────────────

    public function test_get_system_returns_system_by_id(): void
    {
        $system = $this->service->getSystem(1);
        $this->assertInstanceOf(GlxSystem::class, $system);
        $this->assertEquals('test', $system->name);
        $this->assertEquals(6800, $system->x);
        $this->assertEquals(3000, $system->y);
    }

    public function test_get_system_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getSystem(9999));
    }

    public function test_get_system_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSystem(-1);
    }

    public function test_get_system_includes_type_attributes_from_view(): void
    {
        $system = $this->service->getSystem(1);
        $this->assertNotNull($system->class);    // from glx_system_types via view
        $this->assertNotNull($system->size);
    }

    // ── getSystemObject ───────────────────────────────────────────────────────

    public function test_get_system_object_returns_object_by_id(): void
    {
        $object = $this->service->getSystemObject(1);
        $this->assertInstanceOf(GlxSystemObject::class, $object);
        $this->assertEquals('test', $object->name);
        $this->assertEquals(6828, $object->x);
        $this->assertEquals(3016, $object->y);
    }

    public function test_get_system_object_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getSystemObject(9999));
    }

    public function test_get_system_object_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSystemObject(-1);
    }

    public function test_get_system_object_includes_type_from_view(): void
    {
        $object = $this->service->getSystemObject(1);
        $this->assertNotNull($object->type);        // from glx_system_object_types via view
        $this->assertNotNull($object->image_url);
    }

    // ── getSystemObjects ──────────────────────────────────────────────────────

    public function test_get_system_objects_returns_objects_in_range(): void
    {
        // System 1 is at (6800, 3000); objects 1 and 2 are at (6828, 3016) and (6801, 2998)
        $objects = $this->service->getSystemObjects(1);
        $this->assertInstanceOf(Collection::class, $objects);
        $this->assertGreaterThanOrEqual(2, $objects->count());
    }

    public function test_get_system_objects_returns_empty_for_missing_system(): void
    {
        $objects = $this->service->getSystemObjects(9999);
        $this->assertInstanceOf(Collection::class, $objects);
        $this->assertEquals(0, $objects->count());
    }

    public function test_get_system_objects_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSystemObjects(-1);
    }

    // ── getSystemObjectByColonyId ─────────────────────────────────────────────

    public function test_get_system_object_by_colony_id(): void
    {
        // Colony 1 (Springfield) is on system_object_id=1
        $object = $this->service->getSystemObjectByColonyId(1);
        $this->assertInstanceOf(GlxSystemObject::class, $object);
        $this->assertEquals(1, $object->id);
    }

    public function test_get_system_object_by_colony_id_returns_false_for_missing_colony(): void
    {
        $this->assertFalse($this->service->getSystemObjectByColonyId(9999));
    }

    public function test_get_system_object_by_colony_id_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSystemObjectByColonyId(-1);
    }

    // ── getSystemObjectByCoords ───────────────────────────────────────────────

    public function test_get_system_object_by_coords_returns_object(): void
    {
        $object = $this->service->getSystemObjectByCoords([6828, 3016]);
        $this->assertInstanceOf(GlxSystemObject::class, $object);
        $this->assertEquals(1, $object->id);
    }

    public function test_get_system_object_by_coords_returns_null_for_unknown(): void
    {
        $this->assertNull($this->service->getSystemObjectByCoords([0, 0]));
    }

    public function test_get_system_object_by_coords_throws_for_invalid_coords(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getSystemObjectByCoords(['a', 'b']);
    }

    // ── getObjectsByCoords ────────────────────────────────────────────────────

    public function test_get_objects_by_coords_returns_nearby_objects(): void
    {
        // Objects near system 1's position (6800, 3000)
        $objects = $this->service->getObjectsByCoords([6800, 3000]);
        $this->assertInstanceOf(Collection::class, $objects);
        $this->assertGreaterThanOrEqual(1, $objects->count());
        $this->assertInstanceOf(GlxSystemObject::class, $objects->first());
    }

    public function test_get_objects_by_coords_returns_empty_for_distant_point(): void
    {
        $objects = $this->service->getObjectsByCoords([0, 0]);
        $this->assertInstanceOf(Collection::class, $objects);
        $this->assertEquals(0, $objects->count());
    }

    // ── getColoniesByCoords ───────────────────────────────────────────────────

    public function test_get_colonies_by_coords_returns_colonies_in_range(): void
    {
        // System object 1 is at (6828, 3016); Springfield and Shelbyville are on it
        $colonies = $this->service->getColoniesByCoords([6828, 3016]);
        $this->assertInstanceOf(Collection::class, $colonies);
        $this->assertEquals(2, $colonies->count());
    }

    public function test_get_colonies_by_coords_returns_empty_for_distant_point(): void
    {
        $colonies = $this->service->getColoniesByCoords([0, 0]);
        $this->assertEquals(0, $colonies->count());
    }

    // ── getSystemBySystemObject ───────────────────────────────────────────────

    public function test_get_system_by_system_object_using_object(): void
    {
        $object = $this->service->getSystemObject(1); // at (6828, 3016)
        $system = $this->service->getSystemBySystemObject($object);
        $this->assertInstanceOf(GlxSystem::class, $system);
        $this->assertEquals(1, $system->id);
    }

    public function test_get_system_by_system_object_using_id(): void
    {
        $system = $this->service->getSystemBySystemObject(1);
        $this->assertInstanceOf(GlxSystem::class, $system);
        $this->assertEquals(1, $system->id);
    }

    public function test_get_system_by_system_object_throws_for_invalid_argument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // Passing a non-GlxSystemObject, non-integer value
        $this->service->getSystemBySystemObject(false);
    }

    // ── getSystemByObjectCoords ───────────────────────────────────────────────

    public function test_get_system_by_object_coords_returns_system(): void
    {
        $system = $this->service->getSystemByObjectCoords([6828, 3016]);
        $this->assertInstanceOf(GlxSystem::class, $system);
        $this->assertEquals(1, $system->id);
    }

    public function test_get_system_by_object_coords_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->service->getSystemByObjectCoords([0, 0]));
    }

    // ── getDistance / getDistanceTicks ────────────────────────────────────────

    public function test_get_distance_manhattan(): void
    {
        $dist = $this->service->getDistance([0, 0], [3, 4]);
        $this->assertEquals(7, $dist); // |3-0| + |4-0| = 7
    }

    public function test_get_distance_symmetric(): void
    {
        $a = [100, 200];
        $b = [400, 600];
        $this->assertEquals(
            $this->service->getDistance($a, $b),
            $this->service->getDistance($b, $a)
        );
    }

    public function test_get_distance_ticks_is_distance_plus_one(): void
    {
        $this->assertEquals(8, $this->service->getDistanceTicks([0, 0], [3, 4]));
    }

    // ── getPath ───────────────────────────────────────────────────────────────

    public function test_get_path_starts_at_source(): void
    {
        $path = $this->service->getPath([0, 0], [3, 0], 1, 0);
        $this->assertArrayHasKey(0, $path);
        $this->assertEquals([0, 0, 0], $path[0]);
    }

    public function test_get_path_ends_at_destination(): void
    {
        $path = $this->service->getPath([0, 0], [3, 0], 1, 0);
        $last = end($path);
        $this->assertEquals(3, $last[0]);
        $this->assertEquals(0, $last[1]);
    }

    public function test_get_path_speed_two_halves_steps(): void
    {
        // Straight line x=0..4, speed 2 → fewer ticks than speed=1
        $path = $this->service->getPath([0, 0], [4, 0], 2, 10);
        // First key must be 10 (the start tick)
        $this->assertArrayHasKey(10, $path);
        // A speed-2 path has fewer entries than a speed-1 path over the same distance
        $pathSpeed1 = $this->service->getPath([0, 0], [4, 0], 1, 10);
        $this->assertLessThan(count($pathSpeed1), count($path));
    }

    // ── GlxSystem model helpers ───────────────────────────────────────────────

    public function test_glx_system_get_coords(): void
    {
        $system = $this->service->getSystem(1);
        $this->assertEquals([6800, 3000, 0], $system->getCoords());
    }

    // ── GlxSystemObject model helpers ─────────────────────────────────────────

    public function test_glx_system_object_get_coords(): void
    {
        $object = $this->service->getSystemObject(1);
        $this->assertEquals([6828, 3016, 0], $object->getCoords());
    }
}
