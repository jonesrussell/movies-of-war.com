<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\XPost;
use App\Services\XAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class XAnalyticsController extends Controller
{
    public function __construct(
        protected XAnalyticsService $analyticsService
    ) {}

    /**
     * Display analytics dashboard.
     */
    public function index(Request $request): Response
    {
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->subDays(30);
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now();

        $performanceReport = $this->analyticsService->getPerformanceReport($startDate, $endDate);
        $topPerformers = $this->analyticsService->getTopPerformers(10, 'impressions');

        return Inertia::render('Admin/XAnalytics/Index', [
            'performanceReport' => $performanceReport,
            'topPerformers' => $topPerformers,
            'queryParams' => $request->only(['start_date', 'end_date']),
        ]);
    }

    /**
     * Show detailed metrics for a specific post.
     */
    public function show(XPost $xPost): Response
    {
        $xPost->load('analytics', 'user');

        $analytics = $xPost->analytics()->latest('recorded_at')->get();

        return Inertia::render('Admin/XAnalytics/Show', [
            'xPost' => $xPost,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Manually sync metrics for published posts.
     */
    public function sync(Request $request): RedirectResponse
    {
        $limit = $request->get('limit', 50);
        $synced = $this->analyticsService->syncPublishedPosts((int) $limit);

        return redirect()->route('admin.x-analytics.index')
            ->with('success', "Synced metrics for {$synced} posts.");
    }
}
