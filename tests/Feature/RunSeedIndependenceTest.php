<?php

namespace Tests\Feature;

use App\Console\Commands\GameTick;
use App\Models\Colony;
use App\Models\User;
use App\Services\BarService;
use App\Services\ColonyTileService;
use App\Services\CorporateContactService;
use App\Services\MerchantService;
use App\Services\OnboardingService;
use App\Support\RunSeed;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * R5b: game rolls must derive from runs.rng_seed + Sol + domain salt only — never
 * from colony, run or user ids. In a shared database (production, parallel
 * playtest bots) those ids depend on start order, so an id in the seed made the
 * same rng_seed play out differently (found 2026-10-04: seed 1 lost to an
 * instability roll that only happened because its colony got id 4 instead of 1).
 *
 * Two colonies with DIFFERENT ids but the SAME rng_seed and identical state must
 * see identical rolls.
 */
class RunSeedIndependenceTest extends TestCase
{
    use RefreshDatabase;

    private const RNG_SEED = 4242;

    private int $colonyA;

    private int $colonyB;

    protected function setUp(): void
    {
        parent::setUp();
        app(ReferenceDataSeeder::class)->run();

        $this->colonyA = $this->newColony('seed_a');
        $this->newColony('filler_1');
        $this->newColony('filler_2');
        $this->colonyB = $this->newColony('seed_b');
        $this->assertNotSame($this->colonyA, $this->colonyB);
    }

    private function newColony(string $name): int
    {
        $userId = (int) User::create([
            'username' => $name,
            'display_name' => $name,
            'role' => 'player',
            'password' => 'secret',
            'email' => "{$name}@example.invalid",
            'activated' => 1,
            'activation_key' => '',
        ])->user_id;
        $colonyId = (int) app(OnboardingService::class)->setupNewPlayer($userId)->id;
        app(OnboardingService::class)->resetColonyToSol1($userId, $colonyId, rngSeed: self::RNG_SEED);

        return $colonyId;
    }

    private function buildBar(int $colonyId): void
    {
        DB::table('colony_buildings')->insert([
            'colony_id' => $colonyId, 'building_id' => 52, 'instance_id' => 1,
            'level' => 1, 'ap_spend' => 0, 'status_points' => 20, 'tile_x' => 2, 'tile_y' => -1,
        ]);
    }

    /** @return list<array<string,mixed>> rows of $table for the colony, without ids */
    private function rows(string $table, int $colonyId, array $columns): array
    {
        return DB::table($table)->where('colony_id', $colonyId)->orderBy('id')->get($columns)
            ->map(fn ($r) => (array) $r)->all();
    }

