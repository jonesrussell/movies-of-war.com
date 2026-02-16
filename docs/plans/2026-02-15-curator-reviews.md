# Curator Reviews from Filesystem — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Serve curator editorial reviews from markdown files in `resources/reviews/`, bypassing the database, with custom shortcode support and caching.

**Architecture:** A `CuratorReviewService` reads markdown files, parses YAML frontmatter via `league/commonmark`'s FrontMatter extension, renders HTML with the existing `MarkdownRenderer`, then post-processes shortcodes (`[film:]`, `[spoiler]`, `[quote]`) via a separate `ShortcodeProcessor`. Results are file-cache backed with mtime-based invalidation. The rendered review is passed as an Inertia prop to the movie detail page.

**Tech Stack:** PHP 8.4, Laravel 12, league/commonmark ^2.8 (FrontMatter extension), Pest, Vue 3, Tailwind CSS 4 with @tailwindcss/typography

**Design doc:** `docs/plans/2026-02-15-curator-reviews-design.md`

---

## Task 1: CuratorReview DTO

**Files:**
- Create: `app/Data/CuratorReview.php`
- Test: `tests/Unit/CuratorReviewTest.php`

**Step 1: Write the failing test**

```bash
ddev artisan make:test CuratorReviewTest --pest --unit
```

```php
<?php

declare(strict_types=1);

use App\Data\CuratorReview;

it('creates a curator review from valid data', function () {
    $review = new CuratorReview(
        title: 'Gallipoli',
        year: 1981,
        rating: 3.0,
        director: 'Peter Weir',
        starring: ['Mark Lee', 'Mel Gibson'],
        runtime: 110,
        slug: 'gallipoli-1981',
        hasSpoilers: false,
        contentHtml: '<p>A great film.</p>',
        contentExcerpt: 'A great film.',
    );

    expect($review->title)->toBe('Gallipoli');
    expect($review->rating)->toBe(3.0);
    expect($review->slug)->toBe('gallipoli-1981');
    expect($review->starring)->toBe(['Mark Lee', 'Mel Gibson']);
    expect($review->contentHtml)->toContain('<p>');
});

it('converts to array for Inertia props', function () {
    $review = new CuratorReview(
        title: 'Gallipoli',
        year: 1981,
        rating: 3.0,
        director: 'Peter Weir',
        starring: ['Mark Lee', 'Mel Gibson'],
        runtime: 110,
        slug: 'gallipoli-1981',
        hasSpoilers: false,
        contentHtml: '<p>Review body.</p>',
        contentExcerpt: 'Review body.',
    );

    $array = $review->toArray();

    expect($array)->toHaveKeys([
        'title', 'year', 'rating', 'director', 'starring',
        'runtime', 'slug', 'has_spoilers', 'content_html', 'content_excerpt',
    ]);
    expect($array['has_spoilers'])->toBeFalse();
    expect($array['content_html'])->toBe('<p>Review body.</p>');
});
```

**Step 2: Run test to verify it fails**

```bash
ddev artisan test --compact tests/Unit/CuratorReviewTest.php
```

Expected: FAIL — class `App\Data\CuratorReview` not found.

**Step 3: Write the DTO**

```php
<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Immutable DTO for a filesystem-based curator review.
 *
 * @implements Arrayable<string, mixed>
 */
readonly class CuratorReview implements Arrayable
{
    /**
     * @param  string[]  $starring
     */
    public function __construct(
        public string $title,
        public int $year,
        public float $rating,
        public ?string $director,
        public array $starring,
        public ?int $runtime,
        public string $slug,
        public bool $hasSpoilers,
        public string $contentHtml,
        public string $contentExcerpt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'year' => $this->year,
            'rating' => $this->rating,
            'director' => $this->director,
            'starring' => $this->starring,
            'runtime' => $this->runtime,
            'slug' => $this->slug,
            'has_spoilers' => $this->hasSpoilers,
            'content_html' => $this->contentHtml,
            'content_excerpt' => $this->contentExcerpt,
        ];
    }
}
```

**Step 4: Run test to verify it passes**

```bash
ddev artisan test --compact tests/Unit/CuratorReviewTest.php
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Data/CuratorReview.php tests/Unit/CuratorReviewTest.php
git commit -m "feat: add CuratorReview DTO for filesystem reviews"
```

---

## Task 2: ShortcodeProcessor

