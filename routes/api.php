<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// JSON API for the SvelteKit frontend (ADR 0006). Bearer-token auth via Sanctum.
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->get('/ping', fn (Request $request) => [
        'pong' => true,
        'user_id' => $request->user()->user_id,
    ]);
});
