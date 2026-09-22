<?php

namespace Tests\Feature;

/**
 * MerchantService::getForecast() — Marktbericht (Konsul, GDD §12, A13).
 *
 * The Konsul announces Corvan's next visit ahead of time (lead in Sols grows
 * with rank; from game.merchant.forecast_inventory_min_rank the special-inventory
 * categories are shown too). Pure planning information: read-only, never alters
 * the deterministic visit schedule.
 *
 * The oracle for "when does the next visit start" is the real shouldSpawn() —
 * the forecast is verified against the schedule the tick loop actually follows.
 *
 * Covered scenarios:
 *  - test_no_forecast_without_consul
 *  - test_rank_lead_time_is_respected (ranks 1/2/3, inside and just outside window)
 *  - test_no_forecast_when_consul_unavailable
 *  - test_no_forecast_while_visit_is_active
 *  - test_no_forecast_when_bar_not_built
 *  - test_categories_hidden_below_inventory_min_rank
 *  - test_categories_shown_at_rank_3
 *  - test_forecast_matches_actual_first_visit
 *  - test_forecast_matches_actual_follow_up_visit
 *  - test_forecast_is_read_only
 *  - test_forecast_does_not_change_schedule
 *  - test_forecast_lead_time_is_config_driven
 */

use App\Services\MerchantService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MerchantForecastTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private const TRADER_ADVISOR_ID = 92;

    private const BAR_BUILDING_ID = 52;

    private MerchantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(MerchantService::class);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 1]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setConsul(?int $rank, ?int $unavailableUntilTick = null): void
    {
        DB::table('advisors')
            ->where('colony_id', self::COLONY_ID)
            ->where('personell_id', self::TRADER_ADVISOR_ID)
            ->delete();

        if ($rank === null) {
            return;
        }

        DB::table('advisors')->insert([
            'colony_id' => self::COLONY_ID,
            'personell_id' => self::TRADER_ADVISOR_ID,
            'user_id' => self::USER_ID,
            'rank' => $rank,
            'active_ticks' => 0,
            'unavailable_until_tick' => $unavailableUntilTick,
        ]);
    }

    /** First tick at or after $from at which the real spawn check fires. */
    private function nextSpawnTick(int $from): int
    {
        for ($tick = $from; $tick < $from + 60; $tick++) {
            if ($this->service->shouldSpawn(self::COLONY_ID, $tick)) {
                return $tick;
            }
        }

        $this->fail('shouldSpawn never fired within 60 ticks');
    }

    /** Map the actually spawned visit's item types to forecast categories. */
    private function actualCategories(int $visitId): array
    {
        $map = config('game.merchant.forecast_categories');
        $categories = [];
        foreach ($this->service->getItemsForVisit($visitId) as $item) {
            $categories[$map[$item->item_type]] = true;
        }

        return array_keys($categories);
    }

    private function counts(): array
    {
        return [
            DB::table('merchant_visits')->count(),
            DB::table('merchant_items')->count(),
            DB::table('bar_offers')->count(),
            DB::table('advisors')->count(),
        ];
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_no_forecast_without_consul(): void
    {
        $this->setConsul(null);
        $start = $this->nextSpawnTick(15);

        $this->assertNull($this->service->getForecast(self::COLONY_ID, $start - 1));
    }

    public function test_rank_lead_time_is_respected(): void
    {
        $start = $this->nextSpawnTick(15);

        foreach ([1 => 1, 2 => 2, 3 => 3] as $rank => $lead) {
            $this->setConsul($rank);

            $inside = $this->service->getForecast(self::COLONY_ID, $start - $lead);
            $this->assertNotNull($inside, "rank {$rank} must announce {$lead} Sol ahead");
            $this->assertSame($lead, $inside['sols']);
            $this->assertSame($start, $inside['tick']);

            $this->assertNotNull($this->service->getForecast(self::COLONY_ID, $start - 1), "rank {$rank} at 1 Sol");

            $this->assertNull(
                $this->service->getForecast(self::COLONY_ID, $start - $lead - 1),
                "rank {$rank} must not announce more than {$lead} Sol ahead"
            );
        }
    }

    public function test_no_forecast_when_consul_unavailable(): void
    {
        $start = $this->nextSpawnTick(15);
        $this->setConsul(3, $start + 5);

        $this->assertNull($this->service->getForecast(self::COLONY_ID, $start - 1));
    }

    public function test_no_forecast_while_visit_is_active(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);
        $this->service->spawnVisit(self::COLONY_ID, $start);

        // Visit runs $start .. $start + duration - 1.
        $this->assertNull($this->service->getForecast(self::COLONY_ID, $start));
        $this->assertNull($this->service->getForecast(self::COLONY_ID, $start + 1));
    }

    public function test_no_forecast_when_bar_not_built(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);

        DB::table('colony_buildings')
            ->where('colony_id', self::COLONY_ID)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->update(['level' => 0]);

        $this->assertNull($this->service->getForecast(self::COLONY_ID, $start - 1));
    }

    public function test_categories_hidden_below_inventory_min_rank(): void
    {
        $start = $this->nextSpawnTick(15);

        foreach ([1, 2] as $rank) {
            $this->setConsul($rank);
            $forecast = $this->service->getForecast(self::COLONY_ID, $start - 1);

            $this->assertNotNull($forecast);
            $this->assertNull($forecast['categories'], "rank {$rank} must not see categories");
        }
    }

    public function test_categories_shown_at_rank_3(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);

        $forecast = $this->service->getForecast(self::COLONY_ID, $start - 1);

        $this->assertNotNull($forecast);
        $this->assertIsArray($forecast['categories']);
        $this->assertNotEmpty($forecast['categories']);
        $this->assertSame(
            array_values(array_unique($forecast['categories'])),
            $forecast['categories'],
            'categories must be unique'
        );
        foreach ($forecast['categories'] as $category) {
            $this->assertContains($category, config('game.merchant.forecast_categories'));
        }
    }

    public function test_forecast_matches_actual_first_visit(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);

        $forecast = $this->service->getForecast(self::COLONY_ID, $start - 3);
        $this->assertNotNull($forecast);
        $this->assertSame($start, $forecast['tick']);

        $this->service->spawnVisit(self::COLONY_ID, $start);
        $visit = $this->service->getActiveVisit(self::COLONY_ID, $start);

        $this->assertSame($start, (int) $visit->tick_start);
        $this->assertEqualsCanonicalizing($this->actualCategories($visit->id), $forecast['categories']);
    }

    public function test_forecast_matches_actual_follow_up_visit(): void
    {
        $this->setConsul(3);
        $first = $this->nextSpawnTick(15);
        $this->service->spawnVisit(self::COLONY_ID, $first);

        // The visit is over from $first + duration onwards.
        $afterVisit = $first + (int) config('game.merchant.duration_ticks');
        $second = $this->nextSpawnTick($afterVisit);

        $forecast = $this->service->getForecast(self::COLONY_ID, $second - 3);
        $this->assertNotNull($forecast);
        $this->assertSame($second, $forecast['tick']);

        $this->service->spawnVisit(self::COLONY_ID, $second);
        $visit = $this->service->getActiveVisit(self::COLONY_ID, $second);

        $this->assertEqualsCanonicalizing($this->actualCategories($visit->id), $forecast['categories']);
    }

    public function test_forecast_is_read_only(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);
        $before = $this->counts();

        $this->service->getForecast(self::COLONY_ID, $start - 1);
        $this->service->getForecast(self::COLONY_ID, $start - 3);
        $this->service->getForecast(self::COLONY_ID, $start - 10);

        $this->assertSame($before, $this->counts());
    }

    public function test_forecast_does_not_change_schedule(): void
    {
        $this->setConsul(3);
        $start = $this->nextSpawnTick(15);

        for ($tick = 10; $tick < $start + 5; $tick++) {
            $this->service->getForecast(self::COLONY_ID, $tick);
        }

        $this->assertSame($start, $this->nextSpawnTick(15));
        $this->assertFalse($this->service->shouldSpawn(self::COLONY_ID, $start - 1));
    }

    public function test_forecast_lead_time_is_config_driven(): void
    {
        $this->setConsul(1);
        $start = $this->nextSpawnTick(15);

        config(['game.merchant.forecast_sols' => [0 => 0, 1 => 2, 2 => 2, 3 => 3]]);

        $this->assertSame(2, $this->service->getForecast(self::COLONY_ID, $start - 2)['sols']);
    }
}
