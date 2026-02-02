<?php

use App\Enums\TagType;
use App\Models\Movie;
use App\Models\Tag;
use App\Services\TmdbImportGapFiller;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('TmdbImportGapFiller sorts strategies by bucket movie count ascending', function () {
    config(['tmdb.import.gap_filling_enabled' => true]);
    config([
        'tmdb.import.strategy_bucket_map' => [
            'war_genre' => null,
            'wwii' => 'wwii',
            'vietnam' => 'vietnam-war',
        ],
    ]);

    $wwiiTag = Tag::factory()->create(['slug' => 'wwii', 'type' => TagType::Era]);
    $vietnamTag = Tag::factory()->create(['slug' => 'vietnam-war', 'type' => TagType::Era]);
    $m1 = Movie::factory()->create();
    $m2 = Movie::factory()->create();
    $m3 = Movie::factory()->create();
    $wwiiTag->movies()->attach($m1->id);
    $vietnamTag->movies()->attach([$m2->id, $m3->id]);

    $strategies = [
        ['key' => 'war_genre', 'type' => 'genre'],
        ['key' => 'wwii', 'type' => 'keywords'],
        ['key' => 'vietnam', 'type' => 'keywords'],
    ];

    $filler = app(TmdbImportGapFiller::class);
    $sorted = $filler->sortStrategiesByGap($strategies);

    expect($sorted[0]['key'])->toBe('wwii')
        ->and($sorted[1]['key'])->toBe('vietnam')
        ->and($sorted[2]['key'])->toBe('war_genre');
});