**Files:**
- Create: `app/Services/ShortcodeProcessor.php`
- Test: `tests/Unit/ShortcodeProcessorTest.php`

**Step 1: Write the failing tests**

```bash
ddev artisan make:test ShortcodeProcessorTest --pest --unit
```

```php
<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Services\ShortcodeProcessor;

it('expands [quote] shortcode to pull-quote blockquote', function () {
    $processor = app(ShortcodeProcessor::class);
    $html = '<p>[quote]"You\'re always chipper."[/quote]</p>';

    $result = $processor->process($html);

    expect($result)->toContain('<blockquote class="pull-quote">');
    expect($result)->toContain('"You\'re always chipper."');
    expect($result)->not->toContain('[quote]');
});

it('expands [spoiler] shortcode to spoiler-block span', function () {
    $processor = app(ShortcodeProcessor::class);
    $html = '<p>The ending is [spoiler]a massacre[/spoiler].</p>';

    $result = $processor->process($html);

    expect($result)->toContain('<span class="spoiler-block" role="button" tabindex="0" aria-label="Reveal spoiler">a massacre</span>');
    expect($result)->not->toContain('[spoiler]');
});

it('expands [film:slug] shortcode to linked movie title', function () {
    $movie = Movie::factory()->published()->create([
        'slug' => 'saving-private-ryan',
        'title' => 'Saving Private Ryan',
    ]);

    $processor = app(ShortcodeProcessor::class);
    $html = '<p>Similar to [film:saving-private-ryan] in approach.</p>';

    $result = $processor->process($html);

    expect($result)->toContain('href="/movies/saving-private-ryan"');
    expect($result)->toContain('class="film-ref"');
    expect($result)->toContain('>Saving Private Ryan</a>');
    expect($result)->not->toContain('[film:');
});

it('renders missing film reference with fallback markup', function () {
    $processor = app(ShortcodeProcessor::class);
    $html = '<p>See also [film:nonexistent-movie].</p>';

    $result = $processor->process($html);

    expect($result)->toContain('class="film-ref film-ref--missing"');
    expect($result)->toContain('nonexistent-movie');
    expect($result)->not->toContain('[film:');
});

it('handles multiple shortcodes in the same content', function () {
    $movie = Movie::factory()->published()->create([
        'slug' => 'apocalypse-now',
        'title' => 'Apocalypse Now',
    ]);

    $processor = app(ShortcodeProcessor::class);
    $html = '<p>[quote]The horror[/quote] Echoes [film:apocalypse-now] with [spoiler]a dark ending[/spoiler].</p>';

    $result = $processor->process($html);

    expect($result)->toContain('<blockquote class="pull-quote">');
    expect($result)->toContain('Apocalypse Now</a>');
    expect($result)->toContain('<span class="spoiler-block"');
});

it('leaves content without shortcodes unchanged', function () {
    $processor = app(ShortcodeProcessor::class);
    $html = '<p>A perfectly normal paragraph.</p>';

    $result = $processor->process($html);

    expect($result)->toBe('<p>A perfectly normal paragraph.</p>');
});
```

**Step 2: Run tests to verify they fail**

```bash
ddev artisan test --compact tests/Unit/ShortcodeProcessorTest.php
```

Expected: FAIL — class `App\Services\ShortcodeProcessor` not found.

**Step 3: Write the ShortcodeProcessor**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Movie;

/**
 * Post-processes rendered HTML to expand custom shortcodes.
 *
 * Shortcodes are plain text that survive CommonMark rendering and are
 * replaced here with styled HTML markup. This keeps the Markdown renderer's
 * security settings (html_input: strip) untouched.
 */
final class ShortcodeProcessor
{
    /** @var array<string, string> */
    private array $filmTitleCache = [];

    /**
     * Process all shortcodes in the given HTML.
     */
    public function process(string $html): string
    {
        $html = $this->processQuotes($html);
        $html = $this->processSpoilers($html);

        return $this->processFilmRefs($html);
    }

    private function processQuotes(string $html): string
    {
        return (string) preg_replace(
            '/\[quote\](.*?)\[\/quote\]/s',
            '<blockquote class="pull-quote">$1</blockquote>',
            $html,
        );
    }

    private function processSpoilers(string $html): string
    {
        return (string) preg_replace(
            '/\[spoiler\](.*?)\[\/spoiler\]/s',
            '<span class="spoiler-block" role="button" tabindex="0" aria-label="Reveal spoiler">$1</span>',
            $html,
        );
    }

