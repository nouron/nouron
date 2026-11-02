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

    /** Shell data (GET /api/v1/shell) the header trust chip is drawn from. */
    private function shellForTrust(int $trust): array
    {
        DB::table('colony_resources')->updateOrInsert(
            ['colony_id' => 1, 'resource_id' => 12],
            ['amount' => $trust]
        );
        $user = User::where('user_id', 3)->firstOrFail();

        return $this->actingAs($user)->getJson('/api/v1/shell')->assertOk()->json();
    }

    private function resourceBar(): string
    {
        return file_get_contents(base_path('frontend/src/lib/components/ResourceBar.svelte'));
    }

    public function test_shell_delivers_trust_and_red_threshold(): void
    {
        foreach ([0, -5, -10, -11, 25] as $trust) {
            $shell = $this->shellForTrust($trust);
            $this->assertSame($trust, $shell['trust']);
            $this->assertSame(-10, $shell['trust_chip_red']);
        }
    }

    public function test_red_threshold_follows_config(): void
    {
        config(['game.run.trust_warning.chip_red' => -20]);

        $this->assertSame(-20, $this->shellForTrust(-15)['trust_chip_red']);
    }

    /**
     * Escalation order in the chip: positive from 20, red below trust_chip_red,
     * amber below 0, neutral otherwise.
     */
    public function test_chip_escalates_neutral_amber_red(): void
    {
        $bar = $this->resourceBar();

        $this->assertMatchesRegularExpression(
            "/tr >= 20\s*\?\s*'ap-chip--trust-pos'\s*:\s*tr < s\.trust_chip_red\s*\?\s*'ap-chip--trust-neg'\s*:\s*tr < 0\s*\?\s*'ap-chip--trust-warn'\s*:\s*'ap-chip--trust-neu'/",
            $bar
        );
        $this->assertStringContainsString('cls: `ap-chip ${mod}`', $bar);
    }
}
