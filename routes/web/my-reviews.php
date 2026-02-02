<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/my-reviews', [ReviewController::class, 'myReviews'])
    ->name('my-reviews.index');