    private function processFilmRefs(string $html): string
    {
        return (string) preg_replace_callback(
            '/\[film:([a-z0-9-]+)\]/',
            fn (array $matches): string => $this->buildFilmLink($matches[1]),
            $html,
        );
    }

    private function buildFilmLink(string $slug): string
    {
        $title = $this->resolveFilmTitle($slug);

        if ($title === null) {
            return '<span class="film-ref film-ref--missing">'
                .e($slug)
                .'</span>';
        }

        return '<a href="/movies/'.e($slug).'" class="film-ref" data-slug="'.e($slug).'">'
            .e($title)
            .'</a>';
    }

    private function resolveFilmTitle(string $slug): ?string
    {
        if (array_key_exists($slug, $this->filmTitleCache)) {
            return $this->filmTitleCache[$slug];
        }

        $title = Movie::query()
            ->where('slug', $slug)
            ->value('title');

        $this->filmTitleCache[$slug] = $title;

        return $title;
    }
}
```

**Step 4: Run tests to verify they pass**

```bash
ddev artisan test --compact tests/Unit/ShortcodeProcessorTest.php
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/ShortcodeProcessor.php tests/Unit/ShortcodeProcessorTest.php
git commit -m "feat: add ShortcodeProcessor for curator review shortcodes"
```

---

## Task 3: CuratorReviewService

**Files:**
- Create: `app/Services/CuratorReviewService.php`
- Test: `tests/Feature/CuratorReviewServiceTest.php`

**Context:** This service orchestrates the full pipeline: read file → parse frontmatter → render markdown → process shortcodes → cache. It uses `league/commonmark`'s `FrontMatterExtension` (already in vendor at `vendor/league/commonmark/src/Extension/FrontMatter/`).

**Step 1: Write the failing tests**

```bash
ddev artisan make:test CuratorReviewServiceTest --pest
```

```php
<?php

declare(strict_types=1);

use App\Data\CuratorReview;
use App\Models\Movie;
use App\Services\CuratorReviewService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('returns null when no review file exists for the slug', function () {
    $service = app(CuratorReviewService::class);

    $result = $service->forMovie('nonexistent-movie');

    expect($result)->toBeNull();
});

