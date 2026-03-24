<?php

namespace Tests\Feature\INNN;

use App\Models\InnnEvent;
use App\Services\EventService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Laravel port of INNN\Service\EventServiceTest.
 *
 * Test data (Simpsons fixture via TestSeeder):
 *   - event 16: user=3(Bart), tick=15405, event=techtree.level_up_finished
 *   - event 19: user=3(Bart), tick=15405, event=galaxy.trade
 *
 * Bart (user=3) has 2 events; Homer (user=0) has 0 events.
 */
class EventServiceTest extends TestCase
{
    use RefreshDatabase;

    private EventService $service;

    private int $userA   = 0;   // Homer — has no events
    private int $userB   = 3;   // Bart  — has 2 events
    private int $eventId = 16;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(EventService::class);
    }

    // ── getEvent ─────────────────────────────────────────────────────────────

    public function test_get_event_returns_event(): void
    {
        $event = $this->service->getEvent($this->eventId);
        $this->assertNotFalse($event);
        $this->assertInstanceOf(InnnEvent::class, $event);
        $this->assertEquals($this->eventId, $event->id);
    }

    public function test_get_event_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getEvent(99));
    }

    public function test_get_event_throws_for_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getEvent(null);
    }

    public function test_get_event_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getEvent(-1);
    }

    // ── getEvents ────────────────────────────────────────────────────────────

    public function test_get_events_returns_collection(): void
    {
        $events = $this->service->getEvents($this->userB);
        $this->assertInstanceOf(Collection::class, $events);
    }

    public function test_bart_has_two_events(): void
    {
        $events = $this->service->getEvents($this->userB);
        $this->assertEquals(2, $events->count());
    }

    public function test_homer_has_zero_events(): void
    {
        $events = $this->service->getEvents($this->userA);
        $this->assertEquals(0, $events->count());
    }

    public function test_get_events_throws_for_negative_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getEvents(-1);
    }

    public function test_get_events_returns_empty_collection_for_unknown_user(): void
    {
        $events = $this->service->getEvents(99);
        $this->assertInstanceOf(Collection::class, $events);
        $this->assertEquals(0, $events->count());
    }

    // ── createEvent ──────────────────────────────────────────────────────────

    public function test_create_event_returns_integer(): void
    {
        $id = $this->service->createEvent([
            'user'       => $this->userB,
            'tick'       => 16000,
            'event'      => 'test.event',
            'area'       => '',
            'parameters' => 'a:0:{}',
        ]);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function test_create_event_is_retrievable(): void
    {
        $id = $this->service->createEvent([
            'user'       => $this->userB,
            'tick'       => 16001,
            'event'      => 'test.created',
            'area'       => 'techtree',
            'parameters' => 'a:1:{s:5:"hello";s:5:"world";}',
        ]);

        $event = $this->service->getEvent($id);
        $this->assertNotFalse($event);
        $this->assertEquals('test.created', $event->event);
        $this->assertEquals($this->userB, $event->user);
    }

    public function test_create_event_increases_count_for_user(): void
    {
        $before = $this->service->getEvents($this->userB)->count();

        $this->service->createEvent([
            'user'       => $this->userB,
            'tick'       => 16002,
            'event'      => 'another.event',
            'area'       => '',
            'parameters' => '',
        ]);

        $after = $this->service->getEvents($this->userB)->count();
        $this->assertEquals($before + 1, $after);
    }
}
