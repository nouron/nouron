<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Colony\ColonyController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Services\ShellStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// JSON API for the SvelteKit frontend (ADR 0006). Bearer-token auth via Sanctum.
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->get('/ping', fn (Request $request) => [
        'pong' => true,
        'user_id' => $request->user()->user_id,
    ]);

    // lang/{lang}/*.php as one JSON object per group (ADR 0006: lang files stay the single source).
    Route::get('/locale/{lang}', function (string $lang) {
        abort_unless(in_array($lang, config('app.supported_locales'), true), 404);

        return collect(glob(lang_path($lang.'/*.php')))
            ->mapWithKeys(fn (string $file) => [basename($file, '.php') => require $file]);
    });

    // Header data of every in-run screen — same source as the Blade layout composer.
    Route::middleware('auth:sanctum')->get('/shell', function (Request $request, ShellStateService $shell) {
        $user = $request->user();
        $colonyId = DB::table('glx_colonies')->where('user_id', $user->user_id)->where('is_primary', 1)->value('id');

        return collect($shell->forUser($user->user_id, $colonyId ? (int) $colonyId : null))
            ->mapWithKeys(fn ($value, $key) => [Str::snake($key) => $value])
            ->put('username', $user->username);
    });

    // Hint bar "×" and first-visit popups (hint_key visit_<screen>) — same action as Blade.
    Route::middleware(['auth:sanctum', 'run.started'])->post('/hint/dismiss', [ColonyController::class, 'dismissHint']);

    Route::middleware(['auth:sanctum', 'run.started'])->prefix('techtree')->group(function () {
        Route::get('/', [TechtreeController::class, 'data']);
        Route::post('/{type}/{id}/order', [TechtreeController::class, 'order']);
    });
});