it('parses a valid review file and returns a CuratorReview', function () {
    $reviewContent = <<<'MD'
---
title: "Test Movie"
year: 2020
rating: 3
director: "Test Director"
starring: ["Actor One", "Actor Two"]
runtime: 120
---

This is a test review.

[quote]A great quote.[/quote]
MD;

    $path = resource_path('reviews/test-movie-2020.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('test-movie-2020');

    expect($result)->toBeInstanceOf(CuratorReview::class);
    expect($result->title)->toBe('Test Movie');
    expect($result->year)->toBe(2020);
    expect($result->rating)->toBe(3.0);
    expect($result->director)->toBe('Test Director');
    expect($result->starring)->toBe(['Actor One', 'Actor Two']);
    expect($result->runtime)->toBe(120);
    expect($result->slug)->toBe('test-movie-2020');
    expect($result->hasSpoilers)->toBeFalse();
    expect($result->contentHtml)->toContain('<p>This is a test review.</p>');
    expect($result->contentHtml)->toContain('<blockquote class="pull-quote">');
    expect($result->contentExcerpt)->toContain('This is a test review.');

    @unlink($path);
});

it('uses frontmatter slug override when provided', function () {
    $reviewContent = <<<'MD'
---
title: "Custom Slug Movie"
year: 2019
rating: 2.5
slug: custom-slug
---

Review body.
MD;

    $path = resource_path('reviews/custom-slug.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('custom-slug');

    expect($result)->toBeInstanceOf(CuratorReview::class);
    expect($result->slug)->toBe('custom-slug');
    expect($result->title)->toBe('Custom Slug Movie');

    @unlink($path);
});

it('caches the result and serves from cache on second call', function () {
    $reviewContent = <<<'MD'
---
title: "Cached Movie"
year: 2021
rating: 4
---

Cached review.
MD;

    $path = resource_path('reviews/cached-movie.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);

    $first = $service->forMovie('cached-movie');
    expect($first)->toBeInstanceOf(CuratorReview::class);

    // Delete the file — cache should still serve
    unlink($path);

    $second = $service->forMovie('cached-movie');
    expect($second)->toBeInstanceOf(CuratorReview::class);
    expect($second->title)->toBe('Cached Movie');
});

it('busts cache when file modification time changes', function () {
    $path = resource_path('reviews/bust-cache-movie.md');

    file_put_contents($path, <<<'MD'
---
title: "Original Title"
year: 2022
rating: 3
---

Original content.
MD);

    $service = app(CuratorReviewService::class);
    $first = $service->forMovie('bust-cache-movie');
    expect($first->title)->toBe('Original Title');

    // Update the file (touch to change mtime)
    sleep(1);
    file_put_contents($path, <<<'MD'
---
title: "Updated Title"
year: 2022
rating: 3.5
---

Updated content.
MD);

    $second = $service->forMovie('bust-cache-movie');
    expect($second->title)->toBe('Updated Title');

    @unlink($path);
});

it('expands film shortcodes with DB lookups', function () {
    Movie::factory()->published()->create([
        'slug' => 'referenced-film',
        'title' => 'Referenced Film',
    ]);

    $reviewContent = <<<'MD'
---
title: "Referencing Movie"
year: 2023
rating: 3
---

Similar to [film:referenced-film] in style.
MD;

    $path = resource_path('reviews/referencing-movie.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('referencing-movie');

    expect($result->contentHtml)->toContain('Referenced Film</a>');
    expect($result->contentHtml)->toContain('href="/movies/referenced-film"');

    @unlink($path);
});

it('returns null for a review with missing required frontmatter', function () {
    $reviewContent = <<<'MD'
---
year: 2020
---

No title provided.
MD;

    $path = resource_path('reviews/bad-frontmatter.md');
    file_put_contents($path, $reviewContent);

    $service = app(CuratorReviewService::class);
    $result = $service->forMovie('bad-frontmatter');

    expect($result)->toBeNull();

    @unlink($path);
});
```

**Step 2: Run tests to verify they fail**

```bash
ddev artisan test --compact tests/Feature/CuratorReviewServiceTest.php
```

Expected: FAIL — class `App\Services\CuratorReviewService` not found.

**Step 3: Write the CuratorReviewService**

```php
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
     */
    public function forMovie(string $slug): ?CuratorReview
    {
        $filePath = $this->resolveFilePath($slug);

        if ($filePath === null) {
            return null;
        }

        $mtime = filemtime($filePath);
        $cacheKey = "curator_review:{$slug}";

        /** @var array{mtime: int, review: CuratorReview}|null $cached */
        $cached = Cache::get($cacheKey);

        if ($cached !== null && $cached['mtime'] === $mtime) {
            return $cached['review'];
        }

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

        // Check if any file has a frontmatter slug override matching this slug.
        // For performance, only check direct filename match — override slugs
        // are found via the filename they were saved under.
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
```

**Step 4: Run tests to verify they pass**

```bash
ddev artisan test --compact tests/Feature/CuratorReviewServiceTest.php
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Services/CuratorReviewService.php tests/Feature/CuratorReviewServiceTest.php
git commit -m "feat: add CuratorReviewService for filesystem review pipeline"
```

---

## Task 4: Controller Integration

**Files:**
- Modify: `app/Http/Controllers/MovieController.php:119-194` (the `show` method)
- Test: `tests/Feature/MovieShowTest.php` (add new test)

**Step 1: Write the failing test**

Add to the bottom of `tests/Feature/MovieShowTest.php`:

```php
test('movie show page includes filesystem curator review when review file exists', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'fs-review-movie']);

    $reviewContent = <<<'MD'
---
title: "FS Review Movie"
year: 2024
rating: 3.5
director: "Test Director"
starring: ["Actor A"]
runtime: 100
---

A filesystem curator review.
MD;

    $path = resource_path('reviews/fs-review-movie.md');
    file_put_contents($path, $reviewContent);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->has('curatorReview')
        ->where('curatorReview.title', 'FS Review Movie')
        ->where('curatorReview.rating', 3.5)
        ->where('curatorReview.director', 'Test Director')
    );

    @unlink($path);
});

test('movie show page has null curatorReview when no review file exists', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'no-fs-review']);

    $response = $this->get(route('movies.show', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Show')
        ->where('curatorReview', null)
    );
});
```

**Step 2: Run tests to verify they fail**

```bash
ddev artisan test --compact --filter="filesystem curator review"
```

Expected: FAIL — `curatorReview` prop not found.

**Step 3: Modify the MovieController**

In `app/Http/Controllers/MovieController.php`, inject the service and add the prop.

Add import at top:

```php
use App\Services\CuratorReviewService;
```

Update constructor:

```php
public function __construct(
    protected MovieFilterService $filterService,
    protected CuratorReviewService $curatorReviewService,
) {}
```

In the `show()` method, before the `return Inertia::render(...)`, add:

```php
$fsCuratorReview = $this->curatorReviewService->forMovie($movie->slug);
```

Add to the `Inertia::render` props array:

```php
'curatorReview' => $fsCuratorReview?->toArray(),
```

**Step 4: Run tests to verify they pass**

```bash
ddev artisan test --compact tests/Feature/MovieShowTest.php
```

Expected: ALL PASS (existing tests should still pass too).

**Step 5: Commit**

```bash
git add app/Http/Controllers/MovieController.php tests/Feature/MovieShowTest.php
git commit -m "feat: pass filesystem curator review as Inertia prop on movie show page"
```

---

## Task 5: TypeScript Types

**Files:**
- Modify: `resources/js/types/models.ts`

**Step 1: Add the CuratorReview interface**

Add after the `ReviewsSummary` interface (around line 153):

```typescript
export interface FilesystemCuratorReview {
    title: string;
    year: number;
    rating: number;
    director: string | null;
    starring: string[];
    runtime: number | null;
    slug: string;
    has_spoilers: boolean;
    content_html: string;
    content_excerpt: string;
}
```

**Step 2: Run type-check**

```bash
npm run check
```

Expected: PASS (no type errors — the interface is just a definition, not consumed yet).

**Step 3: Commit**

```bash
git add resources/js/types/models.ts
git commit -m "feat: add FilesystemCuratorReview TypeScript interface"
```

---

## Task 6: Shortcode CSS

**Files:**
- Modify: `resources/css/app.css` (add after `.review-content p + p` block, around line 188)

**Step 1: Add the shortcode styles**

Add after the existing `.review-content p + p` rule:

```css
    /* Curator review shortcodes */
    .pull-quote {
        border-left: 3px solid var(--color-destructive, theme(--color-red-500));
        padding: 0.75em 1.25em;
        margin: 1.5em 0;
        font-style: italic;
        font-size: 1.125em;
        color: theme(--color-zinc-200);
        background: theme(--color-zinc-900/50);
        border-radius: 0 0.5rem 0.5rem 0;
    }

    .spoiler-block {
        background: theme(--color-zinc-700);
        color: transparent;
        border-radius: 0.25rem;
        padding: 0 0.25em;
        cursor: pointer;
        transition: color 0.2s, background 0.2s;
        user-select: none;
    }
    .spoiler-block.revealed,
    .spoiler-block:focus {
        color: theme(--color-zinc-300);
        background: transparent;
    }

    .film-ref {
        color: theme(--color-red-500);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.15s;
    }
    .film-ref:hover {
        color: theme(--color-red-400);
        text-decoration: underline;
    }
    .film-ref--missing {
        color: theme(--color-zinc-500);
        font-style: italic;
    }
