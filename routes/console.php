<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Legacy: fixed daily tick — replaced by player-triggered Sol (see SolController).
// Kept as fallback reference; will be activated for multiplayer timeout handling.
// Schedule::command('game:tick')->dailyAt('03:00');
