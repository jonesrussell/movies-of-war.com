<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Person;

test('people show page displays person when found', function () {
    $person = Person::factory()->create([
        'name' => 'Christopher Nolan',
        'slug' => 'christopher-nolan-525',
        'tmdb_id' => 525,
    ]);

    $movie = Movie::factory()->published()->create();
    $person->movies()->attach($movie->id, [
        'job' => 'Director',
        'character' => null,
        'department' => 'Directing',
        'cast_order' => null,
    ]);

    $response = $this->get("/people/{$person->slug}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('People/Show')
        ->has('person')
        ->where('person.name', 'Christopher Nolan')
    );
});

test('people show returns 404 for unknown slug', function () {
    $response = $this->get('/people/unknown-person-999');

    $response->assertNotFound();
});
