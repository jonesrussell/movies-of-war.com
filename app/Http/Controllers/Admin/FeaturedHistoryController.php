<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeaturedSlotHistory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedHistoryController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public function index(Request $request): Response
    {
        $query = FeaturedSlotHistory::query()->with('movie');

        if ($request->filled('slot')) {
            $query->where('slot', $request->get('slot'));
        }

        if ($request->filled('method')) {
            $query->where('selection_method', $request->get('method'));
        }

        $sort = $request->get('sort', 'started_at_desc');
        match ($sort) {
            'started_at_asc' => $query->oldest('started_at'),
            'ended_at_desc' => $query->latest('ended_at'),
            'ended_at_asc' => $query->oldest('ended_at'),
            default => $query->latest('started_at'),
        };

        $perPage = $this->resolvePerPage($request);
        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/FeaturedSlots/History', [
            'history' => [
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
            ],
            'queryParams' => $request->only(['sort', 'per_page', 'slot', 'method']),
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', 20);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 20;
    }
}
