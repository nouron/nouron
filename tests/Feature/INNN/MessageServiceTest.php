<?php

namespace Tests\Feature\INNN;

use App\Services\MessageService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Laravel port of INNN\Service\MessageServiceTest.
 *
 * Test data (Simpsons fixture via TestSeeder):
 *   - message 22: sender=3(Bart), recipient=0(Homer), all flags=0
 *   - message 23: sender=3(Bart), recipient=0(Homer), all flags=0
 *   - message 30: sender=0(Homer), recipient=3(Bart), all flags=0
 *   - message 31: sender=0(Homer), recipient=3(Bart), all flags=0
 *   - message 32: sender=0(Homer), recipient=3(Bart), all flags=0
 *   - message 33: sender=0(Homer), recipient=3(Bart), is_archived=1, is_read=1
 *
 * Homer's inbox  (recipient=0, not deleted/archived): 2 messages (22, 23)
 * Homer's outbox (sender=0, not deleted/archived):    3 messages (30, 31, 32)
 * Bart's archive (recipient=3, archived, not deleted): 1 message (33)
 */
class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $service;

    private int $userA     = 0;   // Homer
    private int $userB     = 3;   // Bart
    private int $messageId = 22;  // Bart → Homer

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(MessageService::class);
    }

    // ── getMessage ───────────────────────────────────────────────────────────

    public function test_get_message_returns_message_view(): void
    {
        $message = $this->service->getMessage($this->messageId);
        $this->assertNotFalse($message);
        $this->assertEquals($this->messageId, $message->id);
    }

    public function test_get_message_includes_sender_and_recipient_names(): void
    {
        $message = $this->service->getMessage($this->messageId);
        $this->assertNotNull($message->sender);
        $this->assertNotNull($message->recipient);
    }

    public function test_get_message_returns_false_for_missing_id(): void
    {
        $this->assertFalse($this->service->getMessage(99));
    }

    public function test_get_message_throws_for_null(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getMessage(null);
    }

    public function test_get_message_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getMessage(-1);
    }

    // ── getInboxMessages ─────────────────────────────────────────────────────

    public function test_get_inbox_messages_returns_collection(): void
    {
        $messages = $this->service->getInboxMessages($this->userA);
        $this->assertInstanceOf(Collection::class, $messages);
    }

    public function test_homer_inbox_has_two_messages(): void
    {
        $messages = $this->service->getInboxMessages($this->userA);
        $this->assertEquals(2, $messages->count());
    }

    public function test_inbox_messages_are_ordered_by_tick_desc(): void
    {
        $messages = $this->service->getInboxMessages($this->userA);
        if ($messages->count() > 1) {
            $ticks = $messages->pluck('tick')->toArray();
            $this->assertEquals($ticks, collect($ticks)->sortDesc()->values()->toArray());
        }
        $this->assertTrue(true); // single-message inbox always passes ordering
    }

    public function test_inbox_excludes_archived_and_deleted(): void
    {
        // Message 33 to Bart is archived — must not appear in Bart's inbox
        $messages = $this->service->getInboxMessages($this->userB);
        $ids = $messages->pluck('id')->toArray();
        $this->assertNotContains(33, $ids);
    }

    public function test_inbox_throws_for_negative_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getInboxMessages(-1);
    }

    // ── getOutboxMessages ────────────────────────────────────────────────────

    public function test_get_outbox_messages_returns_collection(): void
    {
        $messages = $this->service->getOutboxMessages($this->userA);
        $this->assertInstanceOf(Collection::class, $messages);
    }

    public function test_homer_outbox_has_three_messages(): void
    {
        $messages = $this->service->getOutboxMessages($this->userA);
        $this->assertEquals(3, $messages->count());
    }

    public function test_outbox_excludes_archived_message(): void
    {
        // Message 33 is archived — even though it is sent by Homer it won't appear
        // in the outbox (sender=0, but archived), according to is_archived=0 filter
        $messages = $this->service->getOutboxMessages($this->userA);
        $ids = $messages->pluck('id')->toArray();
        $this->assertNotContains(33, $ids);
    }

    public function test_outbox_throws_for_negative_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getOutboxMessages(-1);
    }

    // ── getArchivedMessages ──────────────────────────────────────────────────

    public function test_get_archived_messages_returns_collection(): void
    {
        $messages = $this->service->getArchivedMessages($this->userB);
        $this->assertInstanceOf(Collection::class, $messages);
    }

    public function test_bart_archive_has_one_message(): void
    {
        $messages = $this->service->getArchivedMessages($this->userB);
        $this->assertEquals(1, $messages->count());
        $this->assertEquals(33, $messages->first()->id);
    }

    public function test_homer_archive_is_empty(): void
    {
        $messages = $this->service->getArchivedMessages($this->userA);
        $this->assertEquals(0, $messages->count());
    }

    public function test_archive_throws_for_negative_user_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->getArchivedMessages(-1);
    }

    // ── sendMessage ──────────────────────────────────────────────────────────

    public function test_send_message_returns_new_id(): void
    {
        $id = $this->service->sendMessage([
            'sender_id'    => $this->userA,
            'attitude'     => 'mood_factual',
            'recipient_id' => $this->userB,
            'tick'         => 17000,
            'type'         => 0,
            'subject'      => 'Test',
            'text'         => 'Hello Bart',
        ]);
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }

    public function test_send_message_increases_outbox_count(): void
    {
        $before = $this->service->getOutboxMessages($this->userA)->count();

        $this->service->sendMessage([
            'sender_id'    => $this->userA,
            'attitude'     => 'mood_factual',
            'recipient_id' => $this->userB,
            'tick'         => 17000,
            'type'         => 0,
            'subject'      => 'More',
            'text'         => 'Another message',
        ]);

        $after = $this->service->getOutboxMessages($this->userA)->count();
        $this->assertEquals($before + 1, $after);
    }

    public function test_send_message_increases_inbox_count_for_recipient(): void
    {
        $before = $this->service->getInboxMessages($this->userB)->count();

        $this->service->sendMessage([
            'sender_id'    => $this->userA,
            'attitude'     => 'mood_factual',
            'recipient_id' => $this->userB,
            'tick'         => 17000,
            'type'         => 0,
            'subject'      => 'Hi Bart',
            'text'         => 'From Homer',
        ]);

        $after = $this->service->getInboxMessages($this->userB)->count();
        $this->assertEquals($before + 1, $after);
    }

    public function test_send_message_throws_for_invalid_sender(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->sendMessage([
            'sender_id'    => -1,
            'attitude'     => 'mood_factual',
            'recipient_id' => $this->userB,
            'tick'         => 17000,
            'type'         => 0,
            'subject'      => 'x',
            'text'         => 'x',
        ]);
    }

    // ── setMessageStatus ─────────────────────────────────────────────────────

    public function test_set_message_status_read(): void
    {
        $result = $this->service->setMessageStatus($this->messageId, 'read');
        $this->assertTrue($result);

        $msg = $this->service->getMessage($this->messageId);
        $this->assertEquals(1, $msg->is_read);
    }

    public function test_set_message_status_archived(): void
    {
        $result = $this->service->setMessageStatus($this->messageId, 'archived');
        $this->assertTrue($result);

        // After archiving, message 22 should no longer appear in Homer's inbox
        $inbox = $this->service->getInboxMessages($this->userA);
        $this->assertNotContains($this->messageId, $inbox->pluck('id')->toArray());
    }

    public function test_set_message_status_deleted(): void
    {
        $result = $this->service->setMessageStatus($this->messageId, 'deleted');
        $this->assertTrue($result);

        // After deletion the message must not appear in inbox or archive
        $inbox   = $this->service->getInboxMessages($this->userA);
        $archive = $this->service->getArchivedMessages($this->userA);
        $this->assertNotContains($this->messageId, $inbox->pluck('id')->toArray());
        $this->assertNotContains($this->messageId, $archive->pluck('id')->toArray());
    }

    public function test_set_message_status_unknown_returns_false(): void
    {
        $result = $this->service->setMessageStatus($this->messageId, 'unknown');
        $this->assertFalse($result);
    }

    public function test_set_message_status_throws_for_negative_id(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->setMessageStatus(-1, 'read');
    }
}
