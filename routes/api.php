<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Techtree\TechtreeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    Route::middleware(['auth:sanctum', 'run.started'])->prefix('techtree')->group(function () {
        Route::get('/', [TechtreeController::class, 'data']);
        Route::post('/{type}/{id}/order', [TechtreeController::class, 'order']);
    });
});