```

**Step 2: Build to verify CSS compiles**

```bash
npm run build
```

Expected: PASS — no CSS errors.

**Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: add CSS for curator review shortcodes (pull-quote, spoiler, film-ref)"
```

---

## Task 7: CuratorReview Vue Component

**Files:**
- Create: `resources/js/components/reviews/CuratorReview.vue`

**Step 1: Write the component**

```vue
<script setup lang="ts">
import type { FilesystemCuratorReview } from '@/types';

import { StarRating } from '@/components/primitives';
import { onMounted, ref } from 'vue';

interface Props {
    review: FilesystemCuratorReview;
}

defineProps<Props>();

const reviewEl = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!reviewEl.value) return;

    // Activate spoiler blocks on click
    reviewEl.value
        .querySelectorAll<HTMLElement>('.spoiler-block')
        .forEach((el) => {
            el.addEventListener('click', () => {
                el.classList.toggle('revealed');
            });
            el.addEventListener('keydown', (e: KeyboardEvent) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    el.classList.toggle('revealed');
                }
            });
        });
});
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <StarRating :rating="review.rating" :max-stars="4" size="lg" />
            <span class="text-sm font-medium text-zinc-400">
                Curator's Review
            </span>
        </div>

        <div
            v-if="review.has_spoilers"
            class="rounded-md bg-amber-900/20 px-3 py-2 text-sm text-amber-400"
        >
            This review contains spoilers.
        </div>

        <div
            ref="reviewEl"
            class="review-content prose prose-lg leading-relaxed break-words text-zinc-300 prose-neutral dark:prose-invert prose-headings:text-white prose-p:text-zinc-300 prose-a:text-red-500 prose-a:no-underline hover:prose-a:underline prose-strong:text-white prose-hr:border-zinc-700"
            v-html="review.content_html"
        />
    </div>
</template>
```

