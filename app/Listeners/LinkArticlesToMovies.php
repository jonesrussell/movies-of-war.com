<?php

namespace App\Listeners;

use App\Models\Movie;
use Illuminate\Support\Str;
use JonesRussell\LaravelRedisArticles\Events\ArticleProcessed;

class LinkArticlesToMovies
{
    public function handle(ArticleProcessed $event): void
    {
        $article = $event->article;

        // Extract keywords from article title and content
        $keywords = $this->extractKeywords($article);

        // Find movies that match article keywords/tags
        $movies = Movie::query()
            ->published()
            ->where(function ($query) use ($article, $keywords) {
                // Match by movie title in article content/title
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', "%{$keyword}%");
                }

                // Match by shared tags
                if ($article->tags()->exists()) {
                    $tagSlugs = $article->tags->pluck('slug')->toArray();
                    $query->orWhereHas('tags', function ($tagQuery) use ($tagSlugs) {
                        $tagQuery->whereIn('slug', $tagSlugs);
                    });
                }
            })
            ->get();

        // Attach movies with confidence scores
        $movieData = [];
        foreach ($movies as $movie) {
            $confidence = $this->calculateConfidence($article, $movie, $keywords);

            if ($confidence > 0.3) { // Only link if confidence > 30%
                $movieData[$movie->id] = ['confidence' => $confidence];
            }
        }

        if (! empty($movieData)) {
            $article->movies()->sync($movieData);
        }
    }

    protected function extractKeywords($article): array
    {
        $text = $article->title.' '.$article->content;

        // Remove common words and extract meaningful keywords (3+ chars)
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use'];

        return array_values(array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) >= 3 && ! in_array($word, $stopWords);
        }));
    }

    protected function calculateConfidence($article, Movie $movie, array $keywords): float
    {
        $confidence = 0.0;

        // Title match (highest weight)
        $movieTitleWords = explode(' ', strtolower($movie->title));
        $matchingWords = array_intersect($keywords, $movieTitleWords);
        if (count($matchingWords) > 0) {
            $confidence += 0.5 * (count($matchingWords) / count($movieTitleWords));
        }

        // Tag overlap
        if ($article->tags()->exists() && $movie->tags()->exists()) {
            $articleTagSlugs = $article->tags->pluck('slug')->toArray();
            $movieTagSlugs = $movie->tags->pluck('slug')->toArray();
            $sharedTags = array_intersect($articleTagSlugs, $movieTagSlugs);

            if (count($sharedTags) > 0) {
                $confidence += 0.3 * (count($sharedTags) / count($movieTagSlugs));
            }
        }

        // War era match
        if (isset($article->war_era) && isset($movie->conflict)) {
            if (Str::contains(strtolower($movie->conflict), strtolower($article->war_era))) {
                $confidence += 0.2;
            }
        }

        return min($confidence, 1.0);
    }
}
