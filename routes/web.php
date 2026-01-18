<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $heroSlot = \App\Models\FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('hero')
        ->first();

    $pickOfWeekSlot = \App\Models\FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('pick_of_week')
        ->first();

    $latestMovies = \App\Models\Movie::query()
        ->published()
        ->released()
        ->with('tags')
        ->latest('release_date')
        ->limit(12)
        ->get();

    $upcomingMovies = \App\Models\Movie::query()
        ->published()
        ->upcoming()
        ->with('tags')
        ->oldest('release_date')
        ->limit(12)
        ->get();

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'heroMovie' => $heroSlot?->movie,
        'pickOfWeekMovie' => $pickOfWeekSlot?->movie,
        'latestMovies' => $latestMovies,
        'upcomingMovies' => $upcomingMovies,
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
        Route::get('/dashboard/tmdb/imports', [App\Http\Controllers\DashboardController::class, 'tmdbImports'])->name('dashboard.tmdb.imports');
        Route::get('/dashboard/tmdb/search', [App\Http\Controllers\DashboardController::class, 'tmdbSearch'])->name('dashboard.tmdb.search');
        Route::post('/tmdb/search', [App\Http\Controllers\DashboardController::class, 'performTmdbSearch'])->name('tmdb.search');
        Route::post('/tmdb/import', [App\Http\Controllers\DashboardController::class, 'importTmdbMovies'])->name('tmdb.import');
        Route::post('/tmdb/import-single', [App\Http\Controllers\DashboardController::class, 'importSingleTmdbMovie'])->name('tmdb.import-single');
        Route::post('/movies/{movie}/publish', [App\Http\Controllers\DashboardController::class, 'publishMovie'])->name('tmdb.movies.publish');
        Route::post('/movies/{movie}/archive', [App\Http\Controllers\DashboardController::class, 'archiveMovie'])->name('tmdb.movies.archive');

        Route::get('/dashboard/movies', [App\Http\Controllers\Admin\MovieController::class, 'index'])->name('dashboard.movies');
        Route::get('/dashboard/movies/{movie}', [App\Http\Controllers\Admin\MovieController::class, 'show'])->name('dashboard.movies.show');
        Route::post('/movies/{movie}/publish', [App\Http\Controllers\Admin\MovieController::class, 'publish'])->name('admin.movies.publish');
        Route::post('/movies/{movie}/unpublish', [App\Http\Controllers\Admin\MovieController::class, 'unpublish'])->name('admin.movies.unpublish');
        Route::resource('movies', App\Http\Controllers\Admin\MovieController::class)->except(['index', 'show']);
        Route::get('/dashboard/featured-slots', [App\Http\Controllers\Admin\FeaturedSlotController::class, 'index'])->name('dashboard.featured-slots');
        Route::resource('featured-slots', App\Http\Controllers\Admin\FeaturedSlotController::class)->except(['index']);

        // X OAuth 2.0
        Route::get('/x-oauth2/redirect', [App\Http\Controllers\Admin\XOAuth2Controller::class, 'redirect'])->name('admin.x-oauth2.redirect');
        Route::get('/x-oauth2/callback', [App\Http\Controllers\Admin\XOAuth2Controller::class, 'callback'])->name('admin.x-oauth2.callback');

        // X Post Management
        Route::prefix('x-posts')->name('admin.x-posts.')->group(function () {
            Route::post('/{xPost}/schedule', [App\Http\Controllers\Admin\XPostController::class, 'schedule'])->name('schedule');
            Route::post('/{xPost}/publish', [App\Http\Controllers\Admin\XPostController::class, 'publish'])->name('publish');
            Route::post('/{xPost}/cancel', [App\Http\Controllers\Admin\XPostController::class, 'cancel'])->name('cancel');
        });
        Route::resource('x-posts', App\Http\Controllers\Admin\XPostController::class)->names('admin.x-posts');

        // X Analytics
        Route::get('/dashboard/x-analytics', [App\Http\Controllers\Admin\XAnalyticsController::class, 'index'])->name('dashboard.x-analytics');
        Route::get('/x-analytics/{xPost}', [App\Http\Controllers\Admin\XAnalyticsController::class, 'show'])->name('admin.x-analytics.show');
        Route::post('/x-analytics/sync', [App\Http\Controllers\Admin\XAnalyticsController::class, 'sync'])->name('admin.x-analytics.sync');

        // X Trend Monitoring
        Route::get('/dashboard/x-trends', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'index'])->name('dashboard.x-trends');
        Route::post('/x-trends', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'store'])->name('admin.x-trends.store');
        Route::put('/x-trends/{keyword}', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'update'])->name('admin.x-trends.update');
        Route::delete('/x-trends/{keyword}', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'destroy'])->name('admin.x-trends.destroy');
        Route::post('/x-trends/{keyword}/search', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'search'])->name('admin.x-trends.search');
        Route::get('/x-trends/{keyword}/results', [App\Http\Controllers\Admin\XTrendMonitoringController::class, 'results'])->name('admin.x-trends.results');

        // X Auto Replies
        Route::get('/dashboard/x-auto-replies', [App\Http\Controllers\Admin\XAutoReplyController::class, 'index'])->name('dashboard.x-auto-replies');
        Route::post('/x-auto-replies', [App\Http\Controllers\Admin\XAutoReplyController::class, 'store'])->name('admin.x-auto-replies.store');
        Route::put('/x-auto-replies/{rule}', [App\Http\Controllers\Admin\XAutoReplyController::class, 'update'])->name('admin.x-auto-replies.update');
        Route::delete('/x-auto-replies/{rule}', [App\Http\Controllers\Admin\XAutoReplyController::class, 'destroy'])->name('admin.x-auto-replies.destroy');
        Route::post('/x-auto-replies/{rule}/toggle', [App\Http\Controllers\Admin\XAutoReplyController::class, 'toggle'])->name('admin.x-auto-replies.toggle');
        Route::get('/x-auto-replies/{rule}/test', [App\Http\Controllers\Admin\XAutoReplyController::class, 'test'])->name('admin.x-auto-replies.test');

        // X Content Discovery
        Route::get('/dashboard/x-content-discovery', [App\Http\Controllers\Admin\XContentDiscoveryController::class, 'index'])->name('dashboard.x-content-discovery');
        Route::post('/x-content-discovery/discover', [App\Http\Controllers\Admin\XContentDiscoveryController::class, 'discover'])->name('admin.x-content-discovery.discover');
        Route::post('/x-content-discovery', [App\Http\Controllers\Admin\XContentDiscoveryController::class, 'store'])->name('admin.x-content-discovery.store');
        Route::put('/x-content-discovery/{post}', [App\Http\Controllers\Admin\XContentDiscoveryController::class, 'update'])->name('admin.x-content-discovery.update');
        Route::delete('/x-content-discovery/{post}', [App\Http\Controllers\Admin\XContentDiscoveryController::class, 'destroy'])->name('admin.x-content-discovery.destroy');
    });
});

// Public movie show route - must be after admin routes to avoid conflicts
Route::get('/movies/{slug}', [App\Http\Controllers\MovieController::class, 'show'])->name('movies.show');

// Public X feed API endpoint
Route::get('/api/x-feed', function () {
    $posts = \App\Models\XPost::published()
        ->whereNotNull('x_post_id')
        ->orderBy('published_at', 'desc')
        ->limit(5)
        ->get(['id', 'content', 'x_post_id', 'published_at']);

    return response()->json($posts);
})->name('api.x-feed');

// Redirect old /admin routes to /dashboard
Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

require __DIR__.'/settings.php';
