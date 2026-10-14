<?php

namespace Tests\Feature\Playtest\Utility;

use App\Enums\BuildingId;
use App\Services\HangarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;
use Tests\Feature\Playtest\Utility\Sources\FreighterSource;
use Tests\TestCase;

/** T30 §14 U5: buy the freighter for a waiting large find even with fewer than 3 advisors. */
class FreighterSourceTest extends TestCase
{
    use RefreshDatabase;
    use UtilityFixtures;

    private function bootHangar2(int $credits): BotSession
    {
        $bot = BotSession::boot($this, 1);
        $this->placeAt($bot, BuildingId::Hangar->value, 2);
        app(HangarService::class)->ensureGiftDrone($bot->colonyId);
        $this->setCredits($bot, $credits);
        $this->giveAp($bot, 10);

        return $bot;
    }

    /** @return list<Candidate> */
    private function candidates(BotSession $bot): array
    {
        return (new FreighterSource(25))->candidates($bot, BotContext::capture($bot, BotProfile::named('default'), withGoal: true));
    }

    public function test_a_waiting_large_find_makes_the_freighter_a_candidate(): void
    {
        $bot = $this->bootHangar2(5000);
        $this->makeFind($bot, 'find_large');

        $candidates = $this->candidates($bot);

        $this->assertSame(['ship:freighter'], array_map(fn (Candidate $c) => $c->key, $candidates));
        // Review S3 M3: own name, so a failed freighter request does not block legacy request_ship for the Sol.
        $this->assertSame('request_freighter', $candidates[0]->name);
        $this->assertGreaterThan(0.0, $candidates[0]->net);
    }

    public function test_no_waiting_find_no_freighter(): void
    {
        $this->assertSame([], $this->candidates($this->bootHangar2(5000)));
    }

    public function test_the_next_hire_must_stay_affordable(): void
    {
        $bot = $this->bootHangar2((int) config('ships.freighter.nexus_cost'));
        $this->makeFind($bot, 'find_large');

        $this->assertSame([], $this->candidates($bot));
    }

    public function test_the_credit_reserve_guard_blocks_the_freighter(): void
    {
        // Review S3 M3: same guard as legacy request_ship (BotStrategy::creditReserveGuardBlocks()).
        $buffer = (int) round((int) config('game.run.tasks.task_credit_reserve.threshold', 4000) * 1.1);
        $bot = $this->bootHangar2($buffer - 1);
        $this->makeFind($bot, 'find_large');
        $this->assertNotSame([], $this->candidates($bot), 'fixture: affordable without the objective');

        DB::table('run_objectives')->insert(['run_id' => $bot->runId, 'task_key' => 'task_credit_reserve', 'target_value' => 1, 'current_value' => 0]);

        $this->assertSame([], $this->candidates($bot));
    }
}
