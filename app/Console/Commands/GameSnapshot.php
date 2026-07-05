<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

/**
 * GameSnapshot — dev tool to save/restore a player's actual live run state.
 *
 * Solves the "reset-player forces a restart from Sol 1" problem: unlike
 * game:reset-player (which seeds one of 5 hand-crafted scenarios), this
 * captures/restores whatever state a real playtest run is actually in — so
 * fixing a blocker found at e.g. Sol 47 doesn't require re-clicking from
 * Sol 1 to get back there.
 *
 * Usage:
 *   php artisan game:snapshot save bart pre-blocker
 *   php artisan game:snapshot restore bart pre-blocker
 *   php artisan game:snapshot list bart
 *
 * Storage: storage/app/snapshots/{user_id}/{label}.json (local disk, dev-only —
 * not part of the game's DB schema, nothing to migrate).
 */
class GameSnapshot extends Command
{
    protected $signature = 'game:snapshot
        {action : save|restore|list}
        {user? : Username or user_id (omit for interactive select)}
        {label? : Snapshot label (omit for interactive select on restore/list, timestamp on save)}
        {--yes : Skip confirmation prompts}';

    protected $description = 'Save/restore a player\'s live run state (dev tool) — resume a playtest after fixing a blocker instead of restarting from Sol 1.';

    /**
     * Tables scoped by colony_id, in FK-safe delete order (children before
     * glx_colonies) — mirrors ResetPlayer's wipe list.
     */
    private const COLONY_SCOPED_TABLES = [
        'colony_resources', 'colony_buildings', 'colony_tiles', 'advisors',
        'colony_ships', 'colony_researches', 'colony_personell',
        'trade_resources', 'trust_events', 'merchant_visits', 'colony_hangar_missions',
    ];

    public function handle(): int
    {
        $action = $this->argument('action');
        if (! in_array($action, ['save', 'restore', 'list'], true)) {
            $this->error("Unknown action: {$action} (expected save|restore|list)");

            return self::FAILURE;
        }

        $user = $this->resolveUser();
        if ($user === null) {
            return self::FAILURE;
        }

        return match ($action) {
            'save' => $this->save($user),
            'restore' => $this->restore($user),
            'list' => $this->list($user),
        };
    }

    // ── User resolution (same lookup as ResetPlayer) ─────────────────────────

    private function resolveUser(): ?User
    {
        $input = $this->argument('user');

        if ($input !== null) {
            $user = is_numeric($input)
                ? User::find((int) $input)
                : User::whereRaw('LOWER(username) = LOWER(?)', [$input])->first();

            if (! $user) {
                $this->error("User not found: {$input}");
            }

            return $user;
        }

        $rows = DB::table('user')->select('user_id', 'username')->orderBy('username')->get();
        if ($rows->isEmpty()) {
            $this->error('No users in database.');

            return null;
        }

        $chosen = select(label: 'Spieler', options: $rows->pluck('username', 'user_id')->toArray());

        return User::find((int) $chosen);
    }

    // ── save ──────────────────────────────────────────────────────────────────

