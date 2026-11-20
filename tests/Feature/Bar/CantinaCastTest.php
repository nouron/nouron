<?php

namespace Tests\Feature\Bar;

use App\Models\User;
use App\Services\CantinaPresenceService;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * T60 (owner 2026-11-20): Tomas is always there; besides him one to three figures sit in
 * the cantina per Sol. The travelling merchant (Corvan) comes first, the rest is pure
 * chance. The cast of a Sol is fixed: resolving one figure never lets another one in.
 */
class CantinaCastTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TICK = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->atTick(self::TICK);
        foreach (['bar_offers', 'bar_encounters', 'bar_concerns', 'bar_information_encounters', 'merchant_visits'] as $table) {
            DB::table($table)->where('colony_id', self::COLONY_ID)->delete();
        }
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 52, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        config([
            'game.bypass.ap_checks' => false,
            'game.bar.story_encounter.chance' => 0.0,
            'game.bar.guests_per_sol' => ['min' => 1, 'max' => 3],
        ]);
    }

    private function atTick(int $tick): void
    {
        $this->app->instance(TickService::class, new TickService($tick));
        $this->app->forgetInstance(CantinaPresenceService::class);
    }

    private function presence(): CantinaPresenceService
    {
        return $this->app->make(CantinaPresenceService::class);
    }

    private function offer(?int $visitId = null, int $expires = 1000): int
    {
        return DB::table('bar_offers')->insertGetId([
            'colony_id' => self::COLONY_ID, 'give_resource_id' => 3, 'give_amount' => 5,
            'get_resource_id' => 1, 'get_amount' => 10, 'expires_tick' => $expires, 'visit_id' => $visitId,
        ]);
    }

    private function concern(string $slug): int
    {
        return DB::table('bar_concerns')->insertGetId([
            'colony_id' => self::COLONY_ID, 'character_slug' => $slug,
            'created_tick' => self::TICK, 'expires_tick' => 1000,
        ]);
    }

    private function merchantVisit(): int
    {
        return DB::table('merchant_visits')->insertGetId([
            'colony_id' => self::COLONY_ID, 'tick_start' => 0, 'tick_end' => 1000,
        ]);
    }

    /** A full cantina: five guest offers, a concern, an encounter and an information figure. */
    private function crowd(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->offer();
        }
        $this->concern('founder');
        DB::table('bar_encounters')->insert([
            'colony_id' => self::COLONY_ID, 'type' => 'auction', 'expires_tick' => 1000,
            'credits_amount' => 10, 'give_resource_id' => 3, 'give_amount' => 5, 'win_chance' => 0.5,
        ]);
        DB::table('bar_information_encounters')->insert([
            'colony_id' => self::COLONY_ID, 'character_slug' => 'veteran', 'outcome_key' => 'drill_buffer',
            'created_tick' => self::TICK, 'expires_tick' => 1000,
        ]);
    }

    public function test_one_to_three_figures_per_sol_and_the_count_varies(): void
    {
        $this->crowd();
        $sizes = [];
        for ($tick = self::TICK; $tick < self::TICK + 40; $tick++) {
            $this->atTick($tick);
            $size = count($this->presence()->cast(self::COLONY_ID, $tick));
            $this->assertGreaterThanOrEqual(1, $size, "tick {$tick}");
            $this->assertLessThanOrEqual(3, $size, "tick {$tick}");
            $sizes[$size] = true;
        }
        $this->assertCount(3, $sizes, 'over 40 Sols each count 1, 2 and 3 appears');
    }

    public function test_the_travelling_merchant_always_gets_a_place(): void
    {
        $this->crowd();
        $this->offer($this->merchantVisit());
        for ($tick = self::TICK; $tick < self::TICK + 20; $tick++) {
            $this->atTick($tick);
            $this->assertContains('merchant', $this->presence()->cast(self::COLONY_ID, $tick), "tick {$tick}");
        }
    }

    public function test_the_cast_stays_fixed_when_a_figure_is_resolved(): void
    {
        config(['game.bar.guests_per_sol' => ['min' => 1, 'max' => 1]]);
        $this->offer();
        $this->offer();
        $this->offer();
        $cast = $this->presence()->cast(self::COLONY_ID, self::TICK);
        $this->assertCount(1, $cast);

        $chosen = (int) substr($cast[0], strlen('offer_'));
        DB::table('bar_offers')->where('id', $chosen)->update(['is_accepted' => true]);

        $this->assertSame($cast, $this->presence()->cast(self::COLONY_ID, self::TICK));
        $data = $this->actingAs(User::find(3))->getJson(route('colony.bar'))->assertOk()->json();
        $this->assertSame([], $data['offers'], 'nobody takes the empty place');
    }

    public function test_cantina_data_shows_only_the_cast(): void
    {
        config(['game.bar.guests_per_sol' => ['min' => 1, 'max' => 1]]);
        $corvan = $this->offer($this->merchantVisit());
        $this->offer();
        $this->concern('founder');

        $data = $this->actingAs(User::find(3))->getJson(route('colony.bar'))->assertOk()->json();

        $this->assertSame([$corvan], array_column($data['offers'], 'id'));
        $this->assertNull($data['concern']);
        $this->assertNotNull($data['merchantVisit']);
        $this->assertSame(['bartender'], $this->presence()->presentCharacters(self::COLONY_ID, self::TICK));
    }

    public function test_absent_figures_cannot_be_used(): void
    {
        config(['game.bar.guests_per_sol' => ['min' => 1, 'max' => 1]]);
        $this->offer($this->merchantVisit());
        $guest = $this->offer();
        $concern = $this->concern('founder');
        $user = User::find(3);

        $this->actingAs($user)->postJson(route('colony.bar.accept', ['offer' => $guest]))
            ->assertStatus(422)->assertJson(['error' => 'not_present']);
        $this->actingAs($user)->postJson(route('colony.bar.resolve-concern', ['concern' => $concern]))
            ->assertStatus(422)->assertJson(['error' => 'not_present']);
        $this->assertFalse((bool) DB::table('bar_offers')->where('id', $guest)->value('is_accepted'));
    }

    public function test_offer_spots_do_not_shift_when_an_earlier_offer_is_accepted(): void
    {
        config(['game.bar.guests_per_sol' => ['min' => 3, 'max' => 3]]);
        $ids = [$this->offer(), $this->offer(), $this->offer()];
        $before = $this->presence()->offerSpots(self::COLONY_ID, self::TICK);

        DB::table('bar_offers')->where('id', $ids[0])->update(['is_accepted' => true]);

        $after = $this->presence()->offerSpots(self::COLONY_ID, self::TICK);
        $this->assertSame($before[$ids[1]], $after[$ids[1]]);
        $this->assertSame($before[$ids[2]], $after[$ids[2]]);
    }

    public function test_no_cast_without_a_cantina(): void
    {
        DB::table('colony_buildings')->where(['colony_id' => self::COLONY_ID, 'building_id' => 52])->update(['level' => 0]);
        $this->crowd();

        $this->assertSame([], $this->presence()->cast(self::COLONY_ID, self::TICK));
    }
}
