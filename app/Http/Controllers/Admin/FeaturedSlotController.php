<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedSlot;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedSlotController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public function index(Request $request): Response
    {
        $query = FeaturedSlot::query()->with('movie');

        $sort = $request->get('sort', 'created_at_desc');
        match ($sort) {
            'movie_title_asc' => $query->join('movies', 'featured_slots.movie_id', '=', 'movies.id')
                ->orderBy('movies.title', 'asc')
                ->select('featured_slots.*'),
            'movie_title_desc' => $query->join('movies', 'featured_slots.movie_id', '=', 'movies.id')
                ->orderBy('movies.title', 'desc')
                ->select('featured_slots.*'),
            'slot_asc' => $query->orderBy('slot', 'asc')->orderBy('created_at', 'desc'),
            'slot_desc' => $query->orderBy('slot', 'desc')->orderBy('created_at', 'desc'),
            'created_at_asc' => $query->oldest('created_at'),
            default => $query->latest('created_at'),
        };

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage)->withQueryString();

        $slots = [
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

        return Inertia::render('Admin/FeaturedSlots/Index', [
            'slots' => $slots,
            'queryParams' => $this->queryParams($request, ['sort', 'per_page']),
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

    public function create(): Response
    {
        $movies = Movie::orderBy('title')->get(['id', 'title', 'release_year']);

        return Inertia::render('Admin/FeaturedSlots/Create', [
            'movies' => $movies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'slot' => 'required|in:hero,pick_of_week',
        ]);

        FeaturedSlot::create($validated);

        return redirect()->route('dashboard.featured-slots')
            ->with('success', 'Featured slot created successfully.');
    }

    public function edit(FeaturedSlot $featuredSlot): Response
    {
        $featuredSlot->load('movie');
        $movies = Movie::orderBy('title')->get(['id', 'title', 'release_year']);

        return Inertia::render('Admin/FeaturedSlots/Edit', [
            'slot' => $featuredSlot,
            'movies' => $movies,
        ]);
    }

    public function update(Request $request, FeaturedSlot $featuredSlot): RedirectResponse
    {
        $validated = $request->validate([
            'movie_id' => 'required|exists:movies,id',
            'slot' => 'required|in:hero,pick_of_week',
        ]);

        $featuredSlot->update($validated);

        return redirect()->route('dashboard.featured-slots')
            ->with('success', 'Featured slot updated successfully.');
    }

    public function destroy(FeaturedSlot $featuredSlot): RedirectResponse
    {
        $featuredSlot->delete();

        return redirect()->route('dashboard.featured-slots')
            ->with('success', 'Featured slot deleted successfully.');
    }
}
