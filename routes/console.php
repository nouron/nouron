<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule: run one tick per day at 03:00 (matches calculation window in config/game.php)
// Uncomment once the game goes live:
// Schedule::command('game:tick')->dailyAt('03:00');
