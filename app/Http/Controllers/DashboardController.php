<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ImportTmdbMoviesRequest;
use App\Http\Resources\MovieResource;
use App\Http\Resources\ReviewResource;
use App\Jobs\ImportTmdbMoviesJob;
use App\Models\Movie;
use App\Services\DashboardStatsService;
use App\Services\TmdbMovieImportService;
use App\Services\TMDBService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardStatsService $statsService,
        protected TMDBService $tmdbService,
        protected TmdbMovieImportService $tmdbMovieImportService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user->is_admin) {
            $recentReviews = $user->reviews()
                ->with('movie:id,title,slug')
                ->latest()
                ->take(5)
                ->get();

            return Inertia::render('Dashboard', [
                'stats' => $this->statsService->getUserStats($user),
                'recentMovies' => MovieResource::collection(
                    Movie::query()
                        ->published()
                        ->with('tags')
                        ->latest('updated_at')
                        ->limit(6)
                        ->get()
                ),
                'recentReviews' => ReviewResource::collection($recentReviews)->resolve(),
            ]);
        }

        $stats = $this->statsService->getAdminStats();
        $recentMovies = Movie::query()
            ->published()
            ->with('tags')
            ->latest('updated_at')
            ->limit(6)
            ->get();
        $watchlistCount = $user->watchlist()->count();
        $xStats = $this->statsService->getXStats();

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

        $query = Movie::query()->draft()->with('tags');

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $sort = $request->get('sort', 'updated_at_desc');
        if ($sort === 'title_asc') {
            $query->orderBy('title', 'asc');
        } elseif ($sort === 'title_desc') {
            $query->orderBy('title', 'desc');
        } elseif ($sort === 'updated_at_asc') {
            $query->oldest('updated_at');
        } else {
            $query->latest('updated_at');
        }

        $perPage = $this->resolveTmdbImportsPerPage($request);
        $tmdbDrafts = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Dashboard/TmdbImports', [
            'tmdbDrafts' => MovieResource::collection($tmdbDrafts),
            'queryParams' => $this->tmdbImportsQueryParams($request),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tmdbImportsQueryParams(Request $request): array
    {
        $params = $request->only(['search', 'sort', 'per_page']);
        if (array_key_exists('per_page', $params) && $params['per_page'] !== null) {
            $params['per_page'] = (int) $params['per_page'];
        }

        return $params;
    }

    private function resolveTmdbImportsPerPage(Request $request): int
    {
        $allowed = [10, 20, 24, 50, 100];
        $perPage = (int) $request->get('per_page', 24);

        return in_array($perPage, $allowed, true) ? $perPage : 24;
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

        $tmdbId = (int) $request->input('tmdb_id');

        if (Movie::where('tmdb_id', $tmdbId)->exists()) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'This movie has already been imported.');
        }

        $result = $this->tmdbMovieImportService->import($tmdbId);

        if ($result === null) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'Could not fetch movie details from TMDB.');
        }

        return redirect()->route('dashboard.tmdb.search')->with('success', "'{$result['movie']->title}' has been imported as a draft.");
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
