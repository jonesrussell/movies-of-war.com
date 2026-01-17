<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XCuratedPost;
use App\Services\XContentDiscoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class XContentDiscoveryController extends Controller
{
    public function __construct(
        protected XContentDiscoveryService $discoveryService
    ) {}

    /**
     * Display curated content feed.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'featured' => $request->boolean('featured'),
            'high_engagement' => $request->boolean('high_engagement'),
            'recent' => $request->boolean('recent'),
        ];

        $posts = XCuratedPost::query()
            ->when($filters['featured'], fn ($q) => $q->featured())
            ->when($filters['high_engagement'], fn ($q) => $q->highEngagement())
            ->when($filters['recent'], fn ($q) => $q->recent(7))
            ->orderBy('discovered_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/XContentDiscovery/Index', [
            'posts' => $posts,
            'filters' => $filters,
        ]);
    }

    /**
     * Trigger manual discovery scan.
     */
    public function discover(Request $request): RedirectResponse
    {
        $filters = [
            'min_likes' => (int) $request->get('min_likes', 10),
            'max_results' => (int) $request->get('max_results', 50),
        ];

        try {
            $discovered = $this->discoveryService->discoverContent($filters);

            return redirect()->route('dashboard.x-content-discovery')
                ->with('success', "Discovered {$discovered} new posts.");
        } catch (\Exception $e) {
            return redirect()->route('dashboard.x-content-discovery')
                ->with('error', 'Failed to discover content: '.$e->getMessage());
        }
    }

    /**
     * Manually add a curated post.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tweet_id' => ['required', 'string', 'unique:x_curated_posts,tweet_id'],
            'author_username' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'media_urls' => ['sometimes', 'array'],
            'media_urls.*' => ['string'],
            'like_count' => ['sometimes', 'integer', 'min:0'],
            'retweet_count' => ['sometimes', 'integer', 'min:0'],
        ]);

        $this->discoveryService->saveCuratedPost($validated);

        return redirect()->route('dashboard.x-content-discovery')
            ->with('success', 'Curated post added.');
    }

    /**
     * Update curated post (feature, add notes).
     */
    public function update(Request $request, XCuratedPost $post): RedirectResponse
    {
        $validated = $request->validate([
            'is_featured' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $post->update($validated);

        return redirect()->route('dashboard.x-content-discovery')
            ->with('success', 'Curated post updated.');
    }

    /**
     * Remove curated post.
     */
    public function destroy(XCuratedPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('dashboard.x-content-discovery')
            ->with('success', 'Curated post removed.');
    }
}
