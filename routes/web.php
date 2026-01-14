<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $heroSlot = \App\Models\FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('hero')
        ->latest('starts_at')
        ->first();

    $pickOfWeekSlot = \App\Models\FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('pick_of_week')
        ->latest('starts_at')
        ->first();

    $latestMovies = \App\Models\Movie::query()
        ->published()
        ->with('tags')
        ->latest('release_date')
        ->limit(12)
        ->get();

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'heroMovie' => $heroSlot?->movie,
        'pickOfWeekMovie' => $pickOfWeekSlot?->movie,
        'latestMovies' => $latestMovies,
    ]);
})->name('home');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/movies', [App\Http\Controllers\MovieController::class, 'index'])->name('movies.index');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/watchlist', function () {
        $movies = auth()->user()->watchlist()->with('tags')->get();

        return Inertia::render('Watchlist/Index', [
            'movies' => $movies,
        ]);
    })->name('watchlist.index');

    Route::post('/watchlist/{movie}', [App\Http\Controllers\WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{movie}', [App\Http\Controllers\WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    // Admin-only TMDB movie management
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/tmdb-imports', [App\Http\Controllers\DashboardController::class, 'tmdbImports'])->name('dashboard.tmdb-imports');
        Route::post('/tmdb/import', [App\Http\Controllers\DashboardController::class, 'importTmdbMovies'])->name('tmdb.import');
        Route::post('/movies/{movie}/publish', [App\Http\Controllers\DashboardController::class, 'publishMovie'])->name('tmdb.movies.publish');
        Route::post('/movies/{movie}/archive', [App\Http\Controllers\DashboardController::class, 'archiveMovie'])->name('tmdb.movies.archive');

        Route::get('/dashboard/movies', [App\Http\Controllers\Admin\MovieController::class, 'index'])->name('dashboard.movies');
        Route::post('/movies/{movie}/publish', [App\Http\Controllers\Admin\MovieController::class, 'publish'])->name('admin.movies.publish');
        Route::post('/movies/{movie}/unpublish', [App\Http\Controllers\Admin\MovieController::class, 'unpublish'])->name('admin.movies.unpublish');
        Route::resource('movies', App\Http\Controllers\Admin\MovieController::class)->except(['index', 'show']);
        Route::get('/dashboard/featured-slots', [App\Http\Controllers\Admin\FeaturedSlotController::class, 'index'])->name('dashboard.featured-slots');
        Route::resource('featured-slots', App\Http\Controllers\Admin\FeaturedSlotController::class);

        // X Post Management
        Route::prefix('x-posts')->name('admin.x-posts.')->group(function () {
            Route::post('/{xPost}/schedule', [App\Http\Controllers\Admin\XPostController::class, 'schedule'])->name('schedule');
            Route::post('/{xPost}/publish', [App\Http\Controllers\Admin\XPostController::class, 'publish'])->name('publish');
            Route::post('/{xPost}/cancel', [App\Http\Controllers\Admin\XPostController::class, 'cancel'])->name('cancel');
        });
        Route::resource('x-posts', App\Http\Controllers\Admin\XPostController::class)->names('admin.x-posts');
    });
});

// Public movie show route - must be after admin routes to avoid conflicts
Route::get('/movies/{slug}', [App\Http\Controllers\MovieController::class, 'show'])->name('movies.show');

// Redirect old /admin routes to /dashboard
Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

require __DIR__.'/settings.php';
