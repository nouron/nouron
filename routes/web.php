<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Colony\BarController;
use App\Http\Controllers\Colony\ColonyController;
use App\Http\Controllers\Colony\HangarController;
use App\Http\Controllers\Colony\MerchantController;
use App\Http\Controllers\CommLog\CommLogController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\NexusDbController;
use App\Http\Controllers\Resources\JsonController as ResourcesController;
use App\Http\Controllers\RunResultController;
use App\Http\Controllers\SolController;
use App\Http\Controllers\Techtree\AdvisorController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Lobby ─────────────────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/lobby/start', [LobbyController::class, 'start'])->name('lobby.start');
    Route::post('/lobby/{run}/abandon', [LobbyController::class, 'abandon'])->name('lobby.abandon');
    Route::get('/run/{id}/result', [RunResultController::class, 'show'])->name('run.result')->where('id', '[0-9]+');
    Route::post('/run/new', [LobbyController::class, 'newRun'])->name('run.new');
});

// ── Public ───────────────────────────────────────────────────────────────────

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('lobby')
        : redirect()->route('login');
});

// ── Auth ─────────────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── User ─────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
    Route::get('/', [UserController::class, 'show'])->name('show');
    Route::get('/settings', [UserController::class, 'settings'])->name('settings');
    Route::patch('/settings/name', [UserController::class, 'updateDisplayName'])->name('update.displayname');
    Route::patch('/settings/password', [UserController::class, 'updatePassword'])->name('update.password');
    Route::patch('/settings/onboarding', [UserController::class, 'updateOnboardingHints'])->name('update.onboarding');
});

// ── Colony ────────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'run.started'])->prefix('colony')->name('colony.')->group(function () {
    Route::get('/view', [ColonyController::class, 'hexview'])->name('view');
    Route::patch('/name', [ColonyController::class, 'rename'])->name('rename');

    // Tile actions (AJAX)
    Route::post('/tile/explore', [ColonyController::class, 'exploreTile'])->name('tile.explore');
    Route::post('/tile/deep-scan', [ColonyController::class, 'deepScanTile'])->name('tile.deep-scan');

    // Building actions (AJAX)
    Route::get('/buildings/available', [ColonyController::class, 'availableBuildings'])->name('buildings.available');
    Route::post('/building/place', [ColonyController::class, 'placeBuilding'])->name('building.place');
    Route::post('/building/invest', [ColonyController::class, 'investBuilding'])->name('building.invest');
    Route::post('/building/repair', [ColonyController::class, 'repairBuilding'])->name('building.repair');

    // Onboarding hint actions (AJAX)
    Route::post('/hint/dismiss', [ColonyController::class, 'dismissHint'])->name('hint.dismiss');

    // Bar/Cantina
    Route::get('/bar', [BarController::class, 'index'])->name('bar');
    Route::post('/bar/accept/{offer}', [BarController::class, 'accept'])->name('bar.accept');

    // Traveling Merchant
    Route::post('/merchant/buy/{itemId}', [MerchantController::class, 'buy'])->name('merchant.buy')->where('itemId', '[0-9]+');
    Route::post('/merchant/visit/{visitId}/open', [MerchantController::class, 'markVisited'])->name('merchant.open')->where('visitId', '[0-9]+');

    // Hangar
    Route::get('/hangar', [HangarController::class, 'index'])->name('hangar');
    Route::post('/hangar/request', [HangarController::class, 'requestShip'])->name('hangar.request');
    Route::post('/hangar/assign', [HangarController::class, 'assignToHangar'])->name('hangar.assign');
    Route::post('/hangar/{instanceId}/dispatch', [HangarController::class, 'dispatch'])->name('hangar.dispatch');
    Route::post('/hangar/{instanceId}/recall', [HangarController::class, 'recall'])->name('hangar.recall');
    Route::post('/hangar/{instanceId}/repair', [HangarController::class, 'repair'])->name('hangar.repair');
});

// ── Resources (Schritt 5) ─────────────────────────────────────────────────────

Route::middleware('auth')->prefix('resources')->name('resources.')->group(function () {
    Route::get('/', [ResourcesController::class, 'getResources'])->name('index');
    Route::get('/colony/{id}', [ResourcesController::class, 'getColonyResources'])->name('colony');
    Route::get('/resourcebar', [ResourcesController::class, 'reloadResourceBar'])->name('resourcebar');
});

// ── Kolonieprotokoll ──────────────────────────────────────────────────────────

Route::middleware(['auth', 'run.started'])->prefix('comm-log')->name('comm.')->group(function () {
    Route::get('/', [CommLogController::class, 'log'])->name('log');
    Route::get('/nexus', [CommLogController::class, 'nexus'])->name('nexus');
});

// ── Advisors ──────────────────────────────────────────────────────────────────

Route::middleware(['auth', 'run.started'])->prefix('advisors')->name('advisors.')->group(function () {
    Route::get('/', [AdvisorController::class, 'index'])->name('index');
    Route::post('/hire', [AdvisorController::class, 'hire'])->name('hire');
    Route::delete('/{id}', [AdvisorController::class, 'fire'])->name('fire')->where('id', '[0-9]+');
});

// ── Sol (player-triggered tick advancement) ───────────────────────────────────

Route::middleware('auth')->post('/sol/next', [SolController::class, 'next'])->name('sol.next');
Route::middleware('auth')->get('/sol/remaining-ap', [SolController::class, 'remainingAp'])->name('sol.remaining-ap');
Route::middleware('auth')->post('/sol/report-skip', [SolController::class, 'updateReportSkip'])->name('sol.report-skip');

// ── Nexus Database (static reference) ────────────────────────────────────────

Route::middleware(['auth', 'run.started'])->get('/nexus-db', [NexusDbController::class, 'index'])->name('nexusdb.index');

// ── Techtree (Schritt 10) ─────────────────────────────────────────────────────

Route::middleware(['auth', 'run.started'])->prefix('techtree')->name('techtree.')->group(function () {
    Route::get('/', [TechtreeController::class, 'index'])->name('index');
    Route::get('/{type}/{id}', [TechtreeController::class, 'technology'])->name('technology');
    // Action route called by techtree.js AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
    Route::get('/{type}/{id}/{order}', [TechtreeController::class, 'action'])->name('action');
    Route::get('/{type}/{id}/{order}/{ap}', [TechtreeController::class, 'action'])->name('action.ap');
    Route::post('/{type}/{id}/order', [TechtreeController::class, 'order'])->name('order');
});
