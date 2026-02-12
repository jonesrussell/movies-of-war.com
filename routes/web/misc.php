<?php

use Illuminate\Support\Facades\Route;

Route::get('/admin', function () {
    return redirect()->route('dashboard');
})->middleware('auth');
