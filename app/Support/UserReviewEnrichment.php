<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Movie;
use App\Models\Review;

/**
 * Builds a map of movie_id => minimal user review summary for the current user.
 * Used to enrich movie payloads with "Your rating" and "Your review" link without
 * changing MovieResource or over-fetching.
 */
final class UserReviewEnrichment
{
    /**
     * Returns movie_id => [id, rating, movie => [slug]] for the authenticated user's
     * published reviews. Returns empty array when not authenticated.
     *
     * @param  iterable<Movie>  $movies
     * @return array<int, array{id: int, rating: float, movie: array{slug: string}}>
     */
    public static function userReviewMapForMovies(iterable $movies): array
    {
        if (! auth()->check()) {
            return [];
        }

        $ids = collect($movies)
            ->filter(fn ($m) => $m instanceof Movie && $m->id !== null)
            ->map(fn (Movie $m) => $m->id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        $reviews = Review::query()
            ->where('user_id', auth()->id())
            ->published()
            ->whereIn('movie_id', $ids)
            ->with('movie:id,slug')
            ->get();

        $map = [];
        foreach ($reviews as $review) {
            if ($review->relationLoaded('movie') && $review->movie !== null) {
                $map[$review->movie_id] = [
                    'id' => $review->id,
                    'rating' => (float) $review->rating,
                    'movie' => [
                        'slug' => $review->movie->slug,
                    ],
                ];
            }
        }

        return $map;
    }
}
