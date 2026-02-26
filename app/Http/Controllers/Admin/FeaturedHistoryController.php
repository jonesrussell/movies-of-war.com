<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SelectionMethod;
use App\Enums\SlotType;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeaturedSlotHistoryResource;
use App\Models\FeaturedSlotHistory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedHistoryController extends Controller
{
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    private const SORT_OPTIONS = ['started_at_desc', 'started_at_asc', 'ended_at_desc', 'ended_at_asc'];

    public function index(Request $request): Response
    {
        $request->validate([
            'slot' => ['nullable', Rule::enum(SlotType::class)],
            'method' => ['nullable', Rule::enum(SelectionMethod::class)],
            'sort' => ['nullable', Rule::in(self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

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
            'history' => FeaturedSlotHistoryResource::collection($paginator),
            'queryParams' => $request->only(['sort', 'per_page', 'slot', 'method']),
        ]);
    }

    private function resolvePerPage(Request $request): int
    {
        $perPage = (int) $request->get('per_page', 20);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 20;
    }
}
