<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run one game tick per day at 03:00 (calculation window defined in config/game.php).
Schedule::command('game:tick')->dailyAt('03:00');
