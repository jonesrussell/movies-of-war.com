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
    /** @var array<string, string|null> */
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
