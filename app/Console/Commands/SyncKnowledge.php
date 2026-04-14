<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * SyncKnowledge — synchronises config/ships.php, config/buildings.php and config/knowledge.php to the DB.
 *
 * Run:            php artisan game:sync-knowledge
 * Preview only:   php artisan game:sync-knowledge --dry-run
 *
 * Synced fields:
 *   ships     → moving_speed, decay_rate, supply_cost, max_status_points
 *   buildings → decay_rate, supply_cost, max_status_points, max_level
 *   knowledge → decay_rate, max_status_points
 *
 * Only rows that actually differ are updated (safe to run repeatedly).
 */
class SyncKnowledge extends Command
{
    protected $signature   = 'game:sync-knowledge {--dry-run : Preview changes without writing to DB}';
    protected $description = 'Sync config/ships.php, config/buildings.php and config/knowledge.php values to the database';

    public function handle(): int
    {
        $dry = $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY RUN — no changes will be written.');
        }

        $shipChanges      = $this->syncShips($dry);
        $buildingChanges  = $this->syncBuildings($dry);
        $knowledgeChanges = $this->syncKnowledge($dry);

        $total = $shipChanges + $buildingChanges + $knowledgeChanges;

        if ($total === 0) {
            $this->info('Everything is already in sync — no changes needed.');
        } else {
            $verb = $dry ? 'would update' : 'updated';
            $this->info("Done — {$verb} {$total} row(s) ({$shipChanges} ships, {$buildingChanges} buildings, {$knowledgeChanges} knowledge).");
        }

        return self::SUCCESS;
    }

    // ── Ships ─────────────────────────────────────────────────────────────────

    private function syncShips(bool $dry): int
    {
        $configs = config('ships', []);
        $changed = 0;

        foreach ($configs as $key => $cfg) {
            $id = (int) ($cfg['id'] ?? 0);
            if (!$id) {
                $this->warn("  ships/{$key}: missing 'id' — skipped.");
                continue;
            }

            $row = DB::table('ships')->where('id', $id)->first();
            if (!$row) {
                $this->warn("  ships/{$key}: id={$id} not found in DB — skipped.");
                continue;
            }

            $updates = [];

            foreach ([
                'moving_speed'      => (int)   ($cfg['moving_speed']      ?? $row->moving_speed),
                'decay_rate'        => (float)  ($cfg['decay_rate']        ?? $row->decay_rate),
                'supply_cost'       => (int)    ($cfg['supply_cost']       ?? $row->supply_cost),
                'max_status_points' => (int)    ($cfg['max_status_points'] ?? $row->max_status_points),
            ] as $col => $newVal) {
                if ((string) $row->$col !== (string) $newVal) {
                    $updates[$col] = $newVal;
                }
            }

            if (empty($updates)) {
                continue;
            }

            $this->line("  [ship] {$key} (id={$id}): " . $this->formatUpdates($row, $updates));

            if (!$dry) {
                DB::table('ships')->where('id', $id)->update($updates);
            }

            $changed++;
        }

        return $changed;
    }

    // ── Buildings ─────────────────────────────────────────────────────────────

    private function syncBuildings(bool $dry): int
    {
        $configs = config('buildings', []);
        $changed = 0;

        foreach ($configs as $key => $cfg) {
            $id = (int) ($cfg['id'] ?? 0);
            if (!$id) {
                $this->warn("  buildings/{$key}: missing 'id' — skipped.");
                continue;
            }

            $row = DB::table('buildings')->where('id', $id)->first();
            if (!$row) {
                $this->warn("  buildings/{$key}: id={$id} not found in DB — skipped.");
                continue;
            }

            $updates = [];

            foreach ([
                'decay_rate'        => (float) ($cfg['decay_rate']        ?? $row->decay_rate),
                'supply_cost'       => (int)   ($cfg['supply_cost']       ?? $row->supply_cost),
                'max_status_points' => (int)   ($cfg['max_status_points'] ?? $row->max_status_points),
                'max_level'         => isset($cfg['max_level'])
                                           ? ($cfg['max_level'] === null ? null : (int) $cfg['max_level'])
                                           : $row->max_level,
            ] as $col => $newVal) {
                // Loose comparison to handle null vs. empty string
                if ($row->$col != $newVal || (is_null($newVal) !== is_null($row->$col))) {
                    $updates[$col] = $newVal;
                }
            }

            if (empty($updates)) {
                continue;
            }

            $this->line("  [building] {$key} (id={$id}): " . $this->formatUpdates($row, $updates));

            if (!$dry) {
                DB::table('buildings')->where('id', $id)->update($updates);
            }

            $changed++;
        }

        return $changed;
    }

    // ── Knowledge ─────────────────────────────────────────────────────────────

    private function syncKnowledge(bool $dry): int
    {
        $configs = config('knowledge', []);
        $changed = 0;

        foreach ($configs as $key => $cfg) {
            $id = (int) ($cfg['id'] ?? 0);
            if (!$id) {
                $this->warn("  knowledge/{$key}: missing 'id' — skipped.");
                continue;
            }

            $row = DB::table('knowledge')->where('id', $id)->first();
            if (!$row) {
                $this->warn("  knowledge/{$key}: id={$id} not found in DB — skipped.");
                continue;
            }

            $updates = [];

            foreach ([
                'decay_rate'        => (float) ($cfg['decay_rate']        ?? $row->decay_rate),
                'max_status_points' => (int)   ($cfg['max_status_points'] ?? $row->max_status_points),
            ] as $col => $newVal) {
                if ((string) $row->$col !== (string) $newVal) {
                    $updates[$col] = $newVal;
                }
            }

            if (empty($updates)) {
                continue;
            }

            $this->line("  [knowledge] {$key} (id={$id}): " . $this->formatUpdates($row, $updates));

            if (!$dry) {
                DB::table('knowledge')->where('id', $id)->update($updates);
            }

            $changed++;
        }

        return $changed;
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatUpdates(object $row, array $updates): string
    {
        return implode(', ', array_map(
            fn($col, $val) => "{$col}: {$row->$col} → " . ($val === null ? 'null' : $val),
            array_keys($updates),
            $updates
        ));
    }
}
