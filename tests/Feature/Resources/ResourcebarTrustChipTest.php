<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A6 (GDD §18.2): the trust chip escalates — neutral, amber below 0, red below -10.
 */
class ResourcebarTrustChipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    private function trustChipClass(int $trust): string
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => 1, 'resource_id' => 12],
            ['amount' => $trust]
        );
        $user = User::where('user_id', 3)->firstOrFail();
        $html = $this->actingAs($user)->get(route('nexusdb.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/id="resbar-ap-trust"\s+class="ap-chip ([^"]*)"/', $html);
        preg_match('/id="resbar-ap-trust"\s+class="ap-chip ([^"]*)"/', $html, $m);

        return $m[1];
    }

    public function test_trust_at_zero_or_above_is_neutral(): void
    {
        $this->assertSame('ap-chip--trust-neu', $this->trustChipClass(0));
    }

    public function test_trust_below_zero_is_amber_warning(): void
    {
        $this->assertSame('ap-chip--trust-warn', $this->trustChipClass(-5));
        $this->assertSame('ap-chip--trust-warn', $this->trustChipClass(-10));
    }

    public function test_trust_below_minus_ten_is_red(): void
    {
        $this->assertSame('ap-chip--trust-neg', $this->trustChipClass(-11));
    }

    public function test_high_trust_stays_positive(): void
    {
        $this->assertSame('ap-chip--trust-pos', $this->trustChipClass(25));
    }
}
