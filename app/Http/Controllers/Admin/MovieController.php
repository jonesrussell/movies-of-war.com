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
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    private const INDEX_SORT_MAP = [
        'title_asc' => [['title', 'asc']],
        'title_desc' => [['title', 'desc']],
        'release_year_asc' => [['release_year', 'asc'], ['title', 'asc']],
        'release_year_desc' => [['release_year', 'desc'], ['title', 'asc']],
        'status_asc' => [['status', 'asc'], ['title', 'asc']],
        'status_desc' => [['status', 'desc'], ['title', 'asc']],
        'updated_at_asc' => [['updated_at', 'asc']],
        'updated_at_desc' => [['updated_at', 'desc']],
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Movie::class);

        $query = Movie::query()
            ->with('tags')
            ->whereNot('status', MovieStatus::Archived);

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $status = $request->get('status');
        if (in_array($status, ['draft', 'published'], true)) {
            $query->where('status', $status);
        }

        $tag = $request->get('tag');
        if ($tag !== null && $tag !== '') {
            $query->whereHas('tags', function ($q) use ($tag): void {
                if (is_numeric($tag)) {
                    $q->where('tags.id', (int) $tag);
                } else {
                    $q->where('tags.slug', $tag);
                }
            });
        }

        $sort = $request->get('sort', 'updated_at_desc');
        if (isset(self::INDEX_SORT_MAP[$sort])) {
            foreach (self::INDEX_SORT_MAP[$sort] as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->latest('updated_at');
        }

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage)->withQueryString();
        $movies = [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection()->toArray(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];

        return Inertia::render('Admin/Movies/Index', [
            'movies' => $movies,
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'queryParams' => $this->queryParams($request, ['search', 'sort', 'per_page', 'status', 'tag']),
        ]);
    }

    public function archived(Request $request): Response
    {
        $this->authorize('viewAny', Movie::class);

        $query = Movie::query()->with('tags')->archived();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tag = $request->get('tag');
        if ($tag !== null && $tag !== '') {
            $query->whereHas('tags', function ($q) use ($tag): void {
                if (is_numeric($tag)) {
                    $q->where('tags.id', (int) $tag);
                } else {
                    $q->where('tags.slug', $tag);
                }
            });
        }

        $sort = $request->get('sort', 'updated_at_desc');
        if (isset(self::INDEX_SORT_MAP[$sort])) {
            foreach (self::INDEX_SORT_MAP[$sort] as [$column, $direction]) {
                $query->orderBy($column, $direction);
            }
        } else {
            $query->latest('updated_at');
        }

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage)->withQueryString();
        $movies = [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection()->toArray(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];

        return Inertia::render('Admin/Movies/Archived', [
            'movies' => $movies,
            'tags' => Tag::orderBy('name')->get(['id', 'name', 'slug']),
            'queryParams' => $this->queryParams($request, ['search', 'sort', 'per_page', 'tag']),
        ]);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function queryParams(Request $request, array $keys): array
    {
        $params = $request->only($keys);
        if (array_key_exists('per_page', $params) && $params['per_page'] !== null) {
            $params['per_page'] = (int) $params['per_page'];
        }

        return $params;
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', 20);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 20;
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
