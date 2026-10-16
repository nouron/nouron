<?php

namespace Tests\Feature\Playtest;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Q19: profile `lean-explore` explores only when a harvester relocation needs an explored
 * regolith target (ColonyController::placeBuilding(): harvester => not_explored). Regular
 * buildings may be placed on fogged zone tiles (placing reveals them), so they never need
 * exploration. The default profile keeps exploring unconditionally.
 */
class BotStrategyLeanExploreTest extends TestCase
{
    use RefreshDatabase;

    private function exploreWhen(string $profile, BotSession $bot): ?object
    {
        foreach (BotStrategy::default(BotProfile::named($profile)) as $rule) {
            if ($rule['name'] === 'explore_tile') {
                return ($rule['when'])($bot);
            }
        }
        $this->fail('explore_tile rule missing');
    }

    /** Harvester sits on a depleted tile and no explored free regolith tile exists: relocation is blocked. */
    private function blockRelocation(BotSession $bot): void
    {
        $harvester = DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', 27)->where('instance_id', 1)->first();
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)
            ->where('q', $harvester->tile_x)->where('r', $harvester->tile_y)->update(['resource_amount' => 10]);
        DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('is_explored', 1)
            ->where('tile_type', 'like', 'regolith_%')
            ->where(fn ($q) => $q->where('q', '!=', $harvester->tile_x)->orWhere('r', '!=', $harvester->tile_y))
            ->update(['tile_type' => 'terrain_plain']);
    }

    public function test_default_profile_explores_even_when_nothing_requires_it(): void
    {
        $bot = BotSession::boot($this, seed: 11);

        $this->assertNotNull($this->exploreWhen('default', $bot));
    }

    public function test_lean_explore_skips_exploration_when_the_harvester_is_fine(): void
    {
        $bot = BotSession::boot($this, seed: 11);

        $this->assertNull($this->exploreWhen('lean-explore', $bot));
    }

    public function test_lean_explore_explores_when_relocation_has_no_explored_target(): void
    {
        $bot = BotSession::boot($this, seed: 11);
        $this->blockRelocation($bot);

        $this->assertNotNull($this->exploreWhen('lean-explore', $bot));
    }

    public function test_lean_explore_skips_when_an_explored_free_regolith_target_exists(): void
    {
        $bot = BotSession::boot($this, seed: 11);
        $this->blockRelocation($bot);
        $tile = DB::table('colony_tiles')->where('colony_id', $bot->colonyId)->where('ring', 2)->where('is_colony_zone', 0)->orderBy('q')->orderBy('r')->first();
        DB::table('colony_tiles')->where('id', $tile->id)
            ->update(['tile_type' => 'regolith_y1_d2', 'is_explored' => 1, 'resource_amount' => 100, 'resource_max' => 450]);

        $this->assertNull($this->exploreWhen('lean-explore', $bot));
    }
}
