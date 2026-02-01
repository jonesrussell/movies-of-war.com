<?php

use App\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;

Route::get('/people/{slug}', [PersonController::class, 'show'])->name('people.show');
