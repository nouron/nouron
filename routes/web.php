<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ───────────────────────────────────────────────────────────────────

Route::get('/', fn() => redirect()->route('login'));

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

// ── Game (placeholder routes — filled in as modules are migrated) ────────────

Route::middleware('auth')->group(function () {
    // Techtree — Schritt 10
    Route::get('/techtree', fn() => abort(501, 'Not yet migrated'))->name('techtree.index');
});
