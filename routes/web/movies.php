<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/movies', [MovieController::class, 'index'])->name('movies.index');
Route::get('/movies/{movie:slug}/reviews', [ReviewController::class, 'index'])->name('movies.reviews.index');
Route::get('/movies/{slug}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
