<?php

namespace Tests\Feature\Colony;

use App\Models\User;
use App\Services\TickService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Kolonisten-Zulage (GDD §14) — spend Credits for a one-shot Trust event.
 *
 * Rules under test:
 *  - Each tier spends the configured Credits and fires the configured trust_events
 *    row at tick (current+1) — the tick TrustService::calculateAndStore() will read
 *    on the *next* Sol, not the current one.
 *  - Only one stipend tier may be purchased per colony per Sol — a second purchase
 *    in the same tick is rejected, regardless of which tier was already used.
 *  - Insufficient Credits rejects with no DB writes.
 *  - The guard is per-tick only — a new Sol allows another purchase.
 *
 * Fixture: Colony 1 (Springfield), user_id=3 (Bart), fixed tick 100.
 */
class StipendTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const BART_USER_ID = 3;

    private TickService $tickService;

    private int $tick = 100;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();

        $this->tickService = $this->app->make(TickService::class);
        $this->tickService->setTickCount($this->tick);

        DB::table('user_resources')->where('user_id', self::BART_USER_ID)->update(['credits' => 5000]);
    }

    private function bart(): User
    {
        return User::where('user_id', self::BART_USER_ID)->firstOrFail();
    }

    private function credits(): int
    {
        return (int) DB::table('user_resources')->where('user_id', self::BART_USER_ID)->value('credits');
    }

    private function stipendEventCount(int $tick): int
    {
        return DB::table('trust_events')
            ->where('colony_id', self::COLONY_ID)
            ->where('tick', $tick)
            ->whereIn('event_type', ['stipend_small', 'stipend_medium', 'stipend_large'])
            ->count();
    }

    private function purchase(string $tier)
    {
        return $this->actingAs($this->bart())->postJson(route('colony.stipend'), ['tier' => $tier]);
    }

    public function test_stipend_small_spends_credits_and_fires_trust_event(): void
    {
        $credits = $this->credits();

        $this->purchase('small')->assertOk()->assertJsonPath('ok', true)->assertJsonPath('cost', 100);

        $this->assertSame($credits - 100, $this->credits());
        $this->assertDatabaseHas('trust_events', [
            'colony_id' => self::COLONY_ID,
            'tick' => $this->tick + 1,
            'event_type' => 'stipend_small',
        ]);
    }

    public function test_stipend_medium_and_large_use_correct_cost_and_event_key(): void
    {
        $this->purchase('medium')->assertOk()->assertJsonPath('ok', true)->assertJsonPath('cost', 300);
        $this->assertDatabaseHas('trust_events', ['colony_id' => self::COLONY_ID, 'tick' => $this->tick + 1, 'event_type' => 'stipend_medium']);

        // New Sol so "large" isn't blocked by the same-tick guard.
        $this->tickService->setTickCount($this->tick + 1);

        $this->purchase('large')->assertOk()->assertJsonPath('ok', true)->assertJsonPath('cost', 600);
        $this->assertDatabaseHas('trust_events', ['colony_id' => self::COLONY_ID, 'tick' => $this->tick + 2, 'event_type' => 'stipend_large']);
    }

    public function test_stipend_second_tier_same_tick_is_rejected(): void
    {
        $this->purchase('small')->assertOk()->assertJsonPath('ok', true);
        $credits = $this->credits();

        $this->purchase('large')->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'stipend_already_used');

        $this->assertSame($credits, $this->credits(), 'no Credits deducted on a rejected second purchase');
        $this->assertSame(1, $this->stipendEventCount($this->tick + 1), 'only the first stipend event was recorded');
    }

    public function test_stipend_rejects_insufficient_credits(): void
    {
        DB::table('user_resources')->where('user_id', self::BART_USER_ID)->update(['credits' => 50]);

        $this->purchase('small')->assertStatus(422)->assertJsonPath('ok', false)->assertJsonPath('error', 'stipend_no_credits');

        $this->assertSame(50, $this->credits());
        $this->assertSame(0, $this->stipendEventCount($this->tick + 1));
    }

    public function test_stipend_invalid_tier_returns_validation_error(): void
    {
        $this->purchase('huge')->assertStatus(422);
    }

    public function test_stipend_allowed_again_next_tick(): void
    {
        $this->purchase('small')->assertOk()->assertJsonPath('ok', true);

        $this->tickService->setTickCount($this->tick + 1);

        $this->purchase('small')->assertOk()->assertJsonPath('ok', true);
        $this->assertSame(1, $this->stipendEventCount($this->tick + 1));
        $this->assertSame(1, $this->stipendEventCount($this->tick + 2));
    }
}
