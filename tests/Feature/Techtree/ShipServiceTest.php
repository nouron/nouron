<?php

namespace Tests\Feature\Techtree;

use App\Services\Techtree\ShipService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ShipService had 0% test coverage. checkRequiredResearchesByEntityId() is the
 * one method it overrides beyond the shared AbstractTechnologyService contract —
 * ships may additionally require a research at a minimum level before leveling up.
 */
class ShipServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const SHIP_ID = 37; // ship_corvette — no research requirement in seed data

    private const RESEARCH_ID = 90; // construction

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function service(): ShipService
    {
        return app(ShipService::class);
    }

    public function test_ship_without_required_research_always_passes(): void
    {
        $this->assertTrue(
            $this->service()->checkRequiredResearchesByEntityId(self::COLONY_ID, self::SHIP_ID)
        );
    }

    public function test_ship_with_required_research_but_colony_has_none_fails(): void
    {
        DB::table('ships')->where('id', self::SHIP_ID)
            ->update(['required_research_id' => self::RESEARCH_ID, 'required_research_level' => 2]);

        $this->assertFalse(
            $this->service()->checkRequiredResearchesByEntityId(self::COLONY_ID, self::SHIP_ID)
        );
    }

    public function test_ship_with_research_below_required_level_fails(): void
    {
        DB::table('ships')->where('id', self::SHIP_ID)
            ->update(['required_research_id' => self::RESEARCH_ID, 'required_research_level' => 2]);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::RESEARCH_ID],
            ['level' => 1, 'ap_spend' => 0, 'status_points' => 20]
        );

        $this->assertFalse(
            $this->service()->checkRequiredResearchesByEntityId(self::COLONY_ID, self::SHIP_ID)
        );
    }

    public function test_ship_with_research_at_required_level_passes(): void
    {
        DB::table('ships')->where('id', self::SHIP_ID)
            ->update(['required_research_id' => self::RESEARCH_ID, 'required_research_level' => 2]);
        DB::table('colony_researches')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'research_id' => self::RESEARCH_ID],
            ['level' => 2, 'ap_spend' => 0, 'status_points' => 20]
        );

        $this->assertTrue(
            $this->service()->checkRequiredResearchesByEntityId(self::COLONY_ID, self::SHIP_ID)
        );
    }

    public function test_invest_advances_ap_spend(): void
    {
        config(['game.bypass.ap_checks' => true]);

        DB::table('colony_ships')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'ship_id' => self::SHIP_ID],
            ['level' => 0, 'ap_spend' => 0, 'status_points' => 0]
        );

        $this->assertTrue($this->service()->invest(self::COLONY_ID, self::SHIP_ID, 'add', 1));

        $this->assertSame(1, DB::table('colony_ships')
            ->where('colony_id', self::COLONY_ID)->where('ship_id', self::SHIP_ID)->value('ap_spend'));
    }
}
