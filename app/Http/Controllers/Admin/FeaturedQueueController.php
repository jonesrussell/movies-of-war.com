<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\SlotType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQueueEntryRequest;
use App\Models\FeaturedSlotQueue;
use App\Models\Movie;
use App\Services\FeaturedSlotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedQueueController extends Controller
{
    public function index(): Response
    {
        $heroQueue = FeaturedSlotQueue::slot(SlotType::Hero)
            ->with('movie')
            ->orderBy('position')
            ->get();

        $pickOfWeekQueue = FeaturedSlotQueue::slot(SlotType::PickOfWeek)
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

    public function store(StoreQueueEntryRequest $request, FeaturedSlotService $service): RedirectResponse
    {
        $validated = $request->validated();
        $slot = SlotType::from($validated['slot']);
        $position = (int) $validated['position'];

        try {
            $service->insertAtPosition($slot, (int) $validated['movie_id'], $position);
        } catch (\Throwable $e) {
            Log::error('Failed to add entry to featured queue', [
                'movie_id' => $validated['movie_id'],
                'slot' => $slot->value,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('dashboard.featured-queue')
                ->with('error', 'Failed to add movie to queue. Please try again.');
        }

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Movie added to queue.');
    }

    public function destroy(FeaturedSlotQueue $featuredSlotQueue, FeaturedSlotService $service): RedirectResponse
    {
        $service->removeAndReindex($featuredSlotQueue);

        return redirect()->route('dashboard.featured-queue')
            ->with('success', 'Entry removed from queue.');
    }

    public function refill(FeaturedSlotService $service): RedirectResponse
    {
        try {
            $added = $service->fillQueue();

            if ($added === 0) {
                return redirect()->route('dashboard.featured-queue')
                    ->with('warning', 'Queue is already full or no eligible movies found.');
            }

            return redirect()->route('dashboard.featured-queue')
                ->with('success', "Queue refilled with {$added} entries.");
        } catch (\Throwable $e) {
            Log::error('Failed to refill featured queue', ['error' => $e->getMessage()]);

            return redirect()->route('dashboard.featured-queue')
                ->with('error', 'Failed to refill queue. Please try again.');
        }
    }
}
