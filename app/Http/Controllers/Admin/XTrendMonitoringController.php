<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XTrendKeyword;
use App\Models\XTrendResult;
use App\Services\XTrendMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class XTrendMonitoringController extends Controller
{
    public function __construct(
        protected XTrendMonitoringService $trendService
    ) {}

    /**
     * Display monitored keywords and recent trends.
     */
    public function index(Request $request): Response
    {
        $keywords = XTrendKeyword::query()
            ->withCount('results')
            ->latest()
            ->paginate(20);

        $recentTrends = $this->trendService->getRecentTrends(10);

        return Inertia::render('Admin/XTrends/Index', [
            'keywords' => $keywords,
            'recentTrends' => $recentTrends,
        ]);
    }

    /**
     * Store a new keyword to monitor.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:hashtag,keyword,phrase'],
        ]);

        XTrendKeyword::create($validated);

        return redirect()->route('admin.x-trends.index')
            ->with('success', 'Keyword added for monitoring.');
    }

    /**
     * Update keyword (toggle active status).
     */
    public function update(Request $request, XTrendKeyword $keyword): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $keyword->update($validated);

        return redirect()->route('admin.x-trends.index')
            ->with('success', 'Keyword updated.');
    }

    /**
     * Remove a keyword from monitoring.
     */
    public function destroy(XTrendKeyword $keyword): RedirectResponse
    {
        $keyword->delete();

        return redirect()->route('admin.x-trends.index')
            ->with('success', 'Keyword removed from monitoring.');
    }

    /**
     * Trigger manual search for a keyword.
     */
    public function search(XTrendKeyword $keyword): RedirectResponse
    {
        try {
            $results = $this->trendService->searchKeyword($keyword, 20);

            return redirect()->route('admin.x-trends.index')
                ->with('success', "Found {$results} new results for keyword.");
        } catch (\Exception $e) {
            return redirect()->route('admin.x-trends.index')
                ->with('error', 'Failed to search keyword: '.$e->getMessage());
        }
    }

    /**
     * View results for a keyword.
     */
    public function results(XTrendKeyword $keyword): Response
    {
        $results = XTrendResult::query()
            ->where('trend_keyword_id', $keyword->id)
            ->orderBy('like_count', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/XTrends/Results', [
            'keyword' => $keyword,
            'results' => $results,
        ]);
    }
}
