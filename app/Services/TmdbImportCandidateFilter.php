<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

class TmdbImportCandidateFilter
{
    /**
     * Filter TMDB discover results by quality and optional relevance.
     *
     * @param  Collection<int, array<string, mixed>>  $candidates  Discover results with id, title, overview, vote_average, vote_count, release_date
     * @return Collection<int, int> Filtered TMDB IDs
     */
    public function filter(Collection $candidates): Collection
    {
        $minVoteCount = config('tmdb.import.min_vote_count');
        $minVoteAverage = config('tmdb.import.min_vote_average');
        $relevanceKeywords = config('tmdb.import.relevance_keywords', []);
        $relevanceMinScore = config('tmdb.import.relevance_min_score');
        $relevanceTopPercent = config('tmdb.import.relevance_top_percent');

        $filtered = $candidates
            ->filter(function (array $movie) use ($minVoteCount, $minVoteAverage) {
                if ($minVoteCount !== null && ($movie['vote_count'] ?? 0) < $minVoteCount) {
                    return false;
                }

                if ($minVoteAverage !== null && ($movie['vote_average'] ?? 0) < $minVoteAverage) {
                    return false;
                }

                return true;
            });

        if (! empty($relevanceKeywords)) {
            $filtered = $filtered->map(function (array $movie) use ($relevanceKeywords) {
                $movie['relevance_score'] = $this->calculateRelevanceScore($movie, $relevanceKeywords);

                return $movie;
            });

            if ($relevanceMinScore !== null) {
                $filtered = $filtered->filter(fn (array $movie) => ($movie['relevance_score'] ?? 0) >= $relevanceMinScore);
            }

            if ($relevanceTopPercent !== null && $relevanceTopPercent > 0 && $relevanceTopPercent <= 100) {
                $filtered = $filtered->sortByDesc('relevance_score')
                    ->take((int) ceil($filtered->count() * ($relevanceTopPercent / 100)));
            }
        }

        return $filtered->pluck('id')->values();
    }

    /**
     * Calculate relevance score based on keyword matches in title and overview.
     *
     * @param  array<string, mixed>  $movie
     * @param  array<int, string>  $keywords
     */
    protected function calculateRelevanceScore(array $movie, array $keywords): float
    {
        $title = strtolower($movie['title'] ?? '');
        $overview = strtolower($movie['overview'] ?? '');
        $text = $title.' '.$overview;

        $score = 0;

        foreach ($keywords as $keyword) {
            $keyword = strtolower($keyword);
            $titleMatches = substr_count($title, $keyword);
            $overviewMatches = substr_count($overview, $keyword);

            $score += $titleMatches * 3;
            $score += $overviewMatches * 1;
        }

        return $score;
    }
}
