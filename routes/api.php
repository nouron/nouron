<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Colony\BarController;
use App\Http\Controllers\Colony\ColonyController;
use App\Http\Controllers\Colony\CommandCenterController;
use App\Http\Controllers\Colony\HangarController;
use App\Http\Controllers\Colony\MerchantController;
use App\Http\Controllers\Colony\PathDepthController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\SolController;
use App\Http\Controllers\Techtree\AdvisorController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Services\ShellStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// JSON API for the SvelteKit frontend (ADR 0006). Bearer-token auth via Sanctum.
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

    // In-game feedback (closed beta) — same action and throttle as Blade.
    Route::middleware(['auth:sanctum', 'throttle:5,1'])->post('/feedback', [FeedbackController::class, 'store']);

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

    // End the Sol — same actions as the Blade Sol button (report JSON, 409 while another advance runs).
    Route::middleware('auth:sanctum')->prefix('sol')->group(function () {
        Route::get('/remaining-ap', [SolController::class, 'remainingAp']);
        Route::post('/next', [SolController::class, 'next']);
    });

    // Hint bar "×" and first-visit popups (hint_key visit_<screen>) — same action as Blade.
    Route::middleware(['auth:sanctum', 'run.started'])->post('/hint/dismiss', [ColonyController::class, 'dismissHint']);

    // Colony hex view — data plus the same tile/building actions as the Blade screen.
    Route::middleware(['auth:sanctum', 'run.started'])->prefix('colony')->group(function () {
        Route::get('/', [ColonyController::class, 'data']);
        Route::post('/tile/explore', [ColonyController::class, 'exploreTile']);
        Route::post('/tile/deep-scan', [ColonyController::class, 'deepScanTile']);
        Route::post('/tile/salvage', [ColonyController::class, 'salvageTile']);
        Route::get('/buildings/available', [ColonyController::class, 'availableBuildings']);
        Route::post('/building/place', [ColonyController::class, 'placeBuilding']);
        Route::post('/building/invest', [ColonyController::class, 'investBuilding']);
        Route::post('/building/repair', [ColonyController::class, 'repairBuilding']);
        Route::get('/building/leveldown-preview', [ColonyController::class, 'leveldownPreview']);
        Route::post('/building/leveldown', [ColonyController::class, 'leveldownBuilding']);
        Route::post('/hangar/{bay}/dispatch', [HangarController::class, 'dispatch'])->whereNumber('bay');
        Route::post('/hangar/{bay}/recall', [HangarController::class, 'recall'])->whereNumber('bay');
        Route::post('/hangar/{bay}/repair', [HangarController::class, 'repair'])->whereNumber('bay');
        Route::post('/hangar/request', [HangarController::class, 'requestShip']);
        Route::post('/hangar/assign', [HangarController::class, 'assignToHangar']);
        // Cantina: offers, encounters, bartender, concerns, information pool, merchant.
        Route::post('/bar/accept/{offer}', [BarController::class, 'accept'])->whereNumber('offer');
        Route::post('/bar/negotiate/{offer}', [BarController::class, 'negotiate'])->whereNumber('offer');
        Route::post('/bar/accept-encounter/{encounter}', [BarController::class, 'acceptEncounter'])->whereNumber('encounter');
        Route::post('/bar/talk-to-bartender', [BarController::class, 'talkToBartender']);
        Route::post('/bar/concern/{concern}', [BarController::class, 'resolveConcern'])->whereNumber('concern');
        Route::post('/bar/information-encounter/{encounter}', [BarController::class, 'resolveInformationEncounter'])->whereNumber('encounter');
        Route::post('/merchant/buy/{itemId}', [MerchantController::class, 'buy'])->whereNumber('itemId');
        Route::post('/merchant/visit/{visitId}/open', [MerchantController::class, 'markVisited'])->whereNumber('visitId');
        // Path depth (T41): hangar training and cantina relationships.
        Route::post('/path-depth/{kind}/{key}/invest', [PathDepthController::class, 'invest']);
        // Kommandozentrale actions: Kolonisten-Zulage and Nexus imports.
        Route::post('/stipend', [ColonyController::class, 'purchaseStipend']);
        Route::post('/nexus/import-compounds', [ColonyController::class, 'nexusImportCompounds']);
        Route::post('/nexus/import-delayed', [ColonyController::class, 'nexusImportResource']);
    });

    Route::middleware(['auth:sanctum', 'run.started'])->get('/command-center', [CommandCenterController::class, 'data']);
    Route::middleware(['auth:sanctum', 'run.started'])->get('/hangar', [HangarController::class, 'data']);
    Route::middleware(['auth:sanctum', 'run.started'])->get('/cantina', [BarController::class, 'data']);

    // Advisors carousel — same actions as Blade (hire, fire, promote).
    Route::middleware(['auth:sanctum', 'run.started'])->prefix('advisors')->group(function () {
        Route::get('/', [AdvisorController::class, 'data']);
        Route::post('/hire', [AdvisorController::class, 'hire']);
        Route::delete('/{id}', [AdvisorController::class, 'fire'])->whereNumber('id');
        Route::post('/{id}/promote', [AdvisorController::class, 'promote'])->whereNumber('id');
    });

    Route::middleware(['auth:sanctum', 'run.started'])->prefix('techtree')->group(function () {
        Route::get('/', [TechtreeController::class, 'data']);
        Route::post('/{type}/{id}/order', [TechtreeController::class, 'order']);
    });
});
