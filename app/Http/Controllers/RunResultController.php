<?php

namespace App\Http\Controllers;

use App\Models\Run;
use App\Services\RunProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RunResultController extends Controller
{
    public function show(int $id): View|RedirectResponse
    {
        $run = Run::with('objectives')->findOrFail($id);

        if ($run->user_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($run->status, ['completed', 'failed'], true)) {
            return redirect()->route('colony.view');
        }

        $score = app(RunProgressService::class)->calculateScore($run);

        $objectives = $run->objectives->map(function ($obj) {
            return [
                'model'  => $obj,
                'label'  => trans('run.' . $obj->task_key),
            ];
        });

        return view('run.result', compact('run', 'score', 'objectives'));
    }
}
