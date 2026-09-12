<?php

namespace Tests\Feature\Playtest;

use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\TrustService;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates one bot run into the JSON artifact + STDERR summary table
 * described in the plan. Read-only: everything here is observation, not
 * game state — same as BotStrategy's DB reads.
 */
class RunReport
{
    /** @var array<int, array> */
    private array $sols = [];

    private ?int $phase2StartSol = null;

    /**
     * A30/B1a: maps a BotSession::act() rule name to its AP-spend category.
     * Unmapped rules (hire_advisor, accept_bar_offer, buy_corporate_harvester_offer,
     * sol_next) cost Credits or nothing, not AP — deliberately excluded rather
     * than lumped into a catch-all "other" bucket the plan doesn't ask for.
     */
    private const AP_CATEGORY_MAP = [
        'repair_critical' => 'repair',
        'invest_cc' => 'project',
        'invest_production' => 'project',
        'place_building' => 'project',
        'place_harvester_instance2' => 'project',
        'relocate_harvester' => 'project',
        'research_knowledge' => 'project',
        'explore_tile' => 'action',
        'deep_scan_signal_tile' => 'action',
        'dispatch_mission' => 'action',
        'dispatch_salvage_mission' => 'action',
        'request_ship' => 'action',
    ];

    public function __construct(private readonly int $seed, private readonly string $profile = 'default') {}

    /**
     * Sums ap_before-ap_after for this Sol's log entries, grouped by
     * AP_CATEGORY_MAP. A negative or zero delta (rejected action, DB
     * untouched) never counts — only real spend does.
     *
     * @return array{repair: int, project: int, action: int}
     */
    private function apSpendByCategory(BotSession $bot): array
    {
        $spent = ['repair' => 0, 'project' => 0, 'action' => 0];

        foreach ($bot->log as $entry) {
            if ($entry['sol'] !== $bot->sol) {
                continue;
            }

            $category = self::AP_CATEGORY_MAP[$entry['rule']] ?? null;
            if ($category === null) {
                continue;
            }

            $delta = ($entry['ap_before'] ?? 0) - ($entry['ap_after'] ?? 0);
            if ($delta > 0) {
                $spent[$category] += $delta;
            }
        }

        return $spent;
    }

    /**
     * Capture one Sol's state — call once per Sol, after the strategy has
     * acted but BEFORE nextSol() (locked_actionpoints still reflect that Sol).
     */
    public function snapshot(BotSession $bot): void
    {
        $colonyId = $bot->colonyId;
        $ccLevel = BotStrategy::ccLevel($bot);

        // Note: AP pool is now unified (GDD §13.1) — no longer separate by type
        $apAvailable = app(AdvisorService::class)->getAvailableActionPoints($colonyId);
        // "inflow" = the colony's total AP pool this tick (base + advisors,
        // trust/plague multipliers applied) — what generatePassiveCredits()'s
        // AP equivalent produced, before this Sol's spend. Distinct from
        // $apAvailable, which is what's LEFT after spending (kept below as
        // 'total'/'ap_unspent' for backward compat with existing consumers).
        $apInflow = app(AdvisorService::class)->getApBreakdown($colonyId)['total'];
        $apSpent = $this->apSpendByCategory($bot);

        $phase = (int) (DB::table('runs')->where('id', $bot->runId)->value('phase') ?? 1);
        if ($phase >= 2 && $this->phase2StartSol === null) {
            $this->phase2StartSol = $bot->sol;
        }

        $this->sols[] = [
            'sol' => $bot->sol,
            'trust' => app(TrustService::class)->getTrust($colonyId),
            'credits' => BotStrategy::credits($bot),
            'regolith' => BotStrategy::regolith($bot),
            'organics' => BotStrategy::organics($bot),
            'ap' => [
                'total' => $apAvailable,
                'inflow' => $apInflow,
                'repair_spent' => $apSpent['repair'],
                'project_spent' => $apSpent['project'],
                'action_spent' => $apSpent['action'],
                'unspent' => $apAvailable,
            ],
            'ap_unspent' => $apAvailable,
            'cc_level' => $ccLevel,
            'advisors' => DB::table('advisors')->where('colony_id', $colonyId)->count(),
        ];
    }

    /**
     * @return array{seed:int, profile:string, outcome:array, phase2_start_sol:?int, objectives:array,
     *               actions:array, rejections:array, burnout:array, sols:array, log:array}
     */
    public function build(BotSession $bot): array
    {
        $run = Run::findOrFail($bot->runId);

        $ok = 0;
        $rejections = [];
        foreach ($bot->log as $entry) {
            if ($entry['ok']) {
                $ok++;
            } else {
                $rejections[$entry['error']] = ($rejections[$entry['error']] ?? 0) + 1;
            }
        }
        $rejected = count($bot->log) - $ok;

        $objectives = $run->objectives->map(fn ($o) => [
            'task_key' => $o->task_key,
            'current' => (int) $o->current_value,
            'target' => (int) $o->target_value,
            'completed_at' => $o->completed_at,
        ])->values()->all();

        $advisorActiveTicks = DB::table('advisors')
            ->where('colony_id', $bot->colonyId)
            ->pluck('active_ticks')
            ->map(fn ($v) => (int) $v)
            ->all();

        $observedLockouts = DB::table('advisors')
            ->where('colony_id', $bot->colonyId)
            ->whereNotNull('unavailable_until_tick')
            ->count();

        return [
            'seed' => $this->seed,
            'profile' => $this->profile,
            'outcome' => [
                'status' => $run->status,
                'fail_reason' => $run->fail_reason,
                'sols' => $bot->sol,
                'score' => (int) ($run->score ?? 0),
            ],
            'phase2_start_sol' => $this->phase2StartSol,
            'objectives' => $objectives,
            'actions' => [
                'attempted' => count($bot->log),
                'ok' => $ok,
                'rejected' => $rejected,
            ],
            'rejections' => $rejections,
            // config('game.advisors.burnout') doesn't exist yet (GDD: formula follows
            // the first playtest) — this reports the raw material for that formula,
            // not a guess at it.
            'burnout' => [
                'implemented' => false,
                'observed_lockouts' => $observedLockouts,
                'advisor_active_ticks' => $advisorActiveTicks,
            ],
            'sols' => $this->sols,
            // Raw per-action log (sol/rule/ok/error) — dashboard event markers
            // read this directly, kept unaggregated unlike 'rejections' above.
            'log' => $bot->log,
        ];
    }

    public function write(array $report): string
    {
        $dir = storage_path('logs/playtest');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$this->profile}-{$this->seed}-".now()->format('Ymd_His').'.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT));

        return $path;
    }

    public function printTable(array $report): void
    {
        fwrite(STDERR, sprintf(
            "\n[playtest] seed=%d profile=%s status=%s fail_reason=%s sols=%d phase2_start_sol=%s score=%d actions=%d/%d rejected=%d\n",
            $report['seed'],
            $report['profile'],
            $report['outcome']['status'],
            $report['outcome']['fail_reason'] ?? '-',
            $report['outcome']['sols'],
            $report['phase2_start_sol'] ?? '-',
            $report['outcome']['score'],
            $report['actions']['ok'],
            $report['actions']['attempted'],
            $report['actions']['rejected'],
        ));

        if (! empty($report['rejections'])) {
            fwrite(STDERR, '[playtest] rejections: '.json_encode($report['rejections'])."\n");
        }
    }
}
