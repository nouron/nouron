<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Colony\BarController;
use App\Http\Controllers\Colony\HangarController;
use App\Http\Controllers\Colony\ColonyController;
use App\Http\Controllers\Colony\MerchantController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Galaxy\GalaxyController;
use App\Http\Controllers\INNN\MessageController;
use App\Http\Controllers\Resources\JsonController as ResourcesController;
use App\Http\Controllers\Fleet\FleetController;
use App\Http\Controllers\Techtree\AdvisorController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Http\Controllers\Trade\TradeController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\NexusDbController;
use App\Http\Controllers\RunResultController;
use App\Http\Controllers\SolController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Lobby ─────────────────────────────────────────────────────────────────────

Route::middleware('auth')->group(function () {
    Route::get('/lobby',          [LobbyController::class, 'index'])->name('lobby');
    Route::post('/lobby/start',   [LobbyController::class, 'start'])->name('lobby.start');
    Route::get('/run/{id}/result', [RunResultController::class, 'show'])->name('run.result')->where('id', '[0-9]+');
    Route::post('/run/new',        [LobbyController::class, 'newRun'])->name('run.new');
});

// ── Public ───────────────────────────────────────────────────────────────────

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('galaxy.index')
        : redirect()->route('login');
});

// ── Auth ─────────────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login',   [LoginController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register',[RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── User ─────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
    Route::get('/',                    [UserController::class, 'show'])->name('show');
    Route::get('/settings',            [UserController::class, 'settings'])->name('settings');
    Route::patch('/settings/name',       [UserController::class, 'updateDisplayName'])->name('update.displayname');
    Route::patch('/settings/password',   [UserController::class, 'updatePassword'])->name('update.password');
    Route::patch('/settings/onboarding', [UserController::class, 'updateOnboardingHints'])->name('update.onboarding');
});

// ── Colony ────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('colony')->name('colony.')->group(function () {
    Route::get('/',       [ColonyController::class, 'index'])->name('index');
    Route::get('/view',   [ColonyController::class, 'hexview'])->name('view');
    Route::patch('/name', [ColonyController::class, 'rename'])->name('rename');

    // Tile actions (AJAX)
    Route::post('/tile/explore',    [ColonyController::class, 'exploreTile'])->name('tile.explore');
    Route::post('/tile/deep-scan',  [ColonyController::class, 'deepScanTile'])->name('tile.deep-scan');

    // Building actions (AJAX)
    Route::get('/buildings/available', [ColonyController::class, 'availableBuildings'])->name('buildings.available');
    Route::post('/building/place',     [ColonyController::class, 'placeBuilding'])->name('building.place');
    Route::post('/building/invest',    [ColonyController::class, 'investBuilding'])->name('building.invest');

    // Onboarding hint actions (AJAX)
    Route::post('/hint/dismiss', [ColonyController::class, 'dismissHint'])->name('hint.dismiss');

    // Bar/Cantina
    Route::get('/bar',               [BarController::class, 'index'])->name('bar');
    Route::post('/bar/accept/{offer}', [BarController::class, 'accept'])->name('bar.accept');

    // Traveling Merchant
    Route::post('/merchant/buy/{itemId}',         [MerchantController::class, 'buy'])->name('merchant.buy')->where('itemId', '[0-9]+');
    Route::post('/merchant/visit/{visitId}/open', [MerchantController::class, 'markVisited'])->name('merchant.open')->where('visitId', '[0-9]+');

    // Hangar
    Route::get('/hangar',                              [HangarController::class, 'index'])->name('hangar');
    Route::post('/hangar/{instanceId}/build',    [HangarController::class, 'build'])->name('hangar.build');
    Route::post('/hangar/{instanceId}/dispatch', [HangarController::class, 'dispatch'])->name('hangar.dispatch');
    Route::post('/hangar/{instanceId}/recall',   [HangarController::class, 'recall'])->name('hangar.recall');
    Route::post('/hangar/{instanceId}/repair',   [HangarController::class, 'repair'])->name('hangar.repair');
});

// ── Resources (Schritt 5) ─────────────────────────────────────────────────────

Route::middleware('auth')->prefix('resources')->name('resources.')->group(function () {
    Route::get('/',              [ResourcesController::class, 'getResources'])->name('index');
    Route::get('/colony/{id}',   [ResourcesController::class, 'getColonyResources'])->name('colony');
    Route::get('/resourcebar',   [ResourcesController::class, 'reloadResourceBar'])->name('resourcebar');
});

// ── Galaxy (Schritt 6) ────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('galaxy')->name('galaxy.')->group(function () {
    Route::get('/',                          [GalaxyController::class, 'index'])->name('index');
    Route::get('/system/{sid}',              [GalaxyController::class, 'showSystem'])->name('system')->where('sid', '[0-9]+');
    Route::get('/{sid}',                     [GalaxyController::class, 'showSystem'])->where('sid', '[0-9]+');
    Route::get('/json/mapdata',              [GalaxyController::class, 'getMapData'])->name('json.mapdata');
    Route::get('/json/getmapdata/{x}/{y}',   [GalaxyController::class, 'getMapData'])->name('json.getmapdata');
});

