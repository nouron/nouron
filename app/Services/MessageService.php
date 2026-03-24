<?php

namespace App\Services;

use App\Models\InnnMessage;
use App\Models\InnnMessageView;
use App\Services\Concerns\ValidatesId;
use Illuminate\Support\Collection;

/**
 * MessageService — Laravel port of INNN\Service\MessageService.
 *
 * Reads come from v_innn_messages (InnnMessageView) which includes sender and
 * recipient usernames via a SQL view join. Writes go directly to innn_messages
 * (InnnMessage). Status changes (read/archived/deleted) also target the table.
 *
 * Message status rules:
 *   - Only the recipient may archive, delete or mark a message as read.
 *   - Outbox shows sent messages that are not deleted and not archived.
 *   - Inbox shows received messages that are not deleted and not archived.
 *   - Archive shows received messages that are archived but not deleted.
 */
class MessageService
{
    use ValidatesId;

    /**
     * Fetch a single message from the view (includes sender/recipient names).
     *
     * @throws \InvalidArgumentException for non-numeric or negative $id
     */
    public function getMessage(mixed $id): InnnMessageView|false
    {
        $this->validateId($id);
        return InnnMessageView::find((int) $id) ?? false;
    }

    /**
     * Inbox: messages received by $userId, not deleted and not archived,
     * ordered newest-first by tick.
     *
     * @throws \InvalidArgumentException for invalid $userId
     */
    public function getInboxMessages(mixed $userId): Collection
    {
        $this->validateId($userId);
        return InnnMessageView::where('recipient_id', (int) $userId)
            ->where('is_deleted', 0)
            ->where('is_archived', 0)
            ->orderBy('tick', 'DESC')
            ->get();
    }

    /**
     * Outbox: messages sent by $userId, not deleted and not archived.
     *
     * @throws \InvalidArgumentException for invalid $userId
     */
    public function getOutboxMessages(mixed $userId): Collection
    {
        $this->validateId($userId);
        return InnnMessageView::where('sender_id', (int) $userId)
            ->where('is_deleted', 0)
            ->where('is_archived', 0)
            ->get();
    }

    /**
     * Archive: messages received by $userId that are archived and not deleted.
     *
     * @throws \InvalidArgumentException for invalid $userId
     */
    public function getArchivedMessages(mixed $userId): Collection
    {
        $this->validateId($userId);
        return InnnMessageView::where('recipient_id', (int) $userId)
            ->where('is_deleted', 0)
            ->where('is_archived', 1)
            ->get();
    }

    /**
     * Insert a new message into innn_messages.
     *
     * Expected keys in $data: sender_id, attitude, recipient_id, tick, type,
     * subject, text. Flags is_read/is_archived/is_deleted default to 0.
     *
     * @return int the new message ID
     * @throws \InvalidArgumentException for invalid sender_id or recipient_id
     */
    public function sendMessage(array $data): int
    {
        $this->validateId($data['sender_id']);
        $this->validateId($data['recipient_id']);

        $message = InnnMessage::create([
            'sender_id'    => (int) $data['sender_id'],
            'attitude'     => $data['attitude'] ?? 'mood_factual',
            'recipient_id' => (int) $data['recipient_id'],
            'tick'         => (int) ($data['tick'] ?? 0),
            'type'         => (int) ($data['type'] ?? 0),
            'subject'      => $data['subject'],
            'text'         => $data['text'],
            'is_read'      => 0,
            'is_archived'  => 0,
            'is_deleted'   => 0,
        ]);

        return $message->id;
    }

    /**
     * Update a message status flag.
     *
     * Accepted values for $status: 'read', 'archived', 'deleted'.
     * Returns false for any unknown status string.
     *
     * @throws \InvalidArgumentException for invalid $id
     */
    public function setMessageStatus(int $id, string $status): bool
    {
        $this->validateId($id);

        $column = match ($status) {
            'read'     => 'is_read',
            'archived' => 'is_archived',
            'deleted'  => 'is_deleted',
            default    => null,
        };

        if ($column === null) {
            return false;
        }

        $affected = InnnMessage::where('id', $id)->update([$column => 1]);

        return $affected > 0;
    }
}
