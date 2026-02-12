<?php

use App\Http\Controllers\Admin\FeaturedSlotController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
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

    // Reviews (moderation)
    Route::get('/dashboard/reviews', [AdminReviewController::class, 'index'])->name('dashboard.reviews');
    Route::delete('/dashboard/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('admin.reviews.destroy');
    Route::post('/dashboard/reviews/{review}/toggle-published', [AdminReviewController::class, 'togglePublished'])->name('admin.reviews.toggle-published');
});
