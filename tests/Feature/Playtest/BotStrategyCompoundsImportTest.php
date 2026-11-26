<?php

namespace Tests\Feature\Playtest;

use App\Enums\BuildingId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * M4 2026-11-24: no bot ever built an infirmary or a security hub — each needs 25 Werkstoffe,
 * which temple and monument used up first, while thousands of Credits lay idle. Since
 * 2026-11-26 health and defense hang on exactly these buildings, so in Phase 2 the bot buys
 * the missing Werkstoffe from Nexus (Uplink-Station) when the Credits stay above a reserve.
 */
class BotStrategyCompoundsImportTest extends TestCase
{
    use RefreshDatabase;

    private function rule(): array
    {
        foreach (BotStrategy::default(BotProfile::named('default')) as $rule) {
            if ($rule['name'] === 'import_compounds_for_building') {
                return $rule;
            }
        }

        $this->fail('Rule import_compounds_for_building not found');
    }

    private function boot(int $credits, int $compounds): BotSession
    {
        $bot = BotSession::boot($this, 1);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 2, 'phase2_start_tick' => 1]);
        DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', BuildingId::CommandCenter->value)->update(['level' => 3]);
        foreach ([BuildingId::UplinkStation->value => [-1, 1], 46 => null, 53 => null] as $id => $tile) {
            DB::table('colony_buildings')->where('colony_id', $bot->colonyId)->where('building_id', $id)->delete();
        }
        DB::table('colony_buildings')->insert([
            'colony_id' => $bot->colonyId, 'building_id' => BuildingId::UplinkStation->value, 'instance_id' => 1,
            'level' => 1, 'status_points' => 20, 'ap_spend' => 0, 'tile_x' => -1, 'tile_y' => 1,
        ]);
        DB::table('user_resources')->where('user_id', $bot->userId)->update(['credits' => $credits, 'supply' => 1000]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 3], ['amount' => 500]);
        DB::table('colony_resources')->updateOrInsert(['colony_id' => $bot->colonyId, 'resource_id' => 4], ['amount' => $compounds]);

        return $bot;
    }

    public function test_buys_the_missing_werkstoffe_for_an_unbuilt_security_hub(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);

        $amount = ($this->rule()['when'])($bot);

        $this->assertSame(20, $amount, '25 needed, 5 in stock');
        $this->assertTrue(($this->rule()['do'])($bot, $amount)['ok']);
        $this->assertSame(25, (int) DB::table('colony_resources')->where('colony_id', $bot->colonyId)->where('resource_id', 4)->value('amount'));
    }

    public function test_keeps_its_credits_when_the_import_would_eat_the_reserve(): void
    {
        $bot = $this->boot(credits: 2000, compounds: 5);

        $this->assertNull(($this->rule()['when'])($bot));
    }

    public function test_does_nothing_in_phase_one(): void
    {
        $bot = $this->boot(credits: 8000, compounds: 5);
        DB::table('runs')->where('id', $bot->runId)->update(['phase' => 1]);

        $this->assertNull(($this->rule()['when'])($bot));
    }
}
