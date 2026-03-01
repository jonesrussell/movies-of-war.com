<?php

use App\Models\Movie;
use App\Models\WarArticle;

test('WarArticle has movies belongsToMany relationship', function () {
    $article = WarArticle::factory()->create();
    $movie = Movie::factory()->create();

    $article->movies()->attach($movie->id, ['confidence' => 0.75]);

    $article->refresh();
    expect($article->movies)->toHaveCount(1)
        ->and($article->movies->first()->id)->toBe($movie->id)
        ->and((float) $article->movies->first()->pivot->confidence)->toBe(0.75);
});
