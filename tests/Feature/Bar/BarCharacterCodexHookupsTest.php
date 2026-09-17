<?php

namespace Tests\Feature\Bar;

/**
 * Charakter-Kodex (A42) trigger hookups across the Cantina cast's various
 * mechanics — see CharacterCodexService::recordProgress() call sites.
 *
 * Covered scenarios:
 *  - test_wager_resolution_records_codex_progress_for_gambler
 *  - test_auction_resolution_records_codex_progress_for_scrap_dealer
 *  - test_corporate_contact_purchase_records_codex_progress_for_corporate_rep
 */

use App\Services\BarService;
use App\Services\CorporateContactService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarCharacterCodexHookupsTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1; // Springfield — user_id = 3 (Bart)

    private const USER_ID = 3;

    private const RES_REGOLITH = 3;

    private const RES_ORGANICS = 5;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    private function insertEncounter(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'type' => 'wager',
            'give_resource_id' => self::RES_REGOLITH,
            'give_amount' => 20,
            'win_chance' => 0.45,
            'credits_amount' => 50,
            'duration_ticks' => null,
            'expires_tick' => 50,
            'is_accepted' => false,
            'resolved' => false,
            'won' => null,
            'ends_tick' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return DB::table('bar_encounters')->insertGetId(array_merge($defaults, $overrides));
    }

    private function setColonyResource(int $resourceId, int $amount): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => $resourceId],
            ['amount' => $amount]
        );
    }

    private function codexEntryCount(string $slug): int
    {
        return DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID)
            ->where('character_slug', $slug)
            ->count();
    }

    public function test_wager_resolution_records_codex_progress_for_gambler(): void
    {
        $this->setColonyResource(self::RES_REGOLITH, 100);
        $id = $this->insertEncounter(['type' => 'wager', 'win_chance' => 1.0]);

        $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(1, $this->codexEntryCount('gambler'));
    }

    public function test_auction_resolution_records_codex_progress_for_scrap_dealer(): void
    {
        $this->setColonyResource(self::RES_ORGANICS, 100);
        $id = $this->insertEncounter(['type' => 'auction', 'give_resource_id' => self::RES_ORGANICS, 'give_amount' => 20]);

        $this->barService->acceptEncounter(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertSame(1, $this->codexEntryCount('scrap_dealer'));
    }

    public function test_corporate_contact_purchase_records_codex_progress_for_corporate_rep(): void
    {
        DB::table('colony_buildings')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'building_id' => 25, 'instance_id' => 1],
            ['level' => 3, 'status_points' => 20, 'ap_spend' => 0]
        );
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['credits' => 5000]);
        config([
            'game.corporate_contact.appearance_interval_min' => 1,
            'game.corporate_contact.appearance_interval_max' => 1,
            'game.corporate_contact.offer_chance' => 1.0,
        ]);

        $service = $this->app->make(CorporateContactService::class);

        // Scan a small tick window for one where both the appearance and the
        // offer rolls hit — mirrors the deterministic-scan approach used by
        // other CorporateContactService/GameTick tests in this codebase.
        $bought = false;
        for ($tick = 15; $tick < 60 && ! $bought; $tick++) {
            $result = $service->buyHarvesterOffer(self::COLONY_ID, self::USER_ID, $tick);
            if ($result['ok']) {
                $bought = true;
            }
        }

        $this->assertTrue($bought, 'precondition: at least one tick in the scanned window must produce a purchasable offer');
        $this->assertSame(1, $this->codexEntryCount('corporate_rep'));
    }
}
