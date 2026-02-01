<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/web/home.php';

Route::middleware(['auth', 'verified'])->group(function () {
    require __DIR__.'/web/dashboard.php';
    require __DIR__.'/web/watchlist.php';
    require __DIR__.'/web/admin.php';
});

// Public movie routes after admin so /movies/{slug} does not conflict with admin resource
require __DIR__.'/web/movies.php';

require __DIR__.'/web/people.php';

require __DIR__.'/web/misc.php';
require __DIR__.'/settings.php';
