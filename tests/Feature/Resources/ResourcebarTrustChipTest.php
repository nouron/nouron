<?php

namespace Tests\Feature\Resources;

use App\Models\User;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * A6 (GDD §18.2): the trust chip escalates. T59: the shell delivers the band, the
 * chip shows band word + AP effect and takes its colour from the band.
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

    public function test_shell_delivers_trust_band_and_ap_instead_of_red_threshold(): void
    {
        // T59: the chip colour follows the band; chip_red is gone.
        foreach ([10 => ['riot', -2], 30 => ['restless', -1], 50 => ['stable', 0], 70 => ['content', 1], 90 => ['euphoric', 2]] as $trust => [$band, $ap]) {
            $shell = $this->shellForTrust($trust);
            $this->assertSame($band, $shell['trust']['band']);
            $this->assertSame($ap, $shell['trust']['ap']);
            $this->assertArrayNotHasKey('trust_chip_red', $shell);
        }
    }
}
