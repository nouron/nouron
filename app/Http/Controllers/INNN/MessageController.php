<?php

namespace App\Http\Controllers\INNN;

use App\Http\Controllers\BaseController;
use App\Services\EventService;
use App\Services\MessageService;
use App\Services\TickService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * MessageController — Laravel port of INNN\Controller\MessageController.
 *
 * Handles player-to-player messages (inbox, outbox, archive, compose, send)
 * and the game events tab. All routes require authentication.
 *
 * Routes (defined in routes/web.php under prefix /messages):
 *   GET  /           → inbox()          — received, non-archived, non-deleted
 *   GET  /outbox     → outbox()         — sent, non-archived, non-deleted
 *   GET  /archive    → showArchive()    — received and archived, non-deleted
 *   GET  /new        → compose()        — compose form
 *   POST /send       → send()           — persist and redirect
 *   POST /react      → react()          — mark as read (JSON)
 *   POST /archive/{id} → archiveMessage() — mark archived (JSON)
 *   POST /remove/{id}  → remove()         — mark deleted (JSON)
 */
class MessageController extends BaseController
{
    public function __construct(
        TickService $tick,
        private MessageService $messageService,
        private EventService   $eventService,
    ) {
        parent::__construct($tick);
    }

    // ── Views ────────────────────────────────────────────────────────────────

    /**
     * Inbox: shows messages received by the current user that are not
     * deleted and not archived, ordered by tick descending.
     */
    public function inbox(): View
    {
        $userId   = $this->getCurrentUserId();
        $messages = $userId !== null
            ? $this->messageService->getInboxMessages($userId)
            : collect();

        return view('messages.inbox', compact('messages'));
    }

    /**
     * Outbox: shows messages sent by the current user that are not deleted
     * and not archived.
     */
    public function outbox(): View
    {
        $userId   = $this->getCurrentUserId();
        $messages = $userId !== null
            ? $this->messageService->getOutboxMessages($userId)
            : collect();

        return view('messages.outbox', compact('messages'));
    }

    /**
     * Archive: shows received messages that are archived but not deleted.
     */
    public function showArchive(): View
    {
        $userId   = $this->getCurrentUserId();
        $messages = $userId !== null
            ? $this->messageService->getArchivedMessages($userId)
            : collect();

        return view('messages.archive', compact('messages'));
    }

    /**
     * Events tab: game-generated notifications for the current user.
     */
    public function events(): View
    {
        $userId = $this->getCurrentUserId();
        $events = $userId !== null
            ? $this->eventService->getEvents($userId)
            : collect();

        return view('messages.events', compact('events'));
    }

    /**
     * Compose form — GET only.
     */
    public function compose(): View
    {
        return view('messages.compose');
    }

    // ── POST Actions ─────────────────────────────────────────────────────────

    /**
     * Send a new message. Validates recipient, sender, subject and text then
     * delegates to MessageService::sendMessage().
     */
    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', 'min:0'],
            'attitude'     => ['required', 'string'],
            'subject'      => ['required', 'string', 'max:255'],
            'text'         => ['required', 'string'],
        ]);

        $data['sender_id'] = $this->getCurrentUserId();
        $data['tick']      = $this->getTick();
        $data['type']      = 0;

        $this->messageService->sendMessage($data);

        return redirect()->route('messages.inbox')
            ->with('success', 'Nachricht gesendet.');
    }

    /**
     * React (mark as read) — POST, returns JSON.
     * Accepts ?id= as query string or request body.
     */
    public function react(Request $request): JsonResponse
    {
        $id     = $request->input('id');
        $result = false;

        if ($id !== null) {
            $result = $this->messageService->setMessageStatus((int) $id, 'read');
        }

        return response()->json(['result' => $result, 'status' => 'read']);
    }

    /**
     * Archive a message by ID — POST /archive/{id}, returns JSON.
     * Only the recipient may archive a message.
     */
    public function archiveMessage(int $id): JsonResponse
    {
        $result = $this->messageService->setMessageStatus($id, 'archived');
        return response()->json(['result' => $result, 'status' => 'archived']);
    }

    /**
     * Mark a message as deleted — POST /remove/{id}, returns JSON.
     */
    public function remove(int $id): JsonResponse
    {
        $result = $this->messageService->setMessageStatus($id, 'deleted');
        return response()->json(['result' => $result, 'status' => 'deleted']);
    }
}
