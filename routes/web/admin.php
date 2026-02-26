<?php

use App\Http\Controllers\Admin\FeaturedHistoryController;
use App\Http\Controllers\Admin\FeaturedQueueController;
use App\Http\Controllers\Admin\FeaturedSlotController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('northcloud-admin')->group(function () {
    // TMDB movie management
    Route::get('/dashboard/tmdb/imports', [DashboardController::class, 'tmdbImports'])->name('dashboard.tmdb.imports');
    Route::get('/dashboard/tmdb/search', [DashboardController::class, 'tmdbSearch'])->name('dashboard.tmdb.search');
    Route::get('/dashboard/tmdb/preview/{tmdbId}', [DashboardController::class, 'tmdbPreview'])->name('dashboard.tmdb.preview');
    Route::post('/tmdb/search', [DashboardController::class, 'performTmdbSearch'])->name('tmdb.search');
    Route::post('/tmdb/import', [DashboardController::class, 'importTmdbMovies'])->name('tmdb.import');
    Route::post('/tmdb/import-single', [DashboardController::class, 'importSingleTmdbMovie'])->name('tmdb.import-single');

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

    // Featured slot queue
    Route::get('/dashboard/featured-queue', [FeaturedQueueController::class, 'index'])->name('dashboard.featured-queue');
    Route::post('/dashboard/featured-queue', [FeaturedQueueController::class, 'store'])->name('dashboard.featured-queue.store');
    Route::delete('/dashboard/featured-queue/{featuredSlotQueue}', [FeaturedQueueController::class, 'destroy'])->name('dashboard.featured-queue.destroy');
    Route::post('/dashboard/featured-queue/refill', [FeaturedQueueController::class, 'refill'])->name('dashboard.featured-queue.refill');

    // Featured slot history
    Route::get('/dashboard/featured-history', [FeaturedHistoryController::class, 'index'])->name('dashboard.featured-history');

    // Reviews (moderation)
    Route::get('/dashboard/reviews', [AdminReviewController::class, 'index'])->name('dashboard.reviews');
    Route::delete('/dashboard/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('admin.reviews.destroy');
    Route::post('/dashboard/reviews/{review}/toggle-published', [AdminReviewController::class, 'togglePublished'])->name('admin.reviews.toggle-published');
});
