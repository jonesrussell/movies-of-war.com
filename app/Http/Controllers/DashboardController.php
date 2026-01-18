<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportTmdbMoviesRequest;
use App\Jobs\ImportTmdbMoviesJob;
use App\Models\Movie;
use App\Models\Tag;
use App\Models\XPost;
use App\Models\XTrendKeyword;
use App\Services\TMDBService;
use App\Services\XAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected XAnalyticsService $analyticsService,
        protected TMDBService $tmdbService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Core stats
        $publishedMoviesCount = Movie::published()->count();
        $draftMoviesCount = Movie::draft()->count();
        $archivedMoviesCount = Movie::archived()->count();

        $stats = [
            'movies' => $publishedMoviesCount,
            'drafts' => $draftMoviesCount,
            'archived' => $archivedMoviesCount,
            'tags' => Tag::count(),
            'activeFeatures' => \App\Models\FeaturedSlot::active()->count(),
            'tmdbDrafts' => $user->is_admin ? $draftMoviesCount : 0,
        ];

        // Recent movies for the dashboard (latest 6 published)
        $recentMovies = Movie::query()
            ->published()
            ->with('tags')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        // User's watchlist count
        $watchlistCount = $user->watchlist()->count();

        // Add X API stats for admins
        $xStats = null;
        if ($user->is_admin) {
            $xPerformanceReport = $this->analyticsService->getPerformanceReport(now()->subDays(30), now());

            $xStats = [
                'published_posts' => XPost::published()->count(),
                'scheduled_posts' => XPost::scheduled()->count(),
                'failed_posts' => XPost::failed()->count(),
                'total_impressions' => $xPerformanceReport['total_impressions'] ?? 0,
                'active_keywords' => XTrendKeyword::active()->count(),
                'engagement_rate' => $xPerformanceReport['average_engagement_rate'] ?? 0,
            ];

            // Keep legacy stats for backwards compatibility
            $stats['x_published_posts'] = $xStats['published_posts'];
            $stats['x_scheduled_posts'] = $xStats['scheduled_posts'];
            $stats['x_failed_posts'] = $xStats['failed_posts'];
            $stats['x_total_impressions'] = $xStats['total_impressions'];
            $stats['x_active_keywords'] = $xStats['active_keywords'];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentMovies' => $recentMovies,
            'watchlistCount' => $watchlistCount,
            'xStats' => $xStats,
        ]);
    }

    public function tmdbImports(Request $request): Response
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $query = Movie::query()->draft()->with('tags')->latest();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tmdbDrafts = $query->paginate(24)->withQueryString();

        return Inertia::render('Dashboard/TmdbImports', [
            'tmdbDrafts' => $tmdbDrafts,
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function tmdbSearch(): Response
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        return Inertia::render('Dashboard/TmdbSearch', [
            'searchResults' => [],
            'query' => '',
        ]);
    }

    public function performTmdbSearch(Request $request): Response
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

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
        if (! auth()->user()->is_admin) {
            abort(403);
        }

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

        // Create movie
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
            'status' => Movie::STATUS_DRAFT,
        ]);

        // Attach genre tags
        if (! empty($movieData['genres'])) {
            foreach ($movieData['genres'] as $genre) {
                $tag = Tag::firstOrCreate(
                    ['name' => $genre['name'], 'type' => 'genre'],
                    ['name' => $genre['name'], 'type' => 'genre']
                );
                $movie->tags()->attach($tag);
            }
        }

        return redirect()->route('dashboard.tmdb.search')->with('success', "'{$movie->title}' has been imported as a draft.");
    }

    public function publishMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_PUBLISHED;
        $movie->save();

        return redirect()->route('dashboard.tmdb.imports')->with('success', 'Movie published successfully.');
    }

    public function archiveMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_ARCHIVED;
        $movie->save();

        return redirect()->route('dashboard.tmdb.imports')->with('success', 'Movie archived successfully.');
    }

    public function unpublishMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_DRAFT;
        $movie->save();

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
