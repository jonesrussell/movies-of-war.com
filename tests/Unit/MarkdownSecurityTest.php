<?php

declare(strict_types=1);

use App\Services\MarkdownRenderer;

beforeEach(function (): void {
    $this->renderer = app(MarkdownRenderer::class);
});

test('script tag is stripped and does not appear in output', function (): void {
    $input = '<script>alert(1)</script>';
    $html = $this->renderer->toHtml($input);

    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('alert(1)');
});

test('img with onerror is sanitized', function (): void {
    $input = '<img src=x onerror=alert(1)>';
    $html = $this->renderer->toHtml($input);

    expect($html)->not->toContain('onerror')
        ->and($html)->not->toContain('alert(');
});

test('raw HTML block in markdown is stripped', function (): void {
    $input = "Paragraph before.\n\n<div>Raw HTML block</div>\n\nParagraph after.";
    $html = $this->renderer->toHtml($input);

    expect($html)->not->toContain('<div>')
        ->and($html)->not->toContain('</div>');
});

test('markdown links are rendered with safe href', function (): void {
    $input = '[Click here](https://example.com)';
    $html = $this->renderer->toHtml($input);

    expect($html)->toContain('<a ')
        ->and($html)->toContain('href="https://example.com"')
        ->and($html)->toContain('Click here');
});

test('javascript link is not allowed when allow_unsafe_links is false', function (): void {
    $input = '[Click](javascript:alert(1))';
    $html = $this->renderer->toHtml($input);

    expect($html)->not->toContain('javascript:');
});
