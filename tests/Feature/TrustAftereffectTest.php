<?php

namespace Tests\Feature;

use App\Services\TickService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T40: trust events fade out over several Sols according to a per-event profile
 * (percent of the delta per Sol of age). The default profile [100] is today's
 * one-Sol behaviour. Everything is checked through calculateTrust().
 */
class TrustAftereffectTest extends TestCase
{
    use RefreshDatabase;

    private TrustService $service;

    private int $colonyId = 1;

    private int $tick = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->app->make(TickService::class)->setTickCount($this->tick);
        $this->service = $this->app->make(TrustService::class);

        DB::table('colony_resources')->where('resource_id', TrustService::RESOURCE_ID)->delete();
        DB::table('trust_events')->delete();
        DB::table('colony_buildings')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_researches')->where('colony_id', $this->colonyId)->delete();
        DB::table('colony_ships')->where('colony_id', $this->colonyId)->delete();
    }

    private function useProfile(string $eventType, array $percentages): void
    {
        config([
            'game.trust.event_profiles.test' => $percentages,
            "game.trust.event_profile.{$eventType}" => 'test',
        ]);
    }

    public function test_default_profile_keeps_the_one_sol_behaviour(): void
    {
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick - 1);

        $this->assertSame(0, $this->service->calculateTrust($this->colonyId, $this->tick));
    }

    public function test_event_fades_out_along_the_profile(): void
    {
        $this->useProfile('research_level_up', [100, 50, 25]); // delta +2
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick - 2);

        // age 2 -> 25 % of 2 = 0.5, rounded away from zero = 1
        $this->assertSame(1, $this->service->calculateTrust($this->colonyId, $this->tick));
        $this->assertSame(0, $this->service->calculateTrust($this->colonyId, $this->tick + 1));
    }

    public function test_event_has_full_effect_in_its_own_sol_and_none_before(): void
    {
        $this->useProfile('research_level_up', [100, 50]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick);

        $this->assertSame(2, $this->service->calculateTrust($this->colonyId, $this->tick));
        $this->assertSame(0, $this->service->calculateTrust($this->colonyId, $this->tick - 1));
    }

    public function test_same_type_from_different_sols_adds_up(): void
    {
        $this->useProfile('research_level_up', [100, 50]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick - 1); // 50 % = 1
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick);     // 100 % = 2

        $this->assertSame(3, $this->service->calculateTrust($this->colonyId, $this->tick));
    }

    public function test_same_type_in_the_same_sol_still_does_not_stack(): void
    {
        $this->useProfile('research_level_up', [100, 50]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick);

        $this->assertSame(2, $this->service->calculateTrust($this->colonyId, $this->tick));
    }

    public function test_negative_events_fade_too(): void
    {
        $this->useProfile('encounter_lost', [100, 50]); // delta -4
        $this->service->fireEvent($this->colonyId, 'encounter_lost', $this->tick - 1);

        $this->assertSame(-2, $this->service->calculateTrust($this->colonyId, $this->tick));
    }

    public function test_event_total_is_capped(): void
    {
        config(['game.trust.event_cap' => 3]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick); // +2
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);     // +2

        $this->assertSame(3, $this->service->calculateTrust($this->colonyId, $this->tick));
    }

    public function test_event_lines_report_current_and_following_values(): void
    {
        $this->useProfile('research_level_up', [100, 50, 25]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick - 1);

        $lines = $this->service->eventLines($this->colonyId, $this->tick);

        $this->assertCount(1, $lines);
        $this->assertSame('research_level_up', $lines[0]['type']);
        $this->assertSame($this->tick - 1, $lines[0]['tick']);
        $this->assertSame(1, $lines[0]['value']);
        $this->assertSame([1], $lines[0]['following']); // 25 % of 2 = 0.5 -> 1, then over
    }

    public function test_event_lines_sum_equals_the_calculated_event_part(): void
    {
        $this->useProfile('research_level_up', [100, 50, 25]);
        $this->service->fireEvent($this->colonyId, 'research_level_up', $this->tick - 1);
        $this->service->fireEvent($this->colonyId, 'trade_success', $this->tick);

        $sum = array_sum(array_column($this->service->eventLines($this->colonyId, $this->tick), 'value'));

        $this->assertSame($sum, $this->service->calculateTrust($this->colonyId, $this->tick));
    }
}
