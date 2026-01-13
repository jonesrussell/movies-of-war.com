<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MovieController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Movie::query()->with('tags');

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $movies = $query->latest()->paginate(20);

        return Inertia::render('Admin/Movies/Index', [
            'movies' => $movies,
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Movies/Create', [
            'tags' => $tags,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'release_year' => 'required|integer|min:1900|max:2100',
            'release_date' => 'nullable|date',
            'synopsis' => 'required|string',
            'runtime' => 'nullable|integer|min:1',
            'country' => 'nullable|string|max:255',
            'conflict' => 'nullable|string|max:255',
            'poster_url' => 'nullable|url',
            'trailer_url' => 'nullable|url',
            'imdb_id' => 'nullable|string|max:20',
            'is_upcoming' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        $validated['status'] = $validated['status'] ?? Movie::STATUS_DRAFT;

        $movie = Movie::create($validated);

        if (isset($validated['tags'])) {
            $movie->tags()->sync($validated['tags']);
        }

        return redirect()->route('dashboard.movies')
            ->with('success', 'Movie created successfully.');
    }

    public function edit(Movie $movie): Response
    {
        $movie->load('tags');
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Movies/Edit', [
            'movie' => $movie,
            'tags' => $tags,
        ]);
    }

    public function update(Request $request, Movie $movie): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'release_year' => 'required|integer|min:1900|max:2100',
            'release_date' => 'nullable|date',
            'synopsis' => 'required|string',
            'runtime' => 'nullable|integer|min:1',
            'country' => 'nullable|string|max:255',
            'conflict' => 'nullable|string|max:255',
            'poster_url' => 'nullable|url',
            'trailer_url' => 'nullable|url',
            'imdb_id' => 'nullable|string|max:20',
            'is_upcoming' => 'boolean',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
        ]);

        $movie->update($validated);

        if (isset($validated['tags'])) {
            $movie->tags()->sync($validated['tags']);
        }

        return redirect()->route('dashboard.movies')
            ->with('success', 'Movie updated successfully.');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        $movie->status = Movie::STATUS_ARCHIVED;
        $movie->save();

        return redirect()->route('dashboard.movies')
            ->with('success', 'Movie archived successfully.');
    }

    public function publish(Movie $movie): RedirectResponse
    {
        $movie->status = Movie::STATUS_PUBLISHED;
        $movie->save();

        return redirect()->route('dashboard.movies')
            ->with('success', 'Movie published successfully.');
    }

    public function unpublish(Movie $movie): RedirectResponse
    {
        $movie->status = Movie::STATUS_DRAFT;
        $movie->save();

        return redirect()->route('dashboard.movies')
            ->with('success', 'Movie unpublished successfully.');
    }
}
