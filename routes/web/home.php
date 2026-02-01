<?php

use App\Models\FeaturedSlot;
use App\Models\Movie;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    $heroSlot = FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('hero')
        ->first();

    $pickOfWeekSlot = FeaturedSlot::query()
        ->with('movie.tags')
        ->active()
        ->slot('pick_of_week')
        ->first();

    $latestMovies = Movie::query()
        ->published()
        ->released()
        ->with('tags')
        ->latest('release_date')
        ->limit(12)
        ->get();

    $upcomingMovies = Movie::query()
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
