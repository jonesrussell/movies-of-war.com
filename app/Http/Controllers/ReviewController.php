<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Resources\MovieResource;
use App\Http\Resources\ReviewResource;
use App\Models\Movie;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function myReviews(Request $request): Response
    {
        $reviews = $request->user()
            ->reviews()
            ->with(['movie:id,title,slug,poster_path,poster_url'])
            ->latest()
            ->paginate(15);

        return Inertia::render('MyReviews/Index', [
            'reviews' => ReviewResource::collection($reviews),
        ]);
    }

    public function index(Request $request, Movie $movie): Response
    {
        $query = $movie->reviews()
            ->published()
            ->with('user');

        if (! $request->boolean('show_spoilers')) {
            $query->withoutSpoilers();
        }

        $sort = $request->get('sort', 'recent');
        $query = match ($sort) {
            'helpful' => $query->orderByDesc('helpful_count')->orderByDesc('created_at'),
            'rating_high' => $query->orderByDesc('rating')->orderByDesc('created_at'),
            'rating_low' => $query->orderBy('rating')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $reviews = $query->paginate(15)->withQueryString();

        return Inertia::render('Movies/Reviews', [
            'movie' => (new MovieResource($movie->load('tags')))->resolve($request),
            'reviews' => ReviewResource::collection($reviews),
            'queryParams' => $request->only(['show_spoilers', 'sort']),
        ]);
    }

    public function store(StoreReviewRequest $request, Movie $movie): RedirectResponse
    {
        $movie->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $request->validated('rating'),
            'title' => $request->validated('title'),
            'content' => $request->validated('content'),
            'has_spoilers' => $request->boolean('has_spoilers'),
        ]);

        return back()->with('success', 'Review published.');
    }

    public function show(Review $review): Response
    {
        $this->authorize('view', $review);

        $review->load(['user', 'movie', 'comments' => fn ($q) => $q->with('user')->orderBy('created_at')]);

        return Inertia::render('Reviews/Show', [
            'review' => (new ReviewResource($review))->resolve(request()),
        ]);
    }

    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update([
            'rating' => $request->validated('rating'),
            'title' => $request->validated('title'),
            'content' => $request->validated('content'),
            'has_spoilers' => $request->boolean('has_spoilers'),
        ]);

        return back()->with('success', 'Review updated.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return back()->with('success', 'Review deleted.');
    }
}
