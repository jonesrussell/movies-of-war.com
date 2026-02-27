<?php

use App\Enums\TagType;
use App\Models\Movie;
use App\Models\Tag;
use App\Services\MovieFilterService;

beforeEach(function () {
    (new MovieFilterService)->clearCache();
});

test('filter options include eraTags with published movies', function () {
    $eraTag = Tag::factory()->create(['type' => TagType::Era, 'name' => 'WWII']);
    $publishedMovie = Movie::factory()->published()->create();
    $publishedMovie->tags()->attach($eraTag);

    $response = $this->get(route('movies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Index')
        ->has('filters.eraTags', 1)
        ->where('filters.eraTags.0.slug', $eraTag->slug)
    );
});

test('eraTags excludes tags only attached to draft movies', function () {
    $eraTag = Tag::factory()->create(['type' => TagType::Era, 'name' => 'Vietnam War']);
    $draftMovie = Movie::factory()->draft()->create();
    $draftMovie->tags()->attach($eraTag);

    $response = $this->get(route('movies.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Index')
        ->has('filters.eraTags', 0)
    );
});

test('filter years only include published movies', function () {
    Movie::factory()->published()->create(['release_year' => 2000]);
    Movie::factory()->draft()->create(['release_year' => 1999]);

    $service = new MovieFilterService;
    $years = $service->getYears();

    expect($years->toArray())->toContain(2000)
        ->and($years->toArray())->not->toContain(1999);
});

test('filter countries only include published movies', function () {
    Movie::factory()->published()->create(['country' => 'USA']);
    Movie::factory()->draft()->create(['country' => 'Atlantis']);

    $service = new MovieFilterService;
    $countries = $service->getCountries();

    expect($countries->toArray())->toContain('USA')
        ->and($countries->toArray())->not->toContain('Atlantis');
});

test('filter conflicts only include published movies', function () {
    Movie::factory()->published()->create(['conflict' => 'WWII']);
    Movie::factory()->draft()->create(['conflict' => 'Draft War']);

    $service = new MovieFilterService;
    $conflicts = $service->getConflicts();

    expect($conflicts->toArray())->toContain('WWII')
        ->and($conflicts->toArray())->not->toContain('Draft War');
});
