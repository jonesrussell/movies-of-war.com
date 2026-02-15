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
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    public function index(Request $request): Response
    {
        $query = Review::query()->with(['user', 'movie']);

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('reviews.content', 'like', "%{$search}%")
                    ->orWhere('reviews.title', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('movie', fn ($q) => $q->where('title', 'like', "%{$search}%"));
            });
        }

        if ($published = $request->get('published')) {
            if ($published === '1') {
                $query->published();
            } else {
                $query->where('reviews.is_published', false);
            }
        }

        $sort = $request->get('sort', 'created_at_desc');
        match ($sort) {
            'created_at_asc' => $query->oldest('created_at'),
            'movie_title_asc' => $query->join('movies', 'reviews.movie_id', '=', 'movies.id')->orderBy('movies.title', 'asc')->select('reviews.*'),
            'movie_title_desc' => $query->join('movies', 'reviews.movie_id', '=', 'movies.id')->orderBy('movies.title', 'desc')->select('reviews.*'),
            'author_asc' => $query->join('users', 'reviews.user_id', '=', 'users.id')->orderBy('users.name', 'asc')->select('reviews.*'),
            'author_desc' => $query->join('users', 'reviews.user_id', '=', 'users.id')->orderBy('users.name', 'desc')->select('reviews.*'),
            'is_published_asc' => $query->orderBy('is_published', 'asc')->orderBy('created_at', 'desc'),
            'is_published_desc' => $query->orderBy('is_published', 'desc')->orderBy('created_at', 'desc'),
            default => $query->latest('created_at'),
        };

        $perPage = $this->resolvePerPage($request);
        $reviews = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => ReviewResource::collection($reviews),
            'queryParams' => $this->queryParams($request, ['search', 'published', 'sort', 'per_page']),
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
