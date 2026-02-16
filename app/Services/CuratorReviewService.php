<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\CuratorReview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Reads curator review markdown files, parses frontmatter, renders HTML,
 * processes shortcodes, and caches the result.
 */
final class CuratorReviewService
{
    private readonly MarkdownConverter $converter;

    private readonly string $reviewsPath;

    public function __construct(
        private readonly ShortcodeProcessor $shortcodeProcessor,
        private readonly MarkdownRenderer $markdownRenderer,
    ) {
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new FrontMatterExtension);

        $this->converter = new MarkdownConverter($environment);
        $this->reviewsPath = resource_path('reviews');
    }

    /**
     * Get the curator review for a movie slug, or null if none exists.
     *
     * Checks cache first so deleted files can still be served from cache.
     * When the file exists, compares mtime to bust stale cache entries.
     */
    public function forMovie(string $slug): ?CuratorReview
    {
        $cacheKey = "curator_review:{$slug}";
        $filePath = $this->resolveFilePath($slug);

        /** @var array{mtime: int, review: CuratorReview}|null $cached */
        $cached = Cache::get($cacheKey);

        // File is gone but we have a cached version — serve it
        if ($filePath === null) {
            return $cached['review'] ?? null;
        }

        $mtime = filemtime($filePath);

        // Cache hit with matching mtime — serve from cache
        if ($cached !== null && $cached['mtime'] === $mtime) {
            return $cached['review'];
        }

        // Cache miss or stale — parse fresh
        $review = $this->parse($filePath, $slug);

        if ($review !== null) {
            Cache::put($cacheKey, ['mtime' => $mtime, 'review' => $review]);
        }

        return $review;
    }

    private function resolveFilePath(string $slug): ?string
    {
        $path = $this->reviewsPath.'/'.$slug.'.md';

        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    private function parse(string $filePath, string $slug): ?CuratorReview
    {
        $markdown = file_get_contents($filePath);

        if ($markdown === false) {
            return null;
        }

        $result = $this->converter->convert($markdown);

        if (! $result instanceof RenderedContentWithFrontMatter) {
            Log::warning("Curator review missing frontmatter: {$filePath}");

            return null;
        }

        /** @var array<string, mixed> $frontmatter */
        $frontmatter = $result->getFrontMatter();

        $title = $frontmatter['title'] ?? null;
        if (! is_string($title) || $title === '') {
            Log::warning("Curator review missing required 'title' in frontmatter: {$filePath}");

            return null;
        }

        $html = $result->getContent();
        $html = $this->shortcodeProcessor->process($html);

        $excerpt = $this->markdownRenderer->toExcerpt(
            strip_tags($html),
        );

        $resolvedSlug = isset($frontmatter['slug']) && is_string($frontmatter['slug'])
            ? $frontmatter['slug']
            : $slug;

        $starring = $frontmatter['starring'] ?? [];
        if (! is_array($starring)) {
            $starring = [];
        }

        return new CuratorReview(
            title: $title,
            year: (int) ($frontmatter['year'] ?? 0),
            rating: (float) ($frontmatter['rating'] ?? 0),
            director: isset($frontmatter['director']) && is_string($frontmatter['director'])
                ? $frontmatter['director']
                : null,
            starring: array_values(array_filter($starring, 'is_string')),
            runtime: isset($frontmatter['runtime']) ? (int) $frontmatter['runtime'] : null,
            slug: $resolvedSlug,
            hasSpoilers: (bool) ($frontmatter['has_spoilers'] ?? false),
            contentHtml: $html,
            contentExcerpt: $excerpt,
        );
    }
}
