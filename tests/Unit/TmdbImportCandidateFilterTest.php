<?php

use App\Services\TmdbImportCandidateFilter;

beforeEach(function (): void {
    config(['tmdb.import.min_vote_count' => 100]);
    config(['tmdb.import.min_vote_average' => null]);
    config(['tmdb.import.relevance_keywords' => []]);
});

test('TmdbImportCandidateFilter filters by min_vote_count', function () {
    $candidates = collect([
        ['id' => 1, 'title' => 'A', 'overview' => '', 'vote_count' => 50, 'vote_average' => 8.0],
        ['id' => 2, 'title' => 'B', 'overview' => '', 'vote_count' => 200, 'vote_average' => 7.0],
    ]);

    $filter = app(TmdbImportCandidateFilter::class);
    $filtered = $filter->filter($candidates);

    expect($filtered->toArray())->toBe([2]);
});

test('TmdbImportCandidateFilter filters by min_vote_average when set', function () {
    config(['tmdb.import.min_vote_average' => 7.5]);

    $candidates = collect([
        ['id' => 1, 'title' => 'A', 'overview' => '', 'vote_count' => 500, 'vote_average' => 7.0],
        ['id' => 2, 'title' => 'B', 'overview' => '', 'vote_count' => 500, 'vote_average' => 8.0],
    ]);

    $filter = app(TmdbImportCandidateFilter::class);
    $filtered = $filter->filter($candidates);

    expect($filtered->toArray())->toBe([2]);
});
