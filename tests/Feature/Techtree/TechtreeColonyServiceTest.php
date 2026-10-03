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

    public function test_get_techtree(): void
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

    /**
     * Characterization (R5b/3): personell nodes carry no colony state in the techtree.
     * Advisors are hired via AdvisorService; the techtree only shows the master rows
     * with level/status_points/ap_spend fixed at 0. Colony 999 has no legacy
     * colony_personell rows, so this holds both before and after dropping that table.
     */
    public function test_personell_nodes_have_level_zero(): void
    {
        $personell = $this->service->getTechtree(999)['personell'];

        $this->assertNotEmpty($personell);
        foreach ($personell as $id => $node) {
            $this->assertSame(0, $node['level'], "personell $id level");
            $this->assertSame(0, $node['status_points'], "personell $id status_points");
            $this->assertSame(0, $node['ap_spend'], "personell $id ap_spend");
            $this->assertArrayHasKey('name', $node);
        }
    }
}
