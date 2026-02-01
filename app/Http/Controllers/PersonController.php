<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\PersonResource;
use App\Models\Person;
use Inertia\Inertia;
use Inertia\Response;

class PersonController extends Controller
{
    public function show(string $slug): Response
    {
        $person = Person::query()
            ->where('slug', $slug)
            ->with(['movies' => fn ($q) => $q->published()->select('movies.id', 'movies.title', 'movies.slug', 'movies.release_year', 'movies.poster_url')])
            ->firstOrFail();

        return Inertia::render('People/Show', [
            'person' => (new PersonResource($person))->resolve(request()),
        ]);
    }
}
