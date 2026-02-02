<?php

declare(strict_types=1);

use App\Services\MarkdownRenderer;

beforeEach(function (): void {
    $this->renderer = app(MarkdownRenderer::class);
});

test('toHtml renders bold markdown', function (): void {
    $html = $this->renderer->toHtml('**bold**');

    expect($html)->toContain('<strong>bold</strong>');
});

test('toHtml renders link', function (): void {
    $html = $this->renderer->toHtml('[link text](https://example.com)');

    expect($html)->toContain('<a ')
        ->and($html)->toContain('href="https://example.com"')
        ->and($html)->toContain('link text');
});

test('toHtml renders list', function (): void {
    $html = $this->renderer->toHtml("- item one\n- item two");

    expect($html)->toContain('<ul>')
        ->and($html)->toContain('<li>')
        ->and($html)->toContain('item one')
        ->and($html)->toContain('item two');
});

test('toExcerpt returns plain text without tags', function (): void {
    $markdown = '**Bold** and *italic* and [link](https://x.com).';
    $excerpt = $this->renderer->toExcerpt($markdown, 500);

    expect($excerpt)->not->toContain('<')
        ->and($excerpt)->not->toContain('>')
        ->and($excerpt)->toContain('Bold')
        ->and($excerpt)->toContain('link');
});

test('toExcerpt truncates after stripping tags so no mid-tag cut', function (): void {
    $markdown = 'First sentence. **Second sentence with bold.** Third sentence.';
    $excerpt = $this->renderer->toExcerpt($markdown, 35);

    expect($excerpt)->not->toContain('<')
        ->and($excerpt)->not->toContain('**')
        ->and(mb_strlen($excerpt))->toBeLessThanOrEqual(36);
});