**Step 2: Build to verify compilation**

```bash
npm run build
```

Expected: PASS

**Step 3: Commit**

```bash
git add resources/js/components/reviews/CuratorReview.vue
git commit -m "feat: add CuratorReview.vue component with spoiler interactivity"
```

---

## Task 8: Integrate into Movies/Show.vue

**Files:**
- Modify: `resources/js/pages/Movies/Show.vue`

**Step 1: Add the imports and props**

In the `<script setup>` block, add the import:

```typescript
import CuratorReviewComponent from '@/components/reviews/CuratorReview.vue';
```

Add to the `Props` and `PageProps` interfaces:

```typescript
curatorReview?: FilesystemCuratorReview | null;
```

Add the import for the type:

```typescript
import type { AppPageProps, FilesystemCuratorReview, Movie, MovieReviewsData } from '@/types';
```

Add a computed:

```typescript
const hasFilesystemCuratorReview = computed(() => Boolean(props.curatorReview));
```

**Step 2: Add the template section**

In the template, inside the `#reviews` PublicSection, add the filesystem curator review block **above** the existing curator review teaser. Insert before the `<!-- One-line summary when only curator review -->` comment:

```vue
<!-- Filesystem curator review (editorial) -->
<div
    v-if="hasFilesystemCuratorReview && curatorReview"
    class="rounded-lg border border-zinc-800 bg-zinc-900/50 p-6"
    role="region"
    aria-label="Editorial review"
>
    <CuratorReviewComponent :review="curatorReview" />
</div>
```

**Step 3: Build and verify**

```bash
npm run build
```

Expected: PASS

**Step 4: Run full test suite for movie show page**

```bash
ddev artisan test --compact tests/Feature/MovieShowTest.php
```

Expected: ALL PASS

**Step 5: Commit**

```bash
git add resources/js/pages/Movies/Show.vue resources/js/types/models.ts
git commit -m "feat: display filesystem curator review on movie detail page"
```

---

## Task 9: Export Types and Final Verification

**Step 1: Ensure types are exported from index**

Check if `resources/js/types/index.ts` re-exports from `models.ts`. If so, verify `FilesystemCuratorReview` is accessible via `@/types`.

**Step 2: Run all checks**

```bash
npm run check
npm run build
ddev artisan test --compact tests/Unit/CuratorReviewTest.php tests/Unit/ShortcodeProcessorTest.php tests/Feature/CuratorReviewServiceTest.php tests/Feature/MovieShowTest.php
```

Expected: ALL PASS

**Step 3: Run Pint**

```bash
ddev exec vendor/bin/pint --dirty
```

**Step 4: Final commit if any formatting fixes**

```bash
git add -A
git commit -m "chore: format with Pint"
```

---

## Notes for the Implementer

- **league/commonmark FrontMatter docs:** The `FrontMatterExtension` is already in `vendor/league/commonmark/src/Extension/FrontMatter/`. After converting, check for `RenderedContentWithFrontMatter` to access `getFrontMatter()`.
- **The Gallipoli review** is already written at `resources/reviews/gallipoli-1981.md` — use it as a real test case after implementation.
- **Existing curator review (DB-based):** The controller currently loads a DB curator review via `CURATOR_USER_ID`. The filesystem curator review is a separate prop (`curatorReview`) and takes visual precedence. The DB curator review section remains for backwards compatibility.
- **MarkdownRenderer reuse:** The `toExcerpt()` method on `MarkdownRenderer` expects markdown input, but we're passing it stripped HTML. This is fine — it strips tags and truncates, which works on plain text too.
- **Cache location:** Uses the default Laravel cache driver. In production with Redis, this is fast. In dev with file cache, it's fine for single-file reads.
