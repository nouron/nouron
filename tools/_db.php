<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Dev-panel DB handle: reuses the app's own database configuration (.env / DB_* env vars),
// so the panel follows whatever MySQL database the app is configured for.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

return DB::connection()->getPdo();
