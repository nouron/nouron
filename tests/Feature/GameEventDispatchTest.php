<?php

namespace Tests\Feature;

use App\Events\JournalEventRecorded;
use App\Events\TrustEventFired;
use App\Services\EventService;
use App\Services\TrustService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * T38 stage 1: journal lines and trust events are announced as Laravel events
 * the moment they are recorded (observers only — the rows are written regardless).
 */
class GameEventDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
    }

    public function test_create_event_dispatches_journal_event_after_row_is_stored(): void
    {
        Event::fake([JournalEventRecorded::class]);

        $id = $this->app->make(EventService::class)->createEvent([
            'user' => 3,
            'tick' => 42,
            'event' => 'colony.building_completed',
            'area' => 'colony',
            'parameters' => '{"a":1}',
        ]);

        Event::assertDispatched(JournalEventRecorded::class, fn ($e) => $e->logId === $id
            && $e->userId === 3
            && $e->tick === 42
            && $e->event === 'colony.building_completed'
            && $e->area === 'colony'
            && $e->parameters === '{"a":1}');
        $this->assertDatabaseHas('colony_log', ['id' => $id]);
    }

    public function test_fire_event_dispatches_trust_event(): void
    {
        Event::fake([TrustEventFired::class]);

        $this->app->make(TrustService::class)->fireEvent(1, 'building_level_up', 77);

        Event::assertDispatched(TrustEventFired::class, fn ($e) => $e->colonyId === 1
            && $e->eventType === 'building_level_up'
            && $e->tick === 77);
        $this->assertDatabaseHas('trust_events', ['colony_id' => 1, 'tick' => 77, 'event_type' => 'building_level_up']);
    }

    public function test_unknown_trust_event_dispatches_nothing(): void
    {
        Event::fake([TrustEventFired::class]);

        $this->app->make(TrustService::class)->fireEvent(1, 'does_not_exist', 77);

        Event::assertNotDispatched(TrustEventFired::class);
    }
}
