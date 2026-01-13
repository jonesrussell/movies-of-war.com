<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovieController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Movie::query()->with('tags');

        // For non-admins, only show published movies
        if (! auth()->check() || ! auth()->user()->is_admin) {
            $query->published();
        }

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%")
                ->orWhere('synopsis', 'like', "%{$search}%");
        }

        if ($year = $request->get('year')) {
            $query->where('release_year', $year);
        }

        if ($country = $request->get('country')) {
            $query->where('country', $country);
        }

        if ($conflict = $request->get('conflict')) {
            $query->where('conflict', $conflict);
        }

        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', function ($q) use ($tag) {
                $q->where('slug', $tag);
            });
        }

        $movies = $query->latest('release_year')
            ->latest('release_date')
            ->paginate(24)
            ->withQueryString();

        $filters = [
            'countries' => Movie::query()->distinct()->pluck('country')->filter()->sort()->values(),
            'conflicts' => Movie::query()->distinct()->pluck('conflict')->filter()->sort()->values(),
            'years' => Movie::query()->distinct()->pluck('release_year')->sort()->reverse()->values(),
            'tags' => Tag::all(),
        ];

        return Inertia::render('Movies/Index', [
            'movies' => $movies,
            'filters' => $filters,
            'queryParams' => $request->only(['search', 'year', 'country', 'conflict', 'tag']),
        ]);
    }

    public function show(string $slug): Response
    {
        $movie = Movie::published()
            ->where('slug', $slug)
            ->with('tags')
            ->firstOrFail();

        if (auth()->check()) {
            $movie->is_watchlisted = auth()->user()
                ->watchlist()
                ->where('movie_id', $movie->id)
                ->exists();
        }

        $relatedMovies = Movie::query()
            ->published()
            ->where('id', '!=', $movie->id)
            ->where(function ($query) use ($movie) {
                $query->where('conflict', $movie->conflict)
                    ->orWhere('country', $movie->country)
                    ->orWhere('release_year', '>=', $movie->release_year - 5)
                    ->where('release_year', '<=', $movie->release_year + 5);
            })
            ->with('tags')
            ->limit(6)
            ->get();

        return Inertia::render('Movies/Show', [
            'movie' => $movie,
            'relatedMovies' => $relatedMovies,
        ]);
    }
}
