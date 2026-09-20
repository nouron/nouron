<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use App\Services\ResourcesService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A15 (GDD §6 Kolonisten-Framing): the supply chip reads "KOL used / cap"
 * ("47 Kolonisten im Einsatz / 60 verfügbar") instead of the abstract "SUP free / cap".
 */
class ResourcebarColonistChipTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 3;

    private const COLONY_ID = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function chipHtml(): string
    {
        $user = User::where('user_id', self::USER_ID)->firstOrFail();

        // Any page extending layouts.colony renders the header chip with the view composer's supplyBreakdown.
        return $this->actingAs($user)->get(route('nexusdb.index'))->assertOk()->getContent();
    }

    private function chipText(string $html): string
    {
        $this->assertMatchesRegularExpression('/class="res-chip res-Sup[^"]*".*?<span class="res-amount">(.*?)<\/span>/s', $html);
        preg_match('/class="res-chip res-Sup[^"]*".*?<span class="res-abbr">(.*?)<\/span>\s*<span class="res-amount">(.*?)<\/span>/s', $html, $m);

        return trim($m[1]).'|'.trim(preg_replace('/\s+/', ' ', $m[2]));
    }

    public function test_chip_uses_kol_label_and_shows_used_over_cap(): void
    {
        $breakdown = app(ResourcesService::class)->getSupplyBreakdown(self::COLONY_ID);
        $used = $breakdown['cap'] - $breakdown['free'];

        $text = $this->chipText($this->chipHtml());

        $this->assertSame('KOL|'.$used.' / '.$breakdown['cap'], $text);
    }

    public function test_chip_is_flagged_when_colony_is_over_capacity(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 1]);

        $this->assertStringContainsString('res-chip--over', $this->chipHtml());
    }

    public function test_chip_is_not_flagged_when_colony_has_free_capacity(): void
    {
        DB::table('user_resources')->where('user_id', self::USER_ID)->update(['supply' => 500]);

        $this->assertStringNotContainsString('res-chip--over', $this->chipHtml());
    }
}
