<?php

use Illuminate\Support\Facades\Route;

// Laravel only serves the JSON API (routes/api.php) and images under /img (ADR 0006).
// The game itself is the SvelteKit app; send stray browser visits there.
Route::get('/', fn () => redirect(config('cors.allowed_origins')[0]));
