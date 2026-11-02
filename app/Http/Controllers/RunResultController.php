<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use Illuminate\Http\JsonResponse;

class RunResultController extends Controller
{
    /** Result screen data for the SvelteKit frontend; 409 while the run is still going. */
    public function data(int $id): JsonResponse
    {
        $run = Run::with('objectives')->findOrFail($id);
        abort_if($run->user_id !== auth()->id(), 403);

        if (! in_array($run->status, ['completed', 'failed'], true)) {
            return response()->json(['ok' => false, 'error' => 'run_not_finished'], 409);
        }

        $catalog = app(RunTaskCatalog::class);

        return response()->json([
            'id' => $run->id,
            'status' => $run->status,
            'fail_reason' => $run->fail_reason,
            'score' => app(RunProgressService::class)->calculateScore($run),
            'current_tick' => (int) $run->current_tick,
            'tick_limit' => $run->getTickLimit(),
            'objectives' => $run->objectives->map(fn ($obj) => [
                'label' => $catalog->label($obj->task_key, $obj->target_value),
                'current' => $obj->current_value,
                'target' => $obj->target_value,
                'pct' => $obj->progressPct(),
                'done' => $obj->isCompleted(),
            ])->values(),
        ]);
    }
}
