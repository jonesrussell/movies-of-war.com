<?php

use App\Services\TmdbImportCandidateFilter;
use Illuminate\Support\Facades\Config;

test('filter drops candidates without war genre when require_war_genre_for_list_strategies is true', function () {
    Config::set('tmdb.import.require_war_genre_for_list_strategies', true);
    Config::set('tmdb.import.min_vote_count', null);
    Config::set('tmdb.import.min_vote_average', null);
    Config::set('tmdb.import.relevance_keywords', []);

    $candidates = collect([
        ['id' => 1, 'title' => 'War Film', 'genre_ids' => [18, 10752], 'vote_count' => 100, 'vote_average' => 7],
        ['id' => 2, 'title' => 'Comedy', 'genre_ids' => [35], 'vote_count' => 100, 'vote_average' => 7],
    ]);

    $filter = app(TmdbImportCandidateFilter::class);
    $ids = $filter->filter($candidates);

    expect($ids->count())->toBe(1)
        ->and($ids->first())->toBe(1);
});

test('filter keeps candidates without genre_ids when require_war_genre_for_list_strategies is true', function () {
    Config::set('tmdb.import.require_war_genre_for_list_strategies', true);
    Config::set('tmdb.import.min_vote_count', null);
    Config::set('tmdb.import.min_vote_average', null);
    Config::set('tmdb.import.relevance_keywords', []);

    $candidates = collect([
        ['id' => 1, 'title' => 'From Discover', 'vote_count' => 100, 'vote_average' => 7],
    ]);

    $filter = app(TmdbImportCandidateFilter::class);
    $ids = $filter->filter($candidates);

    expect($ids->count())->toBe(1)
        ->and($ids->first())->toBe(1);
});
