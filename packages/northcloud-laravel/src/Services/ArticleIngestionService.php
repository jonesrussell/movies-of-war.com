<?php

namespace JonesRussell\NorthcloudLaravel\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JonesRussell\NorthcloudLaravel\Contracts\ArticleModel;
use JonesRussell\NorthcloudLaravel\Contracts\ArticleProcessor;
use JonesRussell\NorthcloudLaravel\Events\ArticleProcessed;
use JonesRussell\NorthcloudLaravel\Models\NewsSource;

class ArticleIngestionService implements ArticleProcessor
{
    public function __construct(
        protected string $articleModelClass,
        protected string $newsSourceModelClass,
        protected string $tagModelClass
    ) {}

    /**
     * Process an incoming article from Redis.
     */
    public function process(array $articleData): ?ArticleModel
    {
        Log::info('Processing incoming article', [
            'external_id' => $articleData['id'] ?? null,
            'title' => $articleData['title'] ?? $articleData['og_title'] ?? null,
        ]);

        try {
            if (! $this->validate($articleData)) {
                Log::warning('Invalid article data: missing required fields', [
                    'data_keys' => array_keys($articleData),
                ]);

                return null;
            }

            $externalId = $articleData['id'];

            if ($this->exists($externalId)) {
                Log::debug('Skipping duplicate article', [
                    'external_id' => $externalId,
                ]);

                return null;
            }

            $sourceId = $this->getOrCreateSource($articleData);
            $metadata = $this->buildMetadata($articleData);
            $title = $articleData['title'] ?? $articleData['og_title'] ?? 'Untitled Article';
            $url = $this->getArticleUrl($articleData);
            $publishedAt = $this->getPublishedDate($articleData);

            $article = $this->articleModelClass::create([
                'news_source_id' => $sourceId,
                'external_id' => $externalId,
                'title' => $title,
                'slug' => Str::slug($title),
                'excerpt' => $articleData['intro'] ?? $articleData['description'] ?? $articleData['og_description'] ?? null,
                'content' => $this->sanitizeContent($articleData['body'] ?? $articleData['raw_text'] ?? null),
                'url' => $url,
                'image_url' => $articleData['og_image'] ?? null,
                'author' => $articleData['author'] ?? null,
                'status' => 'draft',
                'published_at' => $publishedAt,
                'crawled_at' => now(),
                'metadata' => $metadata,
            ]);

            if (! empty($articleData['topics'])) {
                $this->attachTags($article, $articleData['topics']);
            }

            event(new ArticleProcessed($article));

            Log::info('Article processed successfully', [
                'article_id' => $article->id,
                'external_id' => $article->external_id,
                'title' => $article->title,
            ]);

            return $article;
        } catch (\Exception $e) {
            Log::error('Failed to process incoming article', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'external_id' => $articleData['id'] ?? null,
            ]);

            throw $e;
        }
    }

    /**
     * Validate the incoming article data.
     */
    public function validate(array $articleData): bool
    {
        if (! isset($articleData['id'])) {
            return false;
        }

        if (! isset($articleData['title']) && ! isset($articleData['og_title'])) {
            return false;
        }

        return true;
    }

    /**
     * Check if an article already exists by external ID.
     */
    public function exists(string $externalId): bool
    {
        return $this->articleModelClass::where('external_id', $externalId)->exists();
    }

    /**
     * Get or create NewsSource from available data.
     */
    public function getOrCreateSource(array $articleData): int
    {
        $sourceUrl = $articleData['source'] ?? $articleData['og_url'] ?? $articleData['canonical_url'] ?? null;

        if (! $sourceUrl && isset($articleData['publisher']['channel'])) {
            $channel = $articleData['publisher']['channel'];
            $parts = explode(':', $channel);
            if (count($parts) > 1) {
                $sourceName = Str::title($parts[1]);
                $slug = Str::slug($sourceName);
                $placeholderUrl = "https://{$slug}.example.com";

                $source = $this->newsSourceModelClass::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $sourceName,
                        'url' => $placeholderUrl,
                        'is_active' => true,
                        'credibility_score' => $articleData['source_reputation'] ?? null,
                    ]
                );

                if (! $source->wasRecentlyCreated && isset($articleData['source_reputation'])) {
                    $source->update(['credibility_score' => $articleData['source_reputation']]);
                }

                return $source->id;
            }
        }

        if ($sourceUrl) {
            try {
                $parsedUrl = parse_url($sourceUrl);

                if (! isset($parsedUrl['host'])) {
                    Log::warning('Unable to parse source URL', ['url' => $sourceUrl]);

                    return $this->getOrCreateUnknownSource($sourceUrl);
                }

                $domain = $parsedUrl['host'];
                $name = $this->formatDomainName($domain);
                $slug = Str::slug($name);

                $source = $this->newsSourceModelClass::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'url' => $sourceUrl,
                        'is_active' => true,
                        'credibility_score' => $articleData['source_reputation'] ?? null,
                    ]
                );

                if (! $source->wasRecentlyCreated && isset($articleData['source_reputation'])) {
                    $source->update(['credibility_score' => $articleData['source_reputation']]);
                }

                return $source->id;
            } catch (\Exception $e) {
                Log::error('Failed to create news source', [
                    'url' => $sourceUrl,
                    'error' => $e->getMessage(),
                ]);

                return $this->getOrCreateUnknownSource($sourceUrl);
            }
        }

        return $this->getOrCreateUnknownSource(null);
    }

    /**
     * Format domain name for display.
     */
    protected function formatDomainName(string $domain): string
    {
        $domain = preg_replace('/^www\./', '', $domain);
        $parts = explode('.', $domain);
        $name = $parts[0] ?? $domain;

        return Str::title($name);
    }

    /**
     * Get or create unknown source fallback.
     */
    protected function getOrCreateUnknownSource(?string $url): int
    {
        $source = $this->newsSourceModelClass::firstOrCreate(
            ['slug' => 'unknown'],
            [
                'name' => 'Unknown Source',
                'url' => $url ?? 'https://unknown.example.com',
                'is_active' => true,
            ]
        );

        return $source->id;
    }

    /**
     * Get article URL from available fields.
     */
    protected function getArticleUrl(array $articleData): string
    {
        $url = $articleData['canonical_url'] ?? $articleData['og_url'] ?? null;

        if (! empty($url)) {
            return $url;
        }

        if (! empty($articleData['source'])) {
            return $articleData['source'];
        }

        $externalId = $articleData['id'];
        $publisherChannel = $articleData['publisher']['channel'] ?? 'articles';
        $channelSlug = str_replace(':', '-', $publisherChannel);

        return "https://{$channelSlug}.example.com/{$externalId}";
    }

    /**
     * Get published date from available fields.
     */
    protected function getPublishedDate(array $articleData): Carbon
    {
        if (isset($articleData['published_date'])) {
            try {
                $date = Carbon::parse($articleData['published_date']);
                if ($date->year > 1970) {
                    return $date;
                }
            } catch (\Exception $e) {
                Log::debug('Invalid published_date, trying alternatives', [
                    'published_date' => $articleData['published_date'],
                ]);
            }
        }

        if (isset($articleData['publisher']['published_at'])) {
            try {
                return Carbon::parse($articleData['publisher']['published_at']);
            } catch (\Exception $e) {
                Log::debug('Invalid publisher.published_at, using current time', [
                    'published_at' => $articleData['publisher']['published_at'],
                ]);
            }
        }

        return now();
    }

    /**
     * Build metadata array from publisher fields.
     */
    public function buildMetadata(array $articleData): array
    {
        $metadata = [];

        if (isset($articleData['publisher']) && is_array($articleData['publisher'])) {
            $metadata['publisher'] = [
                'route_id' => $articleData['publisher']['route_id'] ?? null,
                'published_at' => $articleData['publisher']['published_at'] ?? null,
                'channel' => $articleData['publisher']['channel'] ?? null,
            ];
        }

        $metadataFields = [
            'quality_score',
            'source_reputation',
            'confidence',
            'is_crime_related',
            'content_type',
            'word_count',
            'category',
            'section',
            'og_title',
            'og_description',
            'og_url',
        ];

        foreach ($metadataFields as $field) {
            if (isset($articleData[$field])) {
                $metadata[$field] = $articleData[$field];
            }
        }

        if (isset($articleData['keywords']) && is_array($articleData['keywords'])) {
            $metadata['keywords'] = $articleData['keywords'];
        }

        return $metadata;
    }

    /**
     * Sanitize HTML content while preserving basic formatting.
     */
    public function sanitizeContent(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        $allowedTags = config('northcloud.content.allowed_tags', [
            'p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        ]);

        $allowedTagsString = '<'.implode('><', $allowedTags).'>';

        return strip_tags($content, $allowedTagsString);
    }

    /**
     * Attach tags to article from topics array.
     */
    public function attachTags(ArticleModel $article, array $topics): void
    {
        $tagIds = [];
        $defaultTagType = config('northcloud.tags.default_type', 'article_category');

        foreach ($topics as $topic) {
            $tagSlug = Str::slug($topic);

            $tag = $this->tagModelClass::firstOrCreate(
                ['slug' => $tagSlug],
                [
                    'name' => Str::title(str_replace('-', ' ', $tagSlug)),
                    'type' => $defaultTagType,
                ]
            );

            $tagIds[] = $tag->id;
        }

        if (! empty($tagIds)) {
            $article->tags()->sync($tagIds);
        }
    }
}
