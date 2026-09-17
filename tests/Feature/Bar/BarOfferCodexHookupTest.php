<?php

namespace Tests\Feature\Bar;

/**
 * Charakter-Kodex (A42) trigger hookup for bar_trade figures (Dax/Vesper/
 * Sarka/Maret/Fen/Juno) — "abgeschlossener Tausch mit der personalisierten
 * Flavor-Zeile der Figur" (A36's bar_trade_flavor_* assignment). The guest
 * offer itself carries no server-side character association (the flavor
 * line is a UI-only, slot-based assignment — see resources/views/colony/
 * bar.blade.php), so the caller (BarController/Blade) passes the shown
 * character_slug explicitly to acceptOffer() when accepting.
 *
 * Covered scenarios:
 *  - test_accept_with_bar_trade_character_slug_records_codex_progress
 *  - test_accept_without_character_slug_records_no_codex_progress
 *  - test_accept_with_a_non_bar_trade_character_slug_records_no_codex_progress
 */

use App\Services\BarService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BarOfferCodexHookupTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const USER_ID = 3;

    private BarService $barService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->barService = $this->app->make(BarService::class);
    }

    private function insertOffer(array $overrides = []): int
    {
        $defaults = [
            'colony_id' => self::COLONY_ID,
            'give_resource_id' => 3,
            'give_amount' => 10,
            'get_resource_id' => 4,
            'get_amount' => 5,
            'expires_tick' => 50,
            'is_accepted' => false,
            'is_negotiated' => false,
        ];

        return DB::table('bar_offers')->insertGetId(array_merge($defaults, $overrides));
    }

    private function codexEntryCount(string $slug): int
    {
        return DB::table('character_codex_entries')
            ->where('user_id', self::USER_ID)
            ->where('character_slug', $slug)
            ->count();
    }

    public function test_accept_with_bar_trade_character_slug_records_codex_progress(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 3],
            ['amount' => 100]
        );
        $id = $this->insertOffer();

        $result = $this->barService->acceptOffer(self::COLONY_ID, $id, self::USER_ID, 10, 'prospector');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $this->codexEntryCount('prospector'));
    }

    public function test_accept_without_character_slug_records_no_codex_progress(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 3],
            ['amount' => 100]
        );
        $id = $this->insertOffer();

        $result = $this->barService->acceptOffer(self::COLONY_ID, $id, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $this->codexEntryCount('prospector'));
    }

    public function test_accept_with_a_non_bar_trade_character_slug_records_no_codex_progress(): void
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => self::COLONY_ID, 'resource_id' => 3],
            ['amount' => 100]
        );
        $id = $this->insertOffer();

        // 'veteran' (Deva) is game_role 'information', not 'bar_trade' — must
        // not be creditable via a guest-offer trade.
        $result = $this->barService->acceptOffer(self::COLONY_ID, $id, self::USER_ID, 10, 'veteran');

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $this->codexEntryCount('veteran'));
    }
}
