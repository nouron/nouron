<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use App\Services\AdvisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** T30 §13.x/7: bot mission candidates use the return guarantee, not a mirrored 20 × 0.25. */
class BotStrategyFlightReadinessTest extends TestCase
{
    use RefreshDatabase;

    private function bootWithDrone(float $sp): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('colony_buildings')->insert(['colony_id' => $bot->colonyId, 'building_id' => BuildingId::Hangar->value,
            'instance_id' => 1, 'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1]);
        DB::table('colony_ships')->insert(['colony_id' => $bot->colonyId, 'ship_id' => 85, 'bay' => 1, 'ship_state' => 'docked',
            'level' => 0, 'status_points' => $sp, 'ap_spend' => 0]);
        $service = app(AdvisorService::class);
        DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $bot->colonyId)->delete();
        $service->lockActionPoints($bot->colonyId, $service->getTotalActionPoints($bot->colonyId) - 10);

        return $bot;
    }

    private function recon(BotSession $bot): mixed
    {
        return collect(BotStrategy::default())->firstWhere('name', 'dispatch_mission')['when']($bot);
    }

    public function test_recon_flies_with_4_sp_because_the_drone_comes_back(): void
    {
        $this->assertNotEmpty($this->recon($this->bootWithDrone(4.0)), 'old 25 % floor (5) blocked this');
    }

    public function test_recon_waits_with_3_sp(): void
    {
        $this->assertEmpty($this->recon($this->bootWithDrone(3.0)));
    }

    public function test_bot_no_longer_mirrors_the_old_constants(): void
    {
        $source = file_get_contents(base_path('tests/Feature/Playtest/BotStrategy.php'));

        $this->assertStringNotContainsString('dispatch_min_sp_pct', $source);
        $this->assertStringNotContainsString('$shipMaxStatus = 20', $source);
    }
}
