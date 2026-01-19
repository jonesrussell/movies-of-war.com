<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Http\Resources\TagResource;
use App\Models\Movie;
use App\Services\MovieFilterService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovieController extends Controller
{
    public function __construct(
        protected MovieFilterService $filterService
    ) {}

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

        // Apply sorting
        $sort = $request->get('sort', 'release_year_desc');
        $query = match ($sort) {
            'release_year_asc' => $query->oldest('release_year')->oldest('release_date'),
            'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'created_at_desc' => $query->latest('created_at'),
            default => $query->latest('release_year')->latest('release_date'),
        };

        $movies = $query->paginate(24)->withQueryString();

        // Add is_watchlisted flag for authenticated users
        $watchlistedIds = [];
        if (auth()->check()) {
            $watchlistedIds = auth()->user()->watchlist()->pluck('movie_id')->toArray();
        }

        $filterOptions = $this->filterService->getFilterOptions();

        return Inertia::render('Movies/Index', [
            'movies' => MovieResource::collection($movies)->additional([
                'watchlisted_ids' => $watchlistedIds,
            ]),
            'filters' => [
                'countries' => $filterOptions['countries'],
                'conflicts' => $filterOptions['conflicts'],
                'years' => $filterOptions['years'],
                'tags' => TagResource::collection($filterOptions['tags']),
            ],
            'queryParams' => $request->only(['search', 'year', 'country', 'conflict', 'tag', 'sort']),
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
            'movie' => (new MovieResource($movie))->resolve(request()),
            'relatedMovies' => array_values(MovieResource::collection($relatedMovies)->resolve(request())),
        ]);
    }
}
