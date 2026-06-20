<?php

namespace Tests\Feature\Colony;

use App\Models\Colony;
use App\Services\ColonyService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

/**
 * Laravel port of ColonyTest\Service\ColonyServiceTest.
 *
 * Uses the canonical Simpsons test data (via TestSeeder):
 *   - Colony 1 "Springfield"  — user_id=3 (Bart), is_primary=1
 *   - Colony 2 "Shelbyville"  — user_id=0 (Homer), is_primary=1
 */
class ColonyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ColonyService $service;

    private int $colonyId = 1;

    private int $userId = 3;   // Bart

    private int $homerUid = 0;   // Homer (user_id=0, legacy)

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Simpsons test data inside the already-open DB transaction so it
        // is rolled back automatically after each test (RefreshDatabase only
        // calls migrate:fresh once globally, so $seeder property is not reliable
        // when other test classes run first without a seeder).
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(ColonyService::class);
    }

    public function test_get_colonies_returns_collection(): void
    {
        $colonies = $this->service->getColonies();
        $this->assertInstanceOf(Collection::class, $colonies);
        $this->assertGreaterThanOrEqual(2, $colonies->count());
        $this->assertInstanceOf(Colony::class, $colonies->first());
    }

    public function test_get_colony_returns_colony(): void
    {
        $colony = $this->service->getColony($this->colonyId);
        $this->assertInstanceOf(Colony::class, $colony);
        $this->assertEquals('Springfield', $colony->name);
    }

    public function test_get_colony_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getColony(99));
    }

    public function test_get_colony_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getColony(-1);
    }

    public function test_get_colonies_by_user_id(): void
    {
        $colonies = $this->service->getColoniesByUserId($this->userId);
        $this->assertInstanceOf(Collection::class, $colonies);
        $this->assertGreaterThanOrEqual(1, $colonies->count());
        $this->assertInstanceOf(Colony::class, $colonies->first());
    }

    public function test_get_colonies_by_user_id_returns_empty_for_unknown_user(): void
    {
        $colonies = $this->service->getColoniesByUserId(99);
        $this->assertInstanceOf(Collection::class, $colonies);
        $this->assertEquals(0, $colonies->count());
    }

    public function test_get_colonies_by_user_id_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getColoniesByUserId(-1);
    }

    public function test_check_colony_owner_positive(): void
    {
        $this->assertTrue($this->service->checkColonyOwner($this->colonyId, $this->userId));
    }

    public function test_check_colony_owner_wrong_colony(): void
    {
        $this->assertFalse($this->service->checkColonyOwner(99, $this->userId));
    }

    public function test_check_colony_owner_wrong_user(): void
    {
        $this->assertFalse($this->service->checkColonyOwner($this->colonyId, 99));
    }

    public function test_get_prime_colony_for_bart(): void
    {
        $colony = $this->service->getPrimeColony($this->userId);
        $this->assertInstanceOf(Colony::class, $colony);
        $this->assertTrue($colony->is_primary);
    }

    public function test_get_prime_colony_for_homer(): void
    {
        $colony = $this->service->getPrimeColony($this->homerUid);
        $this->assertInstanceOf(Colony::class, $colony);
    }

    public function test_get_prime_colony_throws_when_none_found(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->getPrimeColony(19);
    }

    public function test_set_active_colony_stores_in_session(): void
    {
        // Seed the session with Bart's userId so the ownership check passes
        $this->withSession(['activeIds' => ['userId' => $this->userId, 'colonyId' => null]]);

        session(['activeIds.userId' => $this->userId, 'activeIds.colonyId' => null]);

        $this->service->setActiveColony(1);
        $this->assertEquals(1, session('activeIds.colonyId'));
    }

    public function test_set_active_colony_ignores_non_owned_colony(): void
    {
        // User 19 does not own colony 1
        session(['activeIds.userId' => 19, 'activeIds.colonyId' => null]);

        $this->service->setActiveColony(1);
        $this->assertNull(session('activeIds.colonyId'));
    }

    public function test_set_selected_colony(): void
    {
        session(['selectedIds.colonyId' => null]);

        $this->service->setSelectedColony(1);
        $this->assertEquals(1, session('selectedIds.colonyId'));

        $this->service->setSelectedColony(2);
        $this->assertEquals(2, session('selectedIds.colonyId'));
    }
}
