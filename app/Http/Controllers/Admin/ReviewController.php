<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Review::query()->with(['user', 'movie']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('movie', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            });
        }

        if ($published = $request->get('published')) {
            if ($published === '1') {
                $query->published();
            } else {
                $query->where('is_published', false);
            }
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => ReviewResource::collection($reviews),
            'queryParams' => $request->only(['search', 'published']),
        ]);
    }

    public function destroy(Review $review): RedirectResponse
    {
        $review->delete();

        return back()->with('success', 'Review deleted.');
    }

    public function togglePublished(Review $review): RedirectResponse
    {
        $review->update(['is_published' => ! $review->is_published]);

        return back()->with('success', $review->is_published ? 'Review published.' : 'Review unpublished.');
    }
}
