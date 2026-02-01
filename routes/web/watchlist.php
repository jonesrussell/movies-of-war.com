<?php

use App\Http\Controllers\WatchlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/watchlist', function () {
    $movies = auth()->user()->watchlist()->with('tags')->get();

    return Inertia::render('Watchlist/Index', [
        'movies' => $movies,
    ]);
})->name('watchlist.index');

Route::post('/watchlist/{movie}', [WatchlistController::class, 'store'])->name('watchlist.store');
Route::delete('/watchlist/{movie}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');
