<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\TechtreeColonyService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechtreeColonyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TechtreeColonyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(TechtreeColonyService::class);
    }

    public function testGetTechtree(): void
    {
        $result = $this->service->getTechtree(1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('building', $result);
        $this->assertArrayHasKey('research', $result);
        $this->assertArrayHasKey('ship', $result);
        $this->assertArrayHasKey('personell', $result);
        $this->assertIsArray($result['building']);
        $this->assertNotEmpty($result['building']);
    }

    public function testGetBuildings(): void
    {
        $result = $this->service->getBuildings(1);
        $this->assertTrue($result->isNotEmpty());
    }
}
