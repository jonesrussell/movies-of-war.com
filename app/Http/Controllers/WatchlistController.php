<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\RedirectResponse;

class WatchlistController extends Controller
{
    public function store(Movie $movie): RedirectResponse
    {
        auth()->user()->watchlist()->syncWithoutDetaching([$movie->id]);

        return back()->with('success', 'Added to watchlist');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        auth()->user()->watchlist()->detach($movie->id);

        return back()->with('success', 'Removed from watchlist');
    }
}
