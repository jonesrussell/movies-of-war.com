<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MovieStatus;
use App\Enums\TagType;
use App\Http\Requests\ImportTmdbMoviesRequest;
use App\Http\Resources\MovieResource;
use App\Jobs\ImportTmdbMoviesJob;
use App\Models\Movie;
use App\Models\Tag;
use App\Services\DashboardStatsService;
use App\Services\TMDBService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardStatsService $statsService,
        protected TMDBService $tmdbService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Get stats from cache-optimized service
        $stats = $user->is_admin
            ? $this->statsService->getAdminStats()
            : $this->statsService->getUserStats();

        // Recent movies for the dashboard (latest 6 published)
        $recentMovies = Movie::query()
            ->published()
            ->with('tags')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        // User's watchlist count
        $watchlistCount = $user->watchlist()->count();

        // Get X stats for admins
        $xStats = $user->is_admin
            ? $this->statsService->getXStats()
            : null;

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentMovies' => MovieResource::collection($recentMovies),
            'watchlistCount' => $watchlistCount,
            'xStats' => $xStats,
        ]);
    }

    public function tmdbImports(Request $request): Response
    {
        $this->authorize('create', Movie::class);

        $query = Movie::query()->draft()->with('tags')->latest();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tmdbDrafts = $query->paginate(24)->withQueryString();

        return Inertia::render('Dashboard/TmdbImports', [
            'tmdbDrafts' => MovieResource::collection($tmdbDrafts),
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function tmdbSearch(): Response
    {
        $this->authorize('create', Movie::class);

        return Inertia::render('Dashboard/TmdbSearch', [
            'searchResults' => [],
            'query' => '',
        ]);
    }

    public function performTmdbSearch(Request $request): Response
    {
        $this->authorize('create', Movie::class);

        $query = $request->input('query', '');
        $results = [];

        if (! empty($query)) {
            $results = $this->tmdbService->searchMovies($query);

            // Check which movies are already imported
            $tmdbIds = collect($results)->pluck('id')->toArray();
            $existingMovies = Movie::whereIn('tmdb_id', $tmdbIds)->pluck('tmdb_id')->toArray();

            $results = collect($results)->map(function ($movie) use ($existingMovies) {
                $movie['already_imported'] = in_array($movie['id'], $existingMovies);

                return $movie;
            })->toArray();
        }

        return Inertia::render('Dashboard/TmdbSearch', [
            'searchResults' => $results,
            'query' => $query,
        ]);
    }

    public function importSingleTmdbMovie(Request $request): RedirectResponse
    {
        $this->authorize('create', Movie::class);

        $request->validate([
            'tmdb_id' => 'required|integer',
        ]);

        $tmdbId = $request->input('tmdb_id');

        // Check if already imported
        if (Movie::where('tmdb_id', $tmdbId)->exists()) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'This movie has already been imported.');
        }

        // Get movie details from TMDB
        $movieData = $this->tmdbService->getMovieDetails($tmdbId);

        if (! $movieData) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'Could not fetch movie details from TMDB.');
        }

        // Download poster and get URLs
        $posterPath = null;
        $posterUrl = null;

        if (! empty($movieData['poster_path'])) {
            $posterPath = $this->tmdbService->downloadPoster($movieData['poster_path']);
            $posterUrl = $this->tmdbService->getPosterUrl($movieData['poster_path']);
        }

        // Get trailer URL
        $trailerUrl = $this->tmdbService->getYoutubeTrailerUrl($movieData['videos'] ?? null);

        // Determine if upcoming
        $releaseDate = $movieData['release_date'] ?? null;
        $isUpcoming = $releaseDate && now()->lt($releaseDate);

        // Generate unique slug
        $baseSlug = Str::slug($movieData['title']);
        $slug = $baseSlug;
        $releaseYear = $releaseDate ? (int) substr($releaseDate, 0, 4) : null;

        // If slug exists, append year to make it unique
        if (Movie::where('slug', $slug)->exists()) {
            $slug = $releaseYear ? "{$baseSlug}-{$releaseYear}" : "{$baseSlug}-{$movieData['id']}";

            // If still not unique, append TMDB ID
            if (Movie::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$movieData['id']}";
            }
        }

        // Create movie with MovieStatus enum
        $movie = Movie::create([
            'tmdb_id' => $movieData['id'],
            'title' => $movieData['title'],
            'slug' => $slug,
            'release_year' => $releaseDate ? (int) substr($releaseDate, 0, 4) : null,
            'release_date' => $releaseDate,
            'synopsis' => $movieData['overview'] ?? null,
            'runtime' => $movieData['runtime'] ?? null,
            'poster_path' => $posterPath,
            'poster_url' => $posterUrl,
            'trailer_url' => $trailerUrl,
            'imdb_id' => $movieData['imdb_id'] ?? null,
            'is_upcoming' => $isUpcoming,
            'status' => MovieStatus::Draft,
        ]);

        // Attach genre tags using TagType enum
        if (! empty($movieData['genres'])) {
            foreach ($movieData['genres'] as $genre) {
                $tag = Tag::firstOrCreate(
                    ['name' => $genre['name'], 'type' => TagType::Genre],
                    ['name' => $genre['name'], 'type' => TagType::Genre]
                );
                $movie->tags()->attach($tag);
            }
        }

        return redirect()->route('dashboard.tmdb.search')->with('success', "'{$movie->title}' has been imported as a draft.");
    }

    public function publishMovie(Movie $movie): RedirectResponse
    {
        $this->authorize('publish', $movie);

        $movie->publish();

        return redirect()->route('dashboard.tmdb.imports')->with('success', 'Movie published successfully.');
    }

    public function archiveMovie(Movie $movie): RedirectResponse
    {
        $this->authorize('archive', $movie);

        $movie->archive();

        return redirect()->route('dashboard.tmdb.imports')->with('success', 'Movie archived successfully.');
    }

    public function unpublishMovie(Movie $movie): RedirectResponse
    {
        $this->authorize('unpublish', $movie);

        $movie->unpublish();

        return redirect()->back()->with('success', 'Movie unpublished successfully.');
    }

    public function importTmdbMovies(ImportTmdbMoviesRequest $request): RedirectResponse
    {
        $limit = $request->validated()['limit'] ?? 30;
        $upcoming = $request->boolean('upcoming');

        if (app()->isLocal()) {
            Log::info('TMDB import request received', [
                'input' => $request->all(),
                'validated' => $request->validated(),
                'resolved' => [
                    'limit' => $limit,
                    'upcoming' => $upcoming,
                ],
            ]);
        }

        ImportTmdbMoviesJob::dispatch($limit, $upcoming);

        return redirect()->route('dashboard.tmdb.imports')->with('success', "TMDB import job queued. Importing up to {$limit} movies...");
    }
}
