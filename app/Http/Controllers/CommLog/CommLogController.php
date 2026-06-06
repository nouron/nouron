<?php

namespace App\Http\Controllers\CommLog;

use App\Http\Controllers\BaseController;
use App\Models\ColonyLog;
use App\Services\EventService;
use App\Services\TickService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommLogController extends BaseController
{
    private const NEXUS_EVENT_KEYS = [
        'run.nexus_warning_sol30',
        'run.nexus_warning_sol50',
        'run.nexus_sanction_sol65',
        'run.nexus_countdown_sol80',
        'run.run_completed',
        'run.run_failed_trust',
        'run.run_failed_nexus_debt',
        'run.run_failed_time',
        'run.phase1_complete',
    ];

    private const EXCLUDED_FROM_LOG = [
        'run.sol_advanced',
    ];

    public function __construct(
        TickService $tick,
        private readonly EventService $eventService,
    ) {
        parent::__construct($tick);
    }

    public function log(): View
    {
        $userId = Auth::id();
        $nexusKeys = self::NEXUS_EVENT_KEYS;
        $excluded  = self::EXCLUDED_FROM_LOG;

        $entries = ColonyLog::where('user', $userId)
            ->where('area', '!=', 'nexus')
            ->whereNotIn('event', $nexusKeys)
            ->whereNotIn('event', $excluded)
            ->where('event', 'not like', 'onboarding%')
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->get()
            ->map(fn($e) => $this->decorate($e));

        $unreadCount = $this->eventService->countUnreadNexus($userId);

        return view('comm_log.index', [
            'tab'         => 'log',
            'entries'     => $entries,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function nexus(): View
    {
        $userId    = Auth::id();
        $nexusKeys = self::NEXUS_EVENT_KEYS;

        $entries = ColonyLog::where('user', $userId)
            ->where(function ($q) use ($nexusKeys) {
                $q->where('area', 'nexus')
                  ->orWhereIn('event', $nexusKeys);
            })
            ->orderByDesc('tick')
            ->orderByDesc('id')
            ->get()
            ->map(fn($e) => $this->decorate($e));

        $this->eventService->markNexusRead($userId);

        return view('comm_log.index', [
            'tab'         => 'nexus',
            'entries'     => $entries,
            'unreadCount' => 0,
        ]);
    }

    private function decorate(ColonyLog $entry): array
    {
        $params = [];
        if ($entry->parameters && $entry->parameters !== '') {
            $decoded = json_decode($entry->parameters, true);
            if (is_array($decoded)) {
                $params = $decoded;
            }
        }

        return [
            'id'     => $entry->id,
            'sol'    => $entry->tick,
            'event'  => $entry->event,
            'area'   => $entry->area,
            'params' => $params,
        ];
    }
}
