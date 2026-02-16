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
