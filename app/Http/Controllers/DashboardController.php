<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\Tmdb\TmdbMovieData;
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

        $tmdbId = (int) $request->input('tmdb_id');

        if (Movie::where('tmdb_id', $tmdbId)->exists()) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'This movie has already been imported.');
        }

        $dto = $this->tmdbService->getMovieDetailsAsDto($tmdbId);

        if ($dto === null) {
            return redirect()->route('dashboard.tmdb.search')->with('error', 'Could not fetch movie details from TMDB.');
        }

        $posterPath = null;
        $posterUrl = null;
        if ($dto->posterPath) {
            $posterPath = $this->tmdbService->downloadPoster($dto->posterPath);
            $posterUrl = $this->tmdbService->getPosterUrl($dto->posterPath);
        }

        $slug = $this->resolveUniqueSlugForImport($dto);

        $attributes = array_merge($dto->toMovieAttributes(), [
            'slug' => $slug,
            'poster_path' => $posterPath,
            'poster_url' => $posterUrl,
            'trailer_url' => $dto->getTrailerUrl(),
            'status' => MovieStatus::Draft,
            'tmdb_last_synced_at' => now(),
        ]);

        $movie = Movie::create($attributes);

        $this->syncTagsFromTmdbDto($movie, $dto);

        return redirect()->route('dashboard.tmdb.search')->with('success', "'{$movie->title}' has been imported as a draft.");
    }

    /**
     * Generate a unique slug for a movie being imported from TMDB.
     */
    private function resolveUniqueSlugForImport(TmdbMovieData $dto): string
    {
        $baseSlug = Str::slug($dto->title);
        $releaseYear = $dto->getReleaseYear();

        if (! Movie::where('slug', $baseSlug)->exists()) {
            return $baseSlug;
        }

        $slugWithYear = $releaseYear ? "{$baseSlug}-{$releaseYear}" : "{$baseSlug}-{$dto->id}";
        if (! Movie::where('slug', $slugWithYear)->exists()) {
            return $slugWithYear;
        }

        return "{$baseSlug}-{$dto->id}";
    }

    /**
     * Sync movie tags from TMDB DTO (genres + era from keywords).
     */
    private function syncTagsFromTmdbDto(Movie $movie, TmdbMovieData $dto): void
    {
        $tagIds = collect();

        foreach ($dto->genres as $genre) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($genre->name)],
                ['name' => $genre->name, 'type' => TagType::Genre]
            );
            $tagIds->push($tag->id);
        }

        foreach ($dto->keywords as $keyword) {
            $matchedEra = $keyword->matchEra();
            if ($matchedEra !== null) {
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($matchedEra)],
                    ['name' => $matchedEra, 'type' => TagType::Era]
                );
                $tagIds->push($tag->id);
            }
        }

        $movie->tags()->sync($tagIds->unique()->values()->all());
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
