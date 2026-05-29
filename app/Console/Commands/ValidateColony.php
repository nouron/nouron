<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateColony extends Command
{
    protected $signature   = 'game:validate-colony {colony_id? : Colony ID to check (default: all active colonies)}';
    protected $description = 'Validate colony game state — detect supply overrun, AP inconsistencies, missing run, etc.';

    public function handle(): int
    {
        $colonyIdArg = $this->argument('colony_id');
        $colonies = $colonyIdArg
            ? [DB::table('glx_colonies')->where('id', $colonyIdArg)->first()]
            : DB::table('glx_colonies')->get()->all();

        $hasErrors = false;

        foreach ($colonies as $colony) {
            if (!$colony) {
                $this->error("Colony {$colonyIdArg} not found.");
                return 1;
            }

            $issues = [];
            $id   = $colony->id;
            $name = $colony->name ?? "Colony #{$id}";

            // --- Check 1: Active run exists ---
            $run = DB::table('runs')
                ->where('colony_id', $id)
                ->where('status', 'active')
                ->first();
            if (!$run) {
                $issues[] = ['WARN', 'No active run for colony'];
            }

            // --- Check 2: Supply cap vs usage ---
            $userResource = DB::table('user_resources')
                ->where('user_id', $colony->user_id)
                ->first();
            $supplyCap   = $userResource ? (int) $userResource->supply : 0;

            $buildingUsage = DB::table('colony_buildings as cb')
                ->join('buildings as b', 'cb.building_id', '=', 'b.id')
                ->where('cb.colony_id', $id)
                ->where('cb.level', '>', 0)
                ->selectRaw('SUM(cb.level * b.supply_cost) as total_usage')
                ->value('total_usage') ?? 0;

            if ($buildingUsage > $supplyCap) {
                $issues[] = ['ERROR', "Supply overrun: usage={$buildingUsage} > cap={$supplyCap} (deficit=" . ($buildingUsage - $supplyCap) . ")"];
            } else {
                $issues[] = ['OK', "Supply: usage={$buildingUsage} / cap={$supplyCap}"];
            }

            // --- Check 3: CC level exists ---
            $ccLevel = DB::table('colony_buildings')
                ->where('colony_id', $id)
                ->where('building_id', 25)
                ->value('level') ?? 0;
            if ($ccLevel === 0) {
                $issues[] = ['ERROR', 'CommandCenter missing or level 0'];
            } else {
                $issues[] = ['OK', "CC level: {$ccLevel}"];
            }

            // --- Check 4: Trust resource exists ---
            $trust = DB::table('colony_resources')
                ->where('colony_id', $id)
                ->where('resource_id', 12)
                ->value('amount');
            if ($trust === null) {
                $issues[] = ['WARN', 'Trust resource row missing'];
            } elseif ((int)$trust < -50) {
                $issues[] = ['WARN', "Trust critically low: {$trust}"];
            }

            // --- Check 5: run current_tick sanity ---
            if ($run) {
                $tickLimit = json_decode($run->settings ?? '{}', true)['tick_limit'] ?? 100;
                if ($run->current_tick > $tickLimit) {
                    $issues[] = ['ERROR', "current_tick={$run->current_tick} exceeds tick_limit={$tickLimit} — run should have ended"];
                } else {
                    $issues[] = ['OK', "current_tick={$run->current_tick} / {$tickLimit}"];
                }
            }

            // --- Output ---
            $this->line('');
            $this->line("<options=bold>{$name}</> (id={$id})");
            foreach ($issues as [$level, $msg]) {
                match ($level) {
                    'OK'    => $this->line("  <fg=green>✓</> {$msg}"),
                    'WARN'  => $this->warn("  ⚠ {$msg}"),
                    'ERROR' => $this->error("  ✗ {$msg}"),
                    default => $this->line("  {$msg}"),
                };
            }

            if (collect($issues)->contains(fn($i) => $i[0] === 'ERROR')) {
                $hasErrors = true;
            }
        }

        $this->line('');
        return $hasErrors ? 1 : 0;
    }
}
