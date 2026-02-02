<?php

declare(strict_types=1);

namespace App\Services;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use League\CommonMark\Output\RenderedContentInterface;

/**
 * Renders Markdown to safe HTML for reviews and other user content.
 *
 * Configuration:
 * - html_input: 'strip' – raw HTML in input is removed (no XSS from pasted HTML).
 * - allow_unsafe_links: false – javascript: and other risky URLs are stripped.
 *
 * Extensions (GFM): Strikethrough, Tables, Task lists, Autolinks.
 * Raw HTML and inline HTML are explicitly disabled via html_input.
 */
final class MarkdownRenderer
{
    private const DEFAULT_EXCERPT_LIMIT = 200;

    private readonly GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * Render Markdown to safe HTML.
     */
    public function toHtml(string $markdown): string
    {
        $rendered = $this->converter->convert($markdown);

        return $rendered instanceof RenderedContentInterface
            ? $rendered->getContent()
            : (string) $rendered;
    }

    /**
     * Produce a plain-text excerpt: render to HTML, strip tags, truncate.
     * No mid-tag truncation (strip before truncate).
     */
    public function toExcerpt(string $markdown, int $limit = self::DEFAULT_EXCERPT_LIMIT): string
    {
        $html = $this->toHtml($markdown);
        $plain = strip_tags($html);
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/\s+/', ' ', $plain) ?? $plain;
        $plain = trim($plain);

        if (mb_strlen($plain) <= $limit) {
            return $plain;
        }

        return mb_substr($plain, 0, $limit).'…';
    }
}
