<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\BarService;
use App\Services\OnboardingService;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

/**
 * Review finding (R5b fix round 1): the negotiation roll was seeded with the
 * offer's position among the colony's offers. A failed negotiation deletes its
 * offer, so the next offer moved down into that position and — negotiated in
 * the same Sol — got the identical seed, i.e. failed with certainty.
 *
 * The roll of an offer must not depend on which other offers were deleted.
 */
class BarNegotiationSeedStabilityTest extends TestCase
{
    use RefreshDatabase;

    private const TICK = 5;

    private int $chance = 0;

    private BarService $bar;

    private int $colonyId;

    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        app(ReferenceDataSeeder::class)->run();

        $this->userId = (int) User::create([
            'username' => 'negotiator', 'display_name' => 'negotiator', 'role' => 'player',
            'password' => 'secret', 'email' => 'negotiator@example.invalid', 'activated' => 1, 'activation_key' => '',
        ])->user_id;
        $this->colonyId = (int) app(OnboardingService::class)->setupNewPlayer($this->userId)->id;
        app(OnboardingService::class)->resetColonyToSol1($this->userId, $this->colonyId, rngSeed: 4242);

        DB::table('colony_buildings')->insert([
            'colony_id' => $this->colonyId, 'building_id' => 52, 'instance_id' => 1,
            'level' => 1, 'ap_spend' => 0, 'status_points' => 20, 'tile_x' => 2, 'tile_y' => -1,
        ]);
        DB::table('colony_resources')->where('colony_id', $this->colonyId)->update(['amount' => 10000]);
        DB::table('user_resources')->where('user_id', $this->userId)->update(['credits' => 100000]);
        config([
            'game.bypass.ap_checks' => true,
            'game.bar.guest_count.1' => [3, 3],
            'game.bar.level_max_concurrent.1' => 3,
        ]);

        // Partial mock with the real constructor dependencies: only the Konsul rank and
        // the success chance are controlled, the roll itself is the production code.
        $args = array_map(
            fn (\ReflectionParameter $p) => app($p->getType()->getName()),
            (new \ReflectionMethod(BarService::class, '__construct'))->getParameters()
        );
        $this->bar = Mockery::mock(BarService::class, $args)->makePartial();
        $this->bar->shouldReceive('traderRank')->andReturn(1);
        $this->bar->shouldReceive('negotiateChance')->andReturnUsing(fn () => ['total_percent' => $this->chance]);
        $this->bar->generateOffersForColony($this->colonyId, self::TICK);
    }

    /** The offer's roll (0..99): the smallest chance at which the negotiation succeeds, minus one. */
    private function rollOf(int $offerId): int
    {
        for ($this->chance = 1; $this->chance <= 100; $this->chance++) {
            DB::beginTransaction();
            $result = $this->bar->negotiateOffer($this->colonyId, $offerId, $this->userId, self::TICK);
            DB::rollBack();
            $this->assertTrue($result['ok'], json_encode($result));
            if ($result['success']) {
                return $this->chance - 1;
            }
        }

        $this->fail('negotiation never succeeded');
    }

    public function test_a_failed_negotiation_does_not_change_the_roll_of_the_next_offer_in_the_same_sol(): void
    {
        $offerIds = DB::table('bar_offers')->where('colony_id', $this->colonyId)->orderBy('id')->pluck('id')->all();
        $this->assertCount(3, $offerIds);
        [$first, $second] = $offerIds;

        $before = $this->rollOf($second);

        $this->chance = 0; // the first negotiation fails — its offer is deleted
        $result = $this->bar->negotiateOffer($this->colonyId, $first, $this->userId, self::TICK);
        $this->assertFalse($result['success']);
        $this->assertFalse(DB::table('bar_offers')->where('id', $first)->exists());

        $this->assertSame($before, $this->rollOf($second));
    }

    public function test_offers_of_one_sol_do_not_share_a_roll(): void
    {
        $rolls = DB::table('bar_offers')->where('colony_id', $this->colonyId)->orderBy('id')->pluck('id')
            ->map(fn ($id) => $this->rollOf((int) $id))->all();

        // Three independent 0..99 rolls are all equal only with chance 1:10000.
        $this->assertGreaterThan(1, count(array_unique($rolls)), json_encode($rolls));
    }
}
