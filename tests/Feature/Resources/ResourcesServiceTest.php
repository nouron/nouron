<?php

namespace Tests\Feature\Resources;

use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Laravel port of ResourcesTest\Service\ResourcesServiceTest.
 *
 * Test data (from TestSeeder):
 *   - Colony 1 "Springfield" — user_id=3 (Bart)
 *   - colony_resources for colony 1: resource_ids 4,5,12 (3 rows)
 *   - user_resources for user 3: credits=49615, supply=1938
 *   - building_costs for building 52: resource_id=1 (Credits)=100, resource_id=2 (Supply)=10
 */
class ResourcesServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResourcesService $service;
    private int $colonyId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(ResourcesService::class);
    }

    public function test_get_resources_returns_all_five_types(): void
    {
        $results = $this->service->getResources();
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertEquals(5, $results->count());
    }

    public function test_get_resource_returns_correct_entity(): void
    {
        $resource = $this->service->getResource(1);
        $this->assertEquals('res_credits', $resource->name);
        $this->assertEquals('Cr', $resource->abbreviation);
    }

    public function test_get_resource_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getResource(99));
    }

    public function test_get_colony_resources_returns_all(): void
    {
        $results = $this->service->getColonyResources();
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThanOrEqual(3, $results->count());
    }

    public function test_get_colony_resources_filtered_by_colony(): void
    {
        $results = $this->service->getColonyResources(['colony_id' => $this->colonyId]);
        $this->assertEquals(3, $results->count());
    }

    public function test_get_user_resources_returns_collection(): void
    {
        $results = $this->service->getUserResources();
        $this->assertInstanceOf(Collection::class, $results);
        $this->assertGreaterThanOrEqual(1, $results->count());
    }

    public function test_get_user_resources_filtered_by_user(): void
    {
        $result = $this->service->getUserResources(['user_id' => 3])->first();
        $this->assertNotNull($result);
        $this->assertEquals(49615, $result->credits);
        $this->assertEquals(1938,  $result->supply);
    }

    public function test_get_possessions_by_colony_id_returns_five_resources(): void
    {
        $result = $this->service->getPossessionsByColonyId($this->colonyId);
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        // Credits and supply present
        $this->assertArrayHasKey(ResourcesService::RES_CREDITS, $result);
        $this->assertArrayHasKey(ResourcesService::RES_SUPPLY, $result);
        $this->assertEquals(49615, $result[ResourcesService::RES_CREDITS]['amount']);
        $this->assertEquals(1938,  $result[ResourcesService::RES_SUPPLY]['amount']);
    }

    public function test_get_possessions_throws_for_invalid_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getPossessionsByColonyId(-1);
    }

    public function test_check_returns_true_when_resources_sufficient(): void
    {
        // building_costs for building 52: 100 credits + 10 supply
        // Bart has 49615 credits and 1938 supply — easily sufficient
        $costs = DB::table('building_costs')->where('building_id', 52)->get();
        $result = $this->service->check($costs, $this->colonyId);
        $this->assertTrue($result);
    }

    public function test_check_returns_false_when_resources_insufficient(): void
    {
        // Fake cost: 1,000,000 credits — Bart only has 49615
        $costs = [
            (object) ['resource_id' => ResourcesService::RES_CREDITS, 'amount' => 1_000_000],
        ];
        $this->assertFalse($this->service->check($costs, $this->colonyId));
    }

    public function test_pay_costs_deducts_credits_and_supply(): void
    {
        $costs = DB::table('building_costs')->where('building_id', 52)->get();
        // building 52: 100 credits + 10 supply

        $before = $this->service->getUserResources(['user_id' => 3])->first();
        $creditsBefore = $before->credits;
        $supplyBefore  = $before->supply;

        $result = $this->service->payCosts($costs, $this->colonyId);
        $this->assertTrue($result);

        $after = $this->service->getUserResources(['user_id' => 3])->first();
        $this->assertEquals($creditsBefore - 100, $after->credits);
        $this->assertEquals($supplyBefore  - 10,  $after->supply);
    }

    public function test_increase_amount_user_resource(): void
    {
        $before = $this->service->getUserResources(['user_id' => 3])->first();
        $supplyBefore = $before->supply;

        $result = $this->service->increaseAmount($this->colonyId, ResourcesService::RES_SUPPLY, 100);
        $this->assertTrue($result);

        $after = $this->service->getUserResources(['user_id' => 3])->first();
        $this->assertEquals($supplyBefore + 100, $after->supply);
    }

    public function test_decrease_amount_user_resource(): void
    {
        $before = $this->service->getUserResources(['user_id' => 3])->first();
        $supplyBefore = $before->supply;

        $result = $this->service->decreaseAmount($this->colonyId, ResourcesService::RES_SUPPLY, 100);
        $this->assertTrue($result);

        $after = $this->service->getUserResources(['user_id' => 3])->first();
        $this->assertEquals($supplyBefore - 100, $after->supply);
    }

    public function test_increase_amount_colony_resource(): void
    {
        $before = $this->service->getColonyResources(['colony_id' => $this->colonyId, 'resource_id' => 4])->first();
        $amountBefore = $before->amount;

        $this->service->increaseAmount($this->colonyId, 4, 500);  // res_compounds (Werkstoffe)

        $after = $this->service->getColonyResources(['colony_id' => $this->colonyId, 'resource_id' => 4])->first();
        $this->assertEquals($amountBefore + 500, $after->amount);
    }
}
