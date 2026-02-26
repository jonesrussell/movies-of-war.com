<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MovieStatus;
use App\Http\Controllers\Controller;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Services\FeaturedSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedQueueController extends Controller
{
    public function index(): Response
    {
        $heroQueue = FeaturedSlotQueue::slot('hero')
            ->with('movie')
            ->orderBy('position')
            ->get();

        $pickOfWeekQueue = FeaturedSlotQueue::slot('pick_of_week')
            ->with('movie')
            ->orderBy('position')
            ->get();

        $movies = Movie::query()
            ->published()
            ->orderBy('title')
            ->get(['id', 'title', 'release_year']);

        return Inertia::render('Admin/FeaturedSlots/Queue', [
            'heroQueue' => $heroQueue,
            'pickOfWeekQueue' => $pickOfWeekQueue,
            'movies' => $movies,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->where('status', MovieStatus::Published->value),
            ],
            'slot' => 'required|in:hero,pick_of_week',
            'position' => 'required|integer|min:1',
        ]);

        $slot = $validated['slot'];
        $position = (int) $validated['position'];

        // Shift existing entries down
        FeaturedSlotQueue::slot($slot)
            ->where('position', '>=', $position)
            ->orderByDesc('position')
            ->get()
            ->each(function (FeaturedSlotQueue $entry): void {
                $entry->update(['position' => $entry->position + 1]);
            });

        // Calculate scheduled_for based on position
        $scheduledFor = now()->next('Sunday')->addWeeks($position - 1);

        FeaturedSlotQueue::create([
            'movie_id' => $validated['movie_id'],
            'slot' => $slot,
            'position' => $position,
            'selection_method' => 'manual',
            'scheduled_for' => $scheduledFor->toDateString(),
        ]);

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Movie added to queue.');
    }

    public function destroy(FeaturedSlotQueue $featuredSlotQueue): RedirectResponse
    {
        $slot = $featuredSlotQueue->slot;
        $position = $featuredSlotQueue->position;

        $featuredSlotQueue->delete();

        // Reindex positions
        FeaturedSlotQueue::slot($slot)
            ->where('position', '>', $position)
            ->orderBy('position')
            ->get()
            ->each(function (FeaturedSlotQueue $entry): void {
                $entry->update(['position' => $entry->position - 1]);
            });

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Entry removed from queue.');
    }

    public function refill(FeaturedSlotService $service): RedirectResponse
    {
        $service->fillQueue();

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Queue refilled.');
    }
}