// ── INNN Messages (Schritt 7) ─────────────────────────────────────────────────

Route::middleware('auth')->prefix('messages')->name('messages.')->group(function () {
    Route::get('/',              [MessageController::class, 'inbox'])->name('inbox');
    Route::get('/outbox',        [MessageController::class, 'outbox'])->name('outbox');
    Route::get('/archive',       [MessageController::class, 'showArchive'])->name('archive');
    Route::get('/events',        [MessageController::class, 'events'])->name('events');
    Route::get('/actions',       [MessageController::class, 'actions'])->name('actions');
    Route::get('/news',          [MessageController::class, 'news'])->name('news');
    Route::get('/new',           [MessageController::class, 'compose'])->name('compose');
    Route::post('/send',         [MessageController::class, 'send'])->name('send');
    Route::post('/react',        [MessageController::class, 'react'])->name('react');
    Route::post('/archive/{id}', [MessageController::class, 'archiveMessage'])->name('archive.message');
    Route::post('/remove/{id}',  [MessageController::class, 'remove'])->name('remove');
});

// ── Trade (Schritt 8) ─────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('trade')->name('trade.')->group(function () {
    Route::match(['get', 'post'], '/resources', [TradeController::class, 'resources'])->name('resources');
    Route::post('/offer/resource', [TradeController::class, 'addResourceOffer'])->name('offer.resource');
    Route::post('/offer/remove',   [TradeController::class, 'removeOffer'])->name('offer.remove');
    Route::post('/offer/accept',   [TradeController::class, 'acceptResourceOffer'])->name('offer.accept');
});

// ── Fleet (Schritt 9) ─────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('fleet')->name('fleet.')->group(function () {
    Route::get('/',                        [FleetController::class, 'index'])->name('index');
    Route::post('/',                       [FleetController::class, 'store'])->name('store');
    Route::delete('/{id}',                 [FleetController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
    Route::get('/{id}/config',             [FleetController::class, 'config'])->name('config')->where('id', '[0-9]+');
    Route::post('/{id}/orders',            [FleetController::class, 'storeOrder'])->name('orders.store')->where('id', '[0-9]+');
    // JSON endpoints — path matches fleets.js expectations
    Route::post('/json/addToFleet/{id}',   [FleetController::class, 'addToFleet'])->name('json.addtofleet')->where('id', '[0-9]+');
    Route::get('/json/getFleetTechnologies/{id}', [FleetController::class, 'getFleetTechnologies'])->name('json.technologies')->where('id', '[0-9]+');
    Route::get('/json/getFleetResources/{id}',    [FleetController::class, 'getFleetResources'])->name('json.resources')->where('id', '[0-9]+');
    // Commander assignment
    Route::post('/{id}/commander/assign', [FleetController::class, 'assignCommander'])->name('commander.assign')->where('id', '[0-9]+');
    Route::post('/{id}/commander/remove', [FleetController::class, 'removeCommander'])->name('commander.remove')->where('id', '[0-9]+');
});

// ── Advisors ──────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('advisors')->name('advisors.')->group(function () {
    Route::get('/',      [AdvisorController::class, 'index'])->name('index');
    Route::post('/hire', [AdvisorController::class, 'hire'])->name('hire');
    Route::delete('/{id}', [AdvisorController::class, 'fire'])->name('fire')->where('id', '[0-9]+');
});

// ── Sol (player-triggered tick advancement) ───────────────────────────────────

Route::middleware('auth')->post('/sol/next',        [SolController::class, 'next'])->name('sol.next');
Route::middleware('auth')->get('/sol/remaining-ap', [SolController::class, 'remainingAp'])->name('sol.remaining-ap');

// ── Nexus Database (static reference) ────────────────────────────────────────

Route::middleware('auth')->get('/nexus-db', [NexusDbController::class, 'index'])->name('nexusdb.index');

// ── Techtree (Schritt 10) ─────────────────────────────────────────────────────

Route::middleware('auth')->prefix('techtree')->name('techtree.')->group(function () {
    Route::get('/',                            [TechtreeController::class, 'index'])->name('index');
    Route::get('/{type}/{id}',                 [TechtreeController::class, 'technology'])->name('technology');
    // Action route called by techtree.js AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
    Route::get('/{type}/{id}/{order}',         [TechtreeController::class, 'action'])->name('action');
    Route::get('/{type}/{id}/{order}/{ap}',    [TechtreeController::class, 'action'])->name('action.ap');
    Route::post('/{type}/{id}/order',          [TechtreeController::class, 'order'])->name('order');
});
