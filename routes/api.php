<?php

use App\Http\Controllers\Admin\FeedbackController as AdminFeedbackController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Colony\BarController;
use App\Http\Controllers\Colony\ColonyController;
use App\Http\Controllers\Colony\CommandCenterController;
use App\Http\Controllers\Colony\HangarController;
use App\Http\Controllers\Colony\MerchantController;
use App\Http\Controllers\Colony\PathDepthController;
use App\Http\Controllers\CommLog\CommLogController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\NexusDbController;
use App\Http\Controllers\RunResultController;
use App\Http\Controllers\SolController;
use App\Http\Controllers\Techtree\AdvisorController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Http\Controllers\UserController;
use App\Services\ShellStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// JSON API for the SvelteKit frontend (ADR 0006). Bearer-token auth via Sanctum.
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/auth/register', [RegisterController::class, 'showRegistrationForm'])->name('register.form');
    // Throttled like the web form: the shared beta invite code must not be brute-forceable (R9).
    Route::post('/auth/register', [RegisterController::class, 'register'])->middleware('throttle:5,1')->name('register');

    // In-game feedback (closed beta) — same action and throttle as Blade.
    Route::middleware(['auth:sanctum', 'throttle:5,1'])->post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

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
            ->put('username', $user->username)
            ->put('locale', app()->getLocale())
            ->put('is_admin', $user->isAdmin());
    });

    // Admin screens (403 for players).
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        Route::get('/feedback', [AdminFeedbackController::class, 'index'])->name('admin.feedback');
        Route::get('/debug', [AdminFeedbackController::class, 'debug'])->name('admin.debug');
    });

    // Profile and settings — same actions as the Blade settings page.
    Route::middleware('auth:sanctum')->prefix('settings')->group(function () {
        Route::get('/', [UserController::class, 'settingsJson'])->name('user.settings');
        Route::patch('/name', [UserController::class, 'updateDisplayName'])->name('user.update.displayname');
        Route::patch('/password', [UserController::class, 'updatePassword'])->name('user.update.password');
        Route::patch('/onboarding', [UserController::class, 'updateOnboardingHints'])->name('user.update.onboarding');
        Route::patch('/locale', [UserController::class, 'updateLocale'])->name('user.update.locale');
    });

    // End the Sol — same actions as the Blade Sol button (report JSON, 409 while another advance runs).
    Route::middleware('auth:sanctum')->prefix('sol')->group(function () {
        Route::get('/remaining-ap', [SolController::class, 'remainingAp'])->name('sol.remaining-ap');
        Route::post('/next', [SolController::class, 'next'])->name('sol.next');
    });

    // Hint bar "×" and first-visit popups (hint_key visit_<screen>) — same action as Blade.
    Route::middleware(['auth:sanctum', 'run.started'])->post('/hint/dismiss', [ColonyController::class, 'dismissHint'])->name('colony.hint.dismiss');

    // Colony hex view — data plus the same tile/building actions as the Blade screen.
    Route::middleware(['auth:sanctum', 'run.started'])->prefix('colony')->group(function () {
        Route::get('/', [ColonyController::class, 'data'])->name('colony.view');
        Route::post('/tile/explore', [ColonyController::class, 'exploreTile'])->name('colony.tile.explore');
        Route::post('/tile/deep-scan', [ColonyController::class, 'deepScanTile'])->name('colony.tile.deep-scan');
        Route::post('/tile/salvage', [ColonyController::class, 'salvageTile'])->name('colony.tile.salvage');
        Route::get('/buildings/available', [ColonyController::class, 'availableBuildings'])->name('colony.buildings.available');
        Route::post('/building/place', [ColonyController::class, 'placeBuilding'])->name('colony.building.place');
        Route::post('/building/invest', [ColonyController::class, 'investBuilding'])->name('colony.building.invest');
        Route::post('/building/repair', [ColonyController::class, 'repairBuilding'])->name('colony.building.repair');
        Route::get('/building/leveldown-preview', [ColonyController::class, 'leveldownPreview'])->name('colony.building.leveldown-preview');
        Route::post('/building/leveldown', [ColonyController::class, 'leveldownBuilding'])->name('colony.building.leveldown');
        Route::post('/hangar/{bay}/dispatch', [HangarController::class, 'dispatch'])->whereNumber('bay')->name('colony.hangar.dispatch');
        Route::post('/hangar/{bay}/recall', [HangarController::class, 'recall'])->whereNumber('bay')->name('colony.hangar.recall');
        Route::post('/hangar/{bay}/repair', [HangarController::class, 'repair'])->whereNumber('bay')->name('colony.hangar.repair');
        Route::post('/hangar/request', [HangarController::class, 'requestShip'])->name('colony.hangar.request');
        Route::post('/hangar/assign', [HangarController::class, 'assignToHangar'])->name('colony.hangar.assign');
        // Cantina: offers, encounters, bartender, concerns, information pool, merchant.
        Route::post('/bar/accept/{offer}', [BarController::class, 'accept'])->whereNumber('offer')->name('colony.bar.accept');
        Route::post('/bar/negotiate/{offer}', [BarController::class, 'negotiate'])->whereNumber('offer')->name('colony.bar.negotiate');
        Route::post('/bar/accept-encounter/{encounter}', [BarController::class, 'acceptEncounter'])->whereNumber('encounter')->name('colony.bar.accept-encounter');
        Route::post('/bar/talk-to-bartender', [BarController::class, 'talkToBartender'])->name('colony.bar.talk-to-bartender');
        Route::post('/bar/concern/{concern}', [BarController::class, 'resolveConcern'])->whereNumber('concern')->name('colony.bar.resolve-concern');
        Route::post('/bar/information-encounter/{encounter}', [BarController::class, 'resolveInformationEncounter'])->whereNumber('encounter')->name('colony.bar.resolve-information');
        Route::post('/merchant/buy/{itemId}', [MerchantController::class, 'buy'])->whereNumber('itemId')->name('colony.merchant.buy');
        Route::post('/merchant/visit/{visitId}/open', [MerchantController::class, 'markVisited'])->whereNumber('visitId')->name('colony.merchant.open');
        // Path depth (T41): hangar training and cantina relationships.
        Route::post('/path-depth/{kind}/{key}/invest', [PathDepthController::class, 'invest'])->name('colony.pathdepth.invest');
        // Kommandozentrale actions: Kolonisten-Zulage and Nexus imports.
        Route::post('/stipend', [ColonyController::class, 'purchaseStipend'])->name('colony.stipend');
        Route::post('/nexus/import-compounds', [ColonyController::class, 'nexusImportCompounds'])->name('colony.nexus.import');
        Route::post('/nexus/import-delayed', [ColonyController::class, 'nexusImportResource'])->name('colony.nexus.import-delayed');
    });

    Route::middleware(['auth:sanctum', 'run.started'])->get('/command-center', [CommandCenterController::class, 'data'])->name('colony.command_center');
    Route::middleware(['auth:sanctum', 'run.started'])->get('/hangar', [HangarController::class, 'data'])->name('colony.hangar');
    Route::middleware(['auth:sanctum', 'run.started'])->get('/cantina', [BarController::class, 'data'])->name('colony.bar');

    // Read-only pages: Protokoll/Nexus-Funk (?tab=nexus marks read), Nexus-DB, Codex (no run needed).
    Route::middleware(['auth:sanctum', 'run.started'])->get('/comm-log', [CommLogController::class, 'data'])->name('comm.log');
    Route::middleware(['auth:sanctum', 'run.started'])->get('/nexus-db', [NexusDbController::class, 'data'])->name('nexusdb.index');
    Route::middleware('auth:sanctum')->get('/codex', [UserController::class, 'codexData'])->name('user.codex');

    // Lobby and run result (outside a running run). Renaming is allowed before the start —
    // the lobby offers it for pending runs (the Blade route sat behind run.started).
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/lobby', [LobbyController::class, 'data'])->name('lobby');
        Route::post('/lobby/start', [LobbyController::class, 'start'])->name('lobby.start');
        Route::post('/lobby/{run}/abandon', [LobbyController::class, 'abandon'])->whereNumber('run')->name('lobby.abandon');
        Route::post('/run/new', [LobbyController::class, 'newRun'])->name('run.new');
        Route::get('/run/{id}/result', [RunResultController::class, 'data'])->whereNumber('id')->name('run.result');
        Route::patch('/colony/name', [ColonyController::class, 'rename'])->name('colony.rename');
    });

    // Advisors carousel — same actions as Blade (hire, fire, promote).
    Route::middleware(['auth:sanctum', 'run.started'])->prefix('advisors')->group(function () {
        Route::get('/', [AdvisorController::class, 'data'])->name('advisors.index');
        Route::post('/hire', [AdvisorController::class, 'hire'])->name('advisors.hire');
        Route::delete('/{id}', [AdvisorController::class, 'fire'])->whereNumber('id')->name('advisors.fire');
        Route::post('/{id}/promote', [AdvisorController::class, 'promote'])->whereNumber('id')->name('advisors.promote');
    });

    Route::middleware(['auth:sanctum', 'run.started'])->prefix('techtree')->group(function () {
        Route::get('/', [TechtreeController::class, 'data'])->name('techtree.index');
        Route::post('/{type}/{id}/order', [TechtreeController::class, 'order'])->name('techtree.order');
    });
});
