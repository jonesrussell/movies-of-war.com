<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Movie;
use App\Models\WarArticle;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Events\ArticleProcessed;

class LinkArticlesToMovies
{
    public function handle(ArticleProcessed $event): void
    {
        /** @var WarArticle $article */
        $article = $event->article;
        $keywords = $this->extractKeywords($article);

        $movies = Movie::query()
            ->published()
            ->where(function ($query) use ($article, $keywords): void {
                foreach ($keywords as $keyword) {
                    $query->orWhere('title', 'like', "%{$keyword}%");
                }

                if ($article->tags()->exists()) {
                    $tagSlugs = $article->tags->pluck('slug')->toArray();
                    $query->orWhereHas('tags', function ($tagQuery) use ($tagSlugs): void {
                        $tagQuery->whereIn('slug', $tagSlugs);
                    });
                }
            })
            ->get();

        $threshold = (float) config('northcloud.linking.threshold', 0.3);
        $movieData = [];

        foreach ($movies as $movie) {
            $confidence = $this->calculateConfidence($article, $movie, $keywords);

            if ($confidence > $threshold) {
                $movieData[$movie->id] = ['confidence' => $confidence];
            }
        }

        if ($movieData !== []) {
            $article->movies()->sync($movieData);
        }
    }

    /**
     * @return list<string>
     */
    protected function extractKeywords(WarArticle $article): array
    {
        $text = $article->title.' '.$article->content;
        $words = str_word_count(strtolower($text), 1);
        $minLength = (int) config('northcloud.linking.min_keyword_length', 3);

        $stopWords = ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can',
            'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how',
            'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its',
            'let', 'put', 'say', 'she', 'too', 'use'];

        return array_values(array_filter($words, function ($word) use ($stopWords, $minLength): bool {
            return strlen($word) >= $minLength && ! in_array($word, $stopWords, true);
        }));
    }

    protected function calculateConfidence(WarArticle $article, Movie $movie, array $keywords): float
    {
        $confidence = 0.0;
        $titleWeight = (float) config('northcloud.linking.weights.title_match', 0.5);
        $tagWeight = (float) config('northcloud.linking.weights.tag_overlap', 0.3);
        $metadataWeight = (float) config('northcloud.linking.weights.metadata_match', 0.2);

        // Title match
        $movieTitleWords = explode(' ', strtolower($movie->title));
        $matchingWords = array_intersect($keywords, $movieTitleWords);
        if (count($matchingWords) > 0) {
            $confidence += $titleWeight * (count($matchingWords) / count($movieTitleWords));
        }

        // Tag overlap
        if ($article->tags()->exists() && $movie->tags()->exists()) {
            $articleTagSlugs = $article->tags->pluck('slug')->toArray();
            $movieTagSlugs = $movie->tags->pluck('slug')->toArray();
            $sharedTags = array_intersect($articleTagSlugs, $movieTagSlugs);

            if (count($sharedTags) > 0) {
                $confidence += $tagWeight * (count($sharedTags) / count($movieTagSlugs));
            }
        }

        // War era / metadata match
        if (isset($article->war_era) && isset($movie->conflict)) {
            if (Str::contains(strtolower($movie->conflict), strtolower($article->war_era))) {
                $confidence += $metadataWeight;
            }
        }

        return min($confidence, 1.0);
    }
}
