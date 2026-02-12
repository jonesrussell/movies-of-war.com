<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\TagResource;
use App\Models\Movie;
use App\Services\MovieFilterService;
use App\Support\UserReviewEnrichment;
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

        $userReviewMap = UserReviewEnrichment::userReviewMapForMovies($movies->getCollection());
        $data = $movies->getCollection()->map(function (Movie $movie) use ($userReviewMap) {
            $arr = (new MovieResource($movie))->resolve(request());
            if (isset($userReviewMap[$movie->id])) {
                $arr['user_review'] = $userReviewMap[$movie->id];
            }

            return $arr;
        })->values()->all();

        $paginatorArray = $movies->toArray();
        $moviesPayload = [
            'data' => $data,
            'watchlisted_ids' => $watchlistedIds,
            'links' => [
                'first' => $paginatorArray['first_page_url'] ?? null,
                'last' => $paginatorArray['last_page_url'] ?? null,
                'prev' => $paginatorArray['prev_page_url'] ?? null,
                'next' => $paginatorArray['next_page_url'] ?? null,
            ],
            'meta' => [
                'current_page' => $paginatorArray['current_page'],
                'from' => $paginatorArray['from'] ?? null,
                'last_page' => $paginatorArray['last_page'],
                'links' => $paginatorArray['links'] ?? [],
                'path' => $paginatorArray['path'],
                'per_page' => $paginatorArray['per_page'],
                'to' => $paginatorArray['to'] ?? null,
                'total' => $paginatorArray['total'],
            ],
        ];

        $filterOptions = $this->filterService->getFilterOptions();

        return Inertia::render('Movies/Index', [
            'movies' => $moviesPayload,
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

        $userReview = null;
        if (auth()->check()) {
            $userReview = $movie->reviews()
                ->published()
                ->where('user_id', auth()->id())
                ->with('user')
                ->first();
        }

        $curatorUserId = config('app.curator_user_id');
        $curatorReview = null;
        if ($curatorUserId > 0) {
            $curatorReview = $movie->reviews()
                ->published()
                ->where('user_id', $curatorUserId)
                ->with('user')
                ->first();
        }

        $recentQuery = $movie->reviews()
            ->published()
            ->withoutSpoilers()
            ->with('user')
            ->orderByDesc('created_at');
        if ($curatorReview !== null) {
            $recentQuery->where('id', '!=', $curatorReview->id);
        }
        $recentReviews = $recentQuery->limit(5)->get();

        $reviewsSummary = [
            'user_rating_average' => $movie->user_rating_average,
            'user_rating_count' => $movie->user_rating_count,
        ];

        $reviewsPayload = [
            'summary' => $reviewsSummary,
            'user_review' => $userReview ? (new ReviewResource($userReview))->resolve(request()) : null,
            'recent' => ReviewResource::collection($recentReviews)->resolve(request()),
        ];
        if ($curatorReview !== null) {
            $reviewsPayload['curator_review'] = (new ReviewResource($curatorReview))->resolve(request());
        }

        return Inertia::render('Movies/Show', [
            'movie' => (new MovieResource($movie))->resolve(request()),
            'relatedMovies' => array_values(MovieResource::collection($relatedMovies)->resolve(request())),
            'reviews' => $reviewsPayload,
        ]);
    }
}
