<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMovieRequest;
use App\Http\Requests\Admin\UpdateMovieRequest;
use App\Models\Movie;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MovieController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Movie::class);

        $query = Movie::query()
            ->with('tags')
            ->whereNot('status', MovieStatus::Archived);

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $movies = $query->latest()->paginate(20);

        return Inertia::render('Admin/Movies/Index', [
            'movies' => $movies,
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function archived(Request $request): Response
    {
        $this->authorize('viewAny', Movie::class);

        $query = Movie::query()->with('tags')->archived();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $movies = $query->latest()->paginate(20);

        return Inertia::render('Admin/Movies/Archived', [
            'movies' => $movies,
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function show(Movie $movie): Response
    {
        $this->authorize('view', $movie);

        $movie->load('tags');

        return Inertia::render('Admin/Movies/Show', [
            'movie' => $movie,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Movie::class);

        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Movies/Create', [
            'tags' => $tags,
        ]);
    }

    public function store(StoreMovieRequest $request): RedirectResponse
    {
        $validated = $request->validatedWithDefaults();
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $movie = Movie::create($validated);

        if ($tags) {
            $movie->tags()->sync($tags);
        }

        return redirect()
            ->route('dashboard.movies')
            ->with('success', 'Movie created successfully.');
    }

    public function edit(Movie $movie): Response
    {
        $this->authorize('update', $movie);

        $movie->load('tags');
        $tags = Tag::orderBy('name')->get();

        return Inertia::render('Admin/Movies/Edit', [
            'movie' => $movie,
            'tags' => $tags,
        ]);
    }

    public function update(UpdateMovieRequest $request, Movie $movie): RedirectResponse
    {
        $validated = $request->validated();
        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $movie->update($validated);

        if (isset($request->validated()['tags'])) {
            $movie->tags()->sync($tags);
        }

        return redirect()
            ->route('dashboard.movies')
            ->with('success', 'Movie updated successfully.');
    }

    public function destroy(Movie $movie): RedirectResponse
    {
        $this->authorize('delete', $movie);

        $movie->delete();

        return redirect()
            ->back()
            ->with('success', 'Movie deleted successfully.');
    }

    public function publish(Movie $movie): RedirectResponse
    {
        $this->authorize('publish', $movie);

        $movie->publish();

        return redirect()
            ->back()
            ->with('success', 'Movie published successfully.');
    }

    public function unpublish(Movie $movie): RedirectResponse
    {
        $this->authorize('unpublish', $movie);

        $movie->unpublish();

        return redirect()
            ->back()
            ->with('success', 'Movie unpublished successfully.');
    }

    public function archive(Movie $movie): RedirectResponse
    {
        $this->authorize('archive', $movie);

        $movie->archive();

        return redirect()
            ->back()
            ->with('success', 'Movie archived successfully.');
    }
}
