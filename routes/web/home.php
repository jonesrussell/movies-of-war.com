<?php

use App\Http\Resources\MovieResource;
use App\Http\Resources\ReviewResource;
use App\Models\FeaturedSlot;
use App\Models\Movie;
use App\Services\CuratorReviewService;
use App\Support\UserReviewEnrichment;
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

    $pickOfWeekMovie = $pickOfWeekSlot?->movie;
    $pickOfWeekReview = null;
    if ($pickOfWeekMovie) {
        $curatorReviewService = app(CuratorReviewService::class);
        $fsReview = $curatorReviewService->forMovie($pickOfWeekMovie->slug);

        if ($fsReview !== null) {
            $pickOfWeekReview = [
                'rating' => $fsReview->rating,
                'content_excerpt' => $fsReview->contentExcerpt,
            ];
        } else {
            $curatorUserId = config('app.curator_user_id');
            $curatorReview = null;
            if ($curatorUserId > 0) {
                $curatorReview = $pickOfWeekMovie->reviews()
                    ->where('user_id', $curatorUserId)
                    ->published()
                    ->first();
            }
            if ($curatorReview === null) {
                $curatorReview = $pickOfWeekMovie->reviews()
                    ->published()
                    ->orderByDesc('created_at')
                    ->first();
            }
            if ($curatorReview !== null) {
                $pickOfWeekReview = (new ReviewResource($curatorReview))->resolve(request());
            }
        }
    }

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

    $allMovies = collect()
        ->push($heroSlot?->movie)
        ->push($pickOfWeekMovie)
        ->merge($latestMovies)
        ->merge($upcomingMovies)
        ->filter()
        ->unique('id')
        ->values();
    $userReviewMap = UserReviewEnrichment::userReviewMapForMovies($allMovies);

    $pickOfWeekUserReview = $pickOfWeekMovie && isset($userReviewMap[$pickOfWeekMovie->id])
        ? $userReviewMap[$pickOfWeekMovie->id]
        : null;

    $heroMovie = $heroSlot?->movie;
    $heroMoviePayload = null;
    if ($heroMovie) {
        $heroMoviePayload = (new MovieResource($heroMovie))->resolve(request());
        if (isset($userReviewMap[$heroMovie->id])) {
            $heroMoviePayload['user_review'] = $userReviewMap[$heroMovie->id];
        }
    }

    $latestPayload = $latestMovies->map(function (Movie $movie) use ($userReviewMap) {
        $arr = (new MovieResource($movie))->resolve(request());
        if (isset($userReviewMap[$movie->id])) {
            $arr['user_review'] = $userReviewMap[$movie->id];
        }

        return $arr;
    })->values()->all();

    $upcomingPayload = $upcomingMovies->map(function (Movie $movie) use ($userReviewMap) {
        $arr = (new MovieResource($movie))->resolve(request());
        if (isset($userReviewMap[$movie->id])) {
            $arr['user_review'] = $userReviewMap[$movie->id];
        }

        return $arr;
    })->values()->all();

    $pickOfWeekMoviePayload = $pickOfWeekMovie
        ? (new MovieResource($pickOfWeekMovie))->resolve(request()) + (
            isset($userReviewMap[$pickOfWeekMovie->id])
                ? ['user_review' => $userReviewMap[$pickOfWeekMovie->id]]
                : []
        )
        : null;

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
        'heroMovie' => $heroMoviePayload,
        'pickOfWeekMovie' => $pickOfWeekMoviePayload,
        'pickOfWeekReview' => $pickOfWeekReview,
        'pickOfWeekUserReview' => $pickOfWeekUserReview,
        'latestMovies' => $latestPayload,
        'upcomingMovies' => $upcomingPayload,
        'totalPublishedCount' => Movie::query()->published()->count(),
    ]);
})->name('home');
