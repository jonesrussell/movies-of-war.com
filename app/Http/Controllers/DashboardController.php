<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportTmdbMoviesRequest;
use App\Jobs\ImportTmdbMoviesJob;
use App\Models\Movie;
use App\Models\XPost;
use App\Models\XTrendKeyword;
use App\Services\XAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected XAnalyticsService $analyticsService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();

        $stats = [
            'movies' => Movie::published()->count(),
            'tags' => \App\Models\Tag::count(),
            'activeFeatures' => \App\Models\FeaturedSlot::active()->count(),
            'tmdbDrafts' => $user->is_admin ? Movie::draft()->count() : 0,
        ];

        // Add X API stats for admins
        if ($user->is_admin) {
            $xPerformanceReport = $this->analyticsService->getPerformanceReport(now()->subDays(30), now());

            $stats['x_published_posts'] = XPost::published()->count();
            $stats['x_scheduled_posts'] = XPost::scheduled()->count();
            $stats['x_failed_posts'] = XPost::failed()->count();
            $stats['x_total_impressions'] = $xPerformanceReport['total_impressions'] ?? 0;
            $stats['x_active_keywords'] = XTrendKeyword::active()->count();
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }

    public function tmdbImports(Request $request): Response
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $query = Movie::query()->draft()->with('tags')->latest();

        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $tmdbDrafts = $query->paginate(24)->withQueryString();

        return Inertia::render('Dashboard/TmdbImports', [
            'tmdbDrafts' => $tmdbDrafts,
            'queryParams' => $request->only(['search']),
        ]);
    }

    public function publishMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_PUBLISHED;
        $movie->save();

        return redirect()->route('dashboard.tmdb-imports')->with('success', 'Movie published successfully.');
    }

    public function archiveMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_ARCHIVED;
        $movie->save();

        return redirect()->route('dashboard.tmdb-imports')->with('success', 'Movie archived successfully.');
    }

    public function unpublishMovie(Movie $movie): RedirectResponse
    {
        if (! auth()->user()->is_admin) {
            abort(403);
        }

        $movie->status = Movie::STATUS_DRAFT;
        $movie->save();

        return redirect()->back()->with('success', 'Movie unpublished successfully.');
    }

    public function importTmdbMovies(ImportTmdbMoviesRequest $request): RedirectResponse
    {
        $limit = $request->validated()['limit'] ?? 30;
        $upcoming = $request->boolean('upcoming');

        if (app()->isLocal()) {
            Log::info('TMDB import request received', [
                'input' => $request->all(),
                'validated' => $request->validated(),
                'resolved' => [
                    'limit' => $limit,
                    'upcoming' => $upcoming,
                ],
            ]);
        }

        ImportTmdbMoviesJob::dispatch($limit, $upcoming);

        return redirect()->route('dashboard.tmdb-imports')->with('success', "TMDB import job queued. Importing up to {$limit} movies...");
    }
}
