<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Galaxy\GalaxyController;
use App\Http\Controllers\INNN\MessageController;
use App\Http\Controllers\Resources\JsonController as ResourcesController;
use App\Http\Controllers\Fleet\FleetController;
use App\Http\Controllers\Techtree\TechtreeController;
use App\Http\Controllers\Trade\TradeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────────────────

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('galaxy.index')
        : redirect()->route('login');
});

// ── Auth ─────────────────────────────────────────────────────────────────────

Route::middleware('guest')->group(function () {
    Route::get('/login',    [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login',   [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register',[RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── User ─────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('user')->name('user.')->group(function () {
    Route::get('/',         [UserController::class, 'show'])->name('show');
    Route::get('/settings', [UserController::class, 'settings'])->name('settings');
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
    Route::get('/news',          [MessageController::class, 'news'])->name('news');
    Route::get('/new',           [MessageController::class, 'compose'])->name('compose');
    Route::post('/send',         [MessageController::class, 'send'])->name('send');
    Route::post('/react',        [MessageController::class, 'react'])->name('react');
    Route::post('/archive/{id}', [MessageController::class, 'archiveMessage'])->name('archive.message');
    Route::post('/remove/{id}',  [MessageController::class, 'remove'])->name('remove');
});

// ── Trade (Schritt 8) ─────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('trade')->name('trade.')->group(function () {
    Route::match(['get', 'post'], '/resources',  [TradeController::class, 'resources'])->name('resources');
    Route::match(['get', 'post'], '/researches', [TradeController::class, 'researches'])->name('researches');
    Route::post('/offer/resource', [TradeController::class, 'addResourceOffer'])->name('offer.resource');
    Route::post('/offer/research', [TradeController::class, 'addResearchOffer'])->name('offer.research');
    Route::post('/offer/remove',   [TradeController::class, 'removeOffer'])->name('offer.remove');
});

// ── Fleet (Schritt 9) ─────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('fleet')->name('fleet.')->group(function () {
    Route::get('/',                        [FleetController::class, 'index'])->name('index');
    Route::get('/{id}/config',             [FleetController::class, 'config'])->name('config')->where('id', '[0-9]+');
    Route::post('/addtofleet',             [FleetController::class, 'addToFleet'])->name('addtofleet');
    Route::get('/{id}/technologies',       [FleetController::class, 'getFleetTechnologies'])->name('technologies')->where('id', '[0-9]+');
    Route::get('/{id}/resources',          [FleetController::class, 'getFleetResources'])->name('resources')->where('id', '[0-9]+');
});

// ── Techtree (Schritt 10) ─────────────────────────────────────────────────────

Route::middleware('auth')->prefix('techtree')->name('techtree.')->group(function () {
    Route::get('/',                            [TechtreeController::class, 'index'])->name('index');
    Route::get('/{type}/{id}',                 [TechtreeController::class, 'technology'])->name('technology');
    // Action route called by techtree.js AJAX: GET /techtree/{type}/{id}/{order}[/{ap}]
    Route::get('/{type}/{id}/{order}',         [TechtreeController::class, 'action'])->name('action');
    Route::get('/{type}/{id}/{order}/{ap}',    [TechtreeController::class, 'action'])->name('action.ap');
    Route::post('/{type}/{id}/order',          [TechtreeController::class, 'order'])->name('order');
});
