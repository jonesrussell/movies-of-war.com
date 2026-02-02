<?php

use App\Http\Controllers\ReviewCommentController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::post('/movies/{movie:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('movies.reviews.store');
Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

Route::post('/reviews/{review}/comments', [ReviewCommentController::class, 'store'])
    ->middleware('throttle:20,60')
    ->name('reviews.comments.store');
Route::patch('/review-comments/{comment}', [ReviewCommentController::class, 'update'])
    ->name('review-comments.update');
Route::delete('/review-comments/{comment}', [ReviewCommentController::class, 'destroy'])
    ->name('review-comments.destroy');
