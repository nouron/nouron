<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\RunProgressService;
use App\Services\RunTaskCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

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

    public function show(int $id): View|RedirectResponse
    {
        $run = Run::with('objectives')->findOrFail($id);

        if ($run->user_id !== auth()->id()) {
            abort(403);
        }

        // Dev preview: admin can force-show result screen for any run status.
        $devPreview = ! app()->isProduction()
            && auth()->user()?->role === 'admin'
            && request()->boolean('preview');

        if (! $devPreview && ! in_array($run->status, ['completed', 'failed'], true)) {
            return redirect()->route('colony.view');
        }

        if ($devPreview && ! in_array($run->status, ['completed', 'failed'], true)) {
            $run->status = request()->input('outcome', 'completed') === 'failed' ? 'failed' : 'completed';
        }

        $score = app(RunProgressService::class)->calculateScore($run);

        $taskCatalog = app(RunTaskCatalog::class);
        $objectives = $run->objectives->map(function ($obj) use ($taskCatalog) {
            return [
                'model' => $obj,
                'label' => $taskCatalog->label($obj->task_key, $obj->target_value),
            ];
        });

        return view('run.result', compact('run', 'score', 'objectives'));
    }
}
