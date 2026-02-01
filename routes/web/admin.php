<?php

use App\Http\Controllers\Admin\FeaturedSlotController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\XAnalyticsController;
use App\Http\Controllers\Admin\XAutoReplyController;
use App\Http\Controllers\Admin\XContentDiscoveryController;
use App\Http\Controllers\Admin\XOAuth2Controller;
use App\Http\Controllers\Admin\XPostController;
use App\Http\Controllers\Admin\XSettingsController;
use App\Http\Controllers\Admin\XTrendMonitoringController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin')->group(function () {
    // TMDB movie management
    Route::get('/dashboard/tmdb/imports', [DashboardController::class, 'tmdbImports'])->name('dashboard.tmdb.imports');
    Route::get('/dashboard/tmdb/search', [DashboardController::class, 'tmdbSearch'])->name('dashboard.tmdb.search');
    Route::post('/tmdb/search', [DashboardController::class, 'performTmdbSearch'])->name('tmdb.search');
    Route::post('/tmdb/import', [DashboardController::class, 'importTmdbMovies'])->name('tmdb.import');
    Route::post('/tmdb/import-single', [DashboardController::class, 'importSingleTmdbMovie'])->name('tmdb.import-single');
    Route::post('/movies/{movie}/publish', [DashboardController::class, 'publishMovie'])->name('tmdb.movies.publish');
    Route::post('/movies/{movie}/archive', [DashboardController::class, 'archiveMovie'])->name('tmdb.movies.archive');

    // Admin movies
    Route::get('/dashboard/movies', [AdminMovieController::class, 'index'])->name('dashboard.movies');
    Route::get('/dashboard/movies/archived', [AdminMovieController::class, 'archived'])->name('dashboard.movies.archived');
    Route::get('/dashboard/movies/{movie}', [AdminMovieController::class, 'show'])->name('dashboard.movies.show');
    Route::post('/movies/{movie}/publish', [AdminMovieController::class, 'publish'])->name('admin.movies.publish');
    Route::post('/movies/{movie}/unpublish', [AdminMovieController::class, 'unpublish'])->name('admin.movies.unpublish');
    Route::post('/movies/{movie}/archive', [AdminMovieController::class, 'archive'])->name('admin.movies.archive');
    Route::resource('movies', AdminMovieController::class)->except(['index', 'show']);

    // Featured slots
    Route::get('/dashboard/featured-slots', [FeaturedSlotController::class, 'index'])->name('dashboard.featured-slots');
    Route::resource('featured-slots', FeaturedSlotController::class)->except(['index']);

    // X OAuth 2.0
    Route::get('/x-oauth2/redirect', [XOAuth2Controller::class, 'redirect'])->name('admin.x-oauth2.redirect');
    Route::get('/x-oauth2/callback', [XOAuth2Controller::class, 'callback'])->name('admin.x-oauth2.callback');

    // X Settings
    Route::get('/dashboard/x-settings', [XSettingsController::class, 'index'])->name('admin.x-settings.index');
    Route::post('/dashboard/x-settings/disconnect', [XSettingsController::class, 'disconnect'])->name('admin.x-settings.disconnect');

    // X Post Management
    Route::prefix('x-posts')->name('admin.x-posts.')->group(function () {
        Route::post('/{xPost}/schedule', [XPostController::class, 'schedule'])->name('schedule');
        Route::post('/{xPost}/publish', [XPostController::class, 'publish'])->name('publish');
        Route::post('/{xPost}/cancel', [XPostController::class, 'cancel'])->name('cancel');
    });
    Route::resource('x-posts', XPostController::class)->names('admin.x-posts');

    // X Analytics
    Route::get('/dashboard/x-analytics', [XAnalyticsController::class, 'index'])->name('dashboard.x-analytics');
    Route::get('/x-analytics/{xPost}', [XAnalyticsController::class, 'show'])->name('admin.x-analytics.show');
    Route::post('/x-analytics/sync', [XAnalyticsController::class, 'sync'])->name('admin.x-analytics.sync');

    // X Trend Monitoring
    Route::get('/dashboard/x-trends', [XTrendMonitoringController::class, 'index'])->name('dashboard.x-trends');
    Route::post('/x-trends', [XTrendMonitoringController::class, 'store'])->name('admin.x-trends.store');
    Route::put('/x-trends/{keyword}', [XTrendMonitoringController::class, 'update'])->name('admin.x-trends.update');
    Route::delete('/x-trends/{keyword}', [XTrendMonitoringController::class, 'destroy'])->name('admin.x-trends.destroy');
    Route::post('/x-trends/{keyword}/search', [XTrendMonitoringController::class, 'search'])->name('admin.x-trends.search');
    Route::get('/x-trends/{keyword}/results', [XTrendMonitoringController::class, 'results'])->name('admin.x-trends.results');

    // X Auto Replies
    Route::get('/dashboard/x-auto-replies', [XAutoReplyController::class, 'index'])->name('dashboard.x-auto-replies');
    Route::post('/x-auto-replies', [XAutoReplyController::class, 'store'])->name('admin.x-auto-replies.store');
    Route::put('/x-auto-replies/{rule}', [XAutoReplyController::class, 'update'])->name('admin.x-auto-replies.update');
    Route::delete('/x-auto-replies/{rule}', [XAutoReplyController::class, 'destroy'])->name('admin.x-auto-replies.destroy');
    Route::post('/x-auto-replies/{rule}/toggle', [XAutoReplyController::class, 'toggle'])->name('admin.x-auto-replies.toggle');
    Route::get('/x-auto-replies/{rule}/test', [XAutoReplyController::class, 'test'])->name('admin.x-auto-replies.test');

    // X Content Discovery
    Route::get('/dashboard/x-content-discovery', [XContentDiscoveryController::class, 'index'])->name('dashboard.x-content-discovery');
    Route::post('/x-content-discovery/discover', [XContentDiscoveryController::class, 'discover'])->name('admin.x-content-discovery.discover');
    Route::post('/x-content-discovery', [XContentDiscoveryController::class, 'store'])->name('admin.x-content-discovery.store');
    Route::put('/x-content-discovery/{post}', [XContentDiscoveryController::class, 'update'])->name('admin.x-content-discovery.update');
    Route::delete('/x-content-discovery/{post}', [XContentDiscoveryController::class, 'destroy'])->name('admin.x-content-discovery.destroy');
});
