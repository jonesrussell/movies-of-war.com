<?php

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
    public function index(): Response
    {
        $slots = FeaturedSlot::query()
            ->with('movie')
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/FeaturedSlots/Index', [
            'slots' => $slots,
        ]);
    }

    public function create(): Response
    {
        $movies = Movie::orderBy('title')->get(['id', 'title']);

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
        $movies = Movie::orderBy('title')->get(['id', 'title']);

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