    private function save(User $user): int
    {
        $label = $this->argument('label') ?? now()->format('Y-m-d_His');

        $colonyIds = DB::table('glx_colonies')->where('user_id', $user->user_id)->pluck('id');
        $runIds = DB::table('runs')->where('user_id', $user->user_id)->pluck('id');

        $path = $this->snapshotPath($user->user_id, $label);
        if (Storage::exists($path) && ! $this->option('yes')) {
            if (! confirm("Snapshot '{$label}' existiert bereits — überschreiben?", default: false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        $data = [
            'colony_ids' => $colonyIds->all(),
            'run_ids' => $runIds->all(),
            'tables' => [],
        ];

        foreach (self::COLONY_SCOPED_TABLES as $table) {
            $data['tables'][$table] = DB::table($table)->whereIn('colony_id', $colonyIds)->get()->toArray();
        }
        $data['tables']['run_objectives'] = DB::table('run_objectives')->whereIn('run_id', $runIds)->get()->toArray();
        $data['tables']['runs'] = DB::table('runs')->where('user_id', $user->user_id)->get()->toArray();
        $data['tables']['glx_colonies'] = DB::table('glx_colonies')->where('user_id', $user->user_id)->get()->toArray();
        $data['tables']['user_resources'] = DB::table('user_resources')->where('user_id', $user->user_id)->get()->toArray();
        $data['tables']['user_preferences'] = DB::table('user_preferences')->where('user_id', $user->user_id)->get()->toArray();
        $data['tables']['colony_log'] = DB::table('colony_log')->where('user', $user->user_id)->get()->toArray();

        $run = DB::table('runs')->where('user_id', $user->user_id)->where('status', 'active')->first();
        $data['meta'] = [
            'user_id' => $user->user_id,
            'username' => $user->username,
            'created_at' => now()->toDateTimeString(),
            'current_tick' => $run->current_tick ?? null,
            'phase' => $run->phase ?? null,
            'run_status' => $run->status ?? null,
        ];

        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Snapshot '{$label}' gespeichert (Sol {$data['meta']['current_tick']}, Phase {$data['meta']['phase']}).");

        return self::SUCCESS;
    }

    // ── restore ───────────────────────────────────────────────────────────────

    private function restore(User $user): int
    {
        $label = $this->argument('label') ?? $this->selectLabel($user->user_id);
        if ($label === null) {
            return self::FAILURE;
        }

        $path = $this->snapshotPath($user->user_id, $label);
        if (! Storage::exists($path)) {
            $this->error("Snapshot not found: {$label}");

            return self::FAILURE;
        }

        $data = json_decode(Storage::get($path), true);

        if (! $this->option('yes')) {
            $tick = $data['meta']['current_tick'] ?? '?';
            if (! confirm("Aktuellen Spielstand von '{$user->username}' durch Snapshot '{$label}' (Sol {$tick}) ersetzen?", default: false)) {
                $this->line('Aborted.');

                return self::SUCCESS;
            }
        }

        DB::transaction(function () use ($user, $data) {
            $colonyIds = DB::table('glx_colonies')->where('user_id', $user->user_id)->pluck('id');
            $runIds = DB::table('runs')->where('user_id', $user->user_id)->pluck('id');

            // Wipe current state — same table set/order as ResetPlayer's reset.
            foreach ($colonyIds as $cid) {
                foreach (self::COLONY_SCOPED_TABLES as $table) {
                    DB::table($table)->where('colony_id', $cid)->delete();
                }
                DB::table('locked_actionpoints')->where('scope_type', 'colony')->where('scope_id', $cid)->delete();
            }
            DB::table('run_objectives')->whereIn('run_id', $runIds)->delete();
            DB::table('runs')->where('user_id', $user->user_id)->delete();
            DB::table('glx_colonies')->where('user_id', $user->user_id)->delete();
            DB::table('user_resources')->where('user_id', $user->user_id)->delete();
            DB::table('user_preferences')->where('user_id', $user->user_id)->delete();
            DB::table('colony_log')->where('user', $user->user_id)->delete();

            // Re-insert snapshot — parents before children.
            $this->insertRows('glx_colonies', $data['tables']['glx_colonies']);
            $this->insertRows('runs', $data['tables']['runs']);
            foreach ($data['tables'] as $table => $rows) {
                if (in_array($table, ['glx_colonies', 'runs'], true)) {
                    continue;
                }
                $this->insertRows($table, $rows);
            }
        });

        $tick = $data['meta']['current_tick'] ?? '?';
        $this->info("Snapshot '{$label}' wiederhergestellt (Sol {$tick}).");

        return self::SUCCESS;
    }

    private function insertRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }
        // Chunk to stay well under SQLite's default bound-parameter limit.
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    // ── list ──────────────────────────────────────────────────────────────────

    private function list(User $user): int
    {
        $labels = $this->labelsFor($user->user_id);
        if ($labels === []) {
            $this->line("Keine Snapshots für '{$user->username}'.");

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($labels as $label) {
            $meta = json_decode(Storage::get($this->snapshotPath($user->user_id, $label)), true)['meta'] ?? [];
            $rows[] = [$label, $meta['current_tick'] ?? '?', $meta['phase'] ?? '?', $meta['created_at'] ?? '?'];
        }

        table(['Label', 'Sol', 'Phase', 'Gespeichert am'], $rows);

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function snapshotPath(int $userId, string $label): string
    {
        return "snapshots/{$userId}/{$label}.json";
    }

    /** @return string[] */
    private function labelsFor(int $userId): array
    {
        $dir = "snapshots/{$userId}";
        if (! Storage::exists($dir)) {
            return [];
        }

        return collect(Storage::files($dir))
            ->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all();
    }

    private function selectLabel(int $userId): ?string
    {
        $labels = $this->labelsFor($userId);
        if ($labels === []) {
            $this->error('No snapshots available.');

            return null;
        }

        return select(label: 'Snapshot', options: array_combine($labels, $labels));
    }
}