    private function invokePrivate(object $service, string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod($service, $method))->invoke($service, ...$args);
    }

    public function test_bar_rolls_do_not_depend_on_the_colony_id(): void
    {
        config([
            'game.bar.story_encounter.chance' => 0.5,
            'game.bar.encounter.spawn_chance_per_level.1' => 0.5,
            'game.bar.concern.spawn_chance_per_level.1' => 0.5,
            'game.bar.information_pool.spawn_chance_per_tick' => 0.5,
        ]);
        $this->buildBar($this->colonyA);
        $this->buildBar($this->colonyB);
        $bar = app(BarService::class);

        for ($tick = 1; $tick <= 30; $tick++) {
            $this->assertSame($bar->pickStoryEncounter($this->colonyA, $tick), $bar->pickStoryEncounter($this->colonyB, $tick), "story encounter, tick {$tick}");
            foreach ([$this->colonyA, $this->colonyB] as $colonyId) {
                $bar->generateOffersForColony($colonyId, $tick);
                $fired = $bar->generateEncounterForColony($colonyId, $tick);
                $bar->generateConcernForColony($colonyId, $tick, $fired);
                $bar->generateInformationEncounterForColony($colonyId, $tick);
            }
        }

        $offer = ['give_resource_id', 'give_amount', 'get_resource_id', 'get_amount', 'expires_tick'];
        $this->assertSame($this->rows('bar_offers', $this->colonyA, $offer), $this->rows('bar_offers', $this->colonyB, $offer));
        $this->assertSame($this->rows('bar_encounters', $this->colonyA, ['type', 'expires_tick']), $this->rows('bar_encounters', $this->colonyB, ['type', 'expires_tick']));
        $this->assertSame($this->rows('bar_concerns', $this->colonyA, ['character_slug', 'created_tick']), $this->rows('bar_concerns', $this->colonyB, ['character_slug', 'created_tick']));
        $this->assertSame($this->rows('bar_information_encounters', $this->colonyA, ['character_slug', 'outcome_key', 'expires_tick']), $this->rows('bar_information_encounters', $this->colonyB, ['character_slug', 'outcome_key', 'expires_tick']));
    }

    public function test_merchant_schedule_and_stock_do_not_depend_on_the_colony_id(): void
    {
        config(['game.merchant.first_appearance_min' => 1, 'game.merchant.first_appearance_max' => 200]);
        $this->buildBar($this->colonyA);
        $this->buildBar($this->colonyB);
        $merchant = app(MerchantService::class);

        $spawnA = $spawnB = null;
        for ($tick = 1; $tick <= 200 && ($spawnA === null || $spawnB === null); $tick++) {
            $spawnA ??= $merchant->shouldSpawn($this->colonyA, $tick) ? $tick : null;
            $spawnB ??= $merchant->shouldSpawn($this->colonyB, $tick) ? $tick : null;
        }
        $this->assertNotNull($spawnA);
        $this->assertSame($spawnA, $spawnB, 'First merchant visit must fall on the same Sol');

        $merchant->spawnVisit($this->colonyA, $spawnA);
        $merchant->spawnVisit($this->colonyB, $spawnB);
        $offer = ['give_resource_id', 'give_amount', 'get_resource_id', 'get_amount'];
        $this->assertSame($this->rows('bar_offers', $this->colonyA, $offer), $this->rows('bar_offers', $this->colonyB, $offer));
    }

    public function test_corporate_contact_rolls_do_not_depend_on_the_colony_id(): void
    {
        $contact = app(CorporateContactService::class);

        for ($tick = 1; $tick <= 30; $tick++) {
            foreach (['appearanceRoll', 'offerRoll', 'priceRoll'] as $roll) {
                $this->assertSame(
                    $this->invokePrivate($contact, $roll, $this->colonyA, $tick),
                    $this->invokePrivate($contact, $roll, $this->colonyB, $tick),
                    "{$roll}, tick {$tick}"
                );
            }
        }
    }

    public function test_tick_storm_roll_does_not_depend_on_the_colony_id(): void
    {
        config(['game.encounter.storm.base_chance' => 0.5, 'game.encounter.storm.chance_cap' => 0.5]);
        $tick = app(GameTick::class);
        $results = [];

        foreach ([$this->colonyA, $this->colonyB] as $colonyId) {
            $colony = Colony::findOrFail($colonyId);
            for ($t = 1; $t <= 20; $t++) {
                $results[$colonyId][] = $this->invokePrivate($tick, 'rollStorm', $colony, $t, self::RNG_SEED, 1.0);
            }
        }

        $this->assertSame($results[$this->colonyA], $results[$this->colonyB]);
    }

    public function test_default_tile_map_does_not_depend_on_the_colony_id(): void
    {
        $tiles = app(ColonyTileService::class);
        $maps = [];

        foreach ([$this->colonyA, $this->colonyB] as $colonyId) {
            DB::table('colony_tiles')->where('colony_id', $colonyId)->delete();
            $tiles->generateDefaultTiles(Colony::findOrFail($colonyId));
            $maps[] = DB::table('colony_tiles')->where('colony_id', $colonyId)->orderBy('q')->orderBy('r')
                ->get(['q', 'r', 'tile_type'])->map(fn ($r) => (array) $r)->all();
        }

        $this->assertSame($maps[0], $maps[1]);
    }

    public function test_run_seed_uses_the_active_run_and_row_ordinals_count_per_colony(): void
    {
        $this->assertSame(self::RNG_SEED, RunSeed::forColony($this->colonyA));
        $this->assertSame(self::RNG_SEED, RunSeed::forColony($this->colonyB));

        foreach ([$this->colonyB, $this->colonyA, $this->colonyB, $this->colonyA] as $colonyId) {
            DB::table('bar_concerns')->insert(['colony_id' => $colonyId, 'character_slug' => 'x', 'created_tick' => 1, 'expires_tick' => 3]);
        }
        $idsA = DB::table('bar_concerns')->where('colony_id', $this->colonyA)->orderBy('id')->pluck('id')->all();

        $this->assertSame(1, RunSeed::rowOrdinal('bar_concerns', $this->colonyA, $idsA[0]));
        $this->assertSame(2, RunSeed::rowOrdinal('bar_concerns', $this->colonyA, $idsA[1]));
    }
}
