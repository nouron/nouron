<?php

namespace Tests\Feature\Onboarding;

use App\Models\User;
use App\Services\OnboardingService;
use App\Services\ResourcesService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T43-G8: the start colonist count is derived from the placed start buildings
 * (Command Center + Wohnhabitat), not a hard-coded number — header bar and
 * source rows must agree before the first Sol tick.
 */
class OnboardingStartColonistsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ReferenceDataSeeder::class)->run();
    }

    private function newPlayer(): array
    {
        $user = User::create([
            'username' => 'colonist_tester',
            'display_name' => 'colonist_tester',
            'role' => 'player',
            'password' => 'secret',
            'email' => 'colonist_tester@example.invalid',
            'activated' => 1,
            'activation_key' => '',
        ]);
        $colony = app(OnboardingService::class)->setupNewPlayer((int) $user->user_id);

        return [(int) $user->user_id, (int) $colony->id];
    }

    private function assertColonistsMatchSources(int $colonyId): void
    {
        $svc = app(ResourcesService::class);
        $breakdown = $svc->getSupplyBreakdown($colonyId);
        $sum = $breakdown['sources']['cc'] + $breakdown['sources']['housing'];

        $this->assertSame($sum, $svc->colonistsOverview($colonyId)['colonists']);
        $this->assertSame($svc->calculateSupplyCap($colonyId), $svc->colonistsOverview($colonyId)['colonists']);
        $this->assertGreaterThan(0, $sum);
    }

    public function test_new_player_start_colonists_equal_sum_of_sources(): void
    {
        [, $colonyId] = $this->newPlayer();
        $this->assertColonistsMatchSources($colonyId);
    }

    public function test_reset_to_sol1_start_colonists_equal_sum_of_sources(): void
    {
        [$userId, $colonyId] = $this->newPlayer();
        DB::table('user_resources')->where('user_id', $userId)->update(['supply' => 99]);

        app(OnboardingService::class)->resetColonyToSol1($userId, $colonyId);

        $this->assertColonistsMatchSources($colonyId);
    }
}
