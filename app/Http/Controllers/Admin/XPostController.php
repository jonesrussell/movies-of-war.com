<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\XPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleXPostRequest;
use App\Http\Requests\StoreXPostRequest;
use App\Http\Resources\XPostResource;
use App\Jobs\PublishXPost;
use App\Models\XPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class XPostController extends Controller
{
    /**
     * Display a listing of X posts with filtering by status.
     */
    public function index(Request $request): Response
    {
        $query = XPost::query()->with('user');

        // Filter by status if provided
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Search by content
        if ($search = $request->get('search')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $xPosts = $query->latest()->paginate(20);

        return Inertia::render('Admin/XPosts/Index', [
            'xPosts' => XPostResource::collection($xPosts),
            'queryParams' => $request->only(['status', 'search']),
            'statuses' => collect(XPostStatus::cases())->mapWithKeys(fn ($status) => [
                $status->value => $status->label(),
            ])->all(),
        ]);
    }

    /**
     * Show the form for creating a new X post.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/XPosts/Create', [
            'maxTweetLength' => XPost::MAX_TWEET_LENGTH,
        ]);
    }

    /**
     * Store a newly created X post in storage.
     */
    public function store(StoreXPostRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $validated['user_id'] = auth()->id();
        $publishImmediately = $validated['publish_immediately'] ?? false;

        // Remove publish_immediately from validated data before creating the post
        unset($validated['publish_immediately']);

        $xPost = XPost::create($validated);

        // If publish immediately is requested, dispatch the publish job
        if ($publishImmediately) {
            // Use dispatchSync for immediate publishing to avoid queue worker dependency
            PublishXPost::dispatchSync($xPost);

            return redirect()->route('admin.x-posts.index')
                ->with('success', 'X post published successfully.');
        }

        $message = match ($xPost->status) {
            XPostStatus::Scheduled => 'X post scheduled for '.
                $xPost->scheduled_for->format('M j, Y g:i A'),
            default => 'X post draft created successfully.',
        };

        return redirect()->route('admin.x-posts.index')
            ->with('success', $message);
    }

    /**
     * Display the specified X post.
     */
    public function show(XPost $xPost): Response
    {
        $xPost->load('user');

        return Inertia::render('Admin/XPosts/Show', [
            'xPost' => new XPostResource($xPost),
        ]);
    }

    /**
     * Show the form for editing the specified X post.
     */
    public function edit(XPost $xPost): Response
    {
        // Only allow editing drafts and failed posts
        if (! $xPost->canEdit()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Only draft and failed posts can be edited.');
        }

        return Inertia::render('Admin/XPosts/Edit', [
            'xPost' => $xPost,
            'maxTweetLength' => XPost::MAX_TWEET_LENGTH,
        ]);
    }

    /**
     * Update the specified X post in storage.
     */
    public function update(StoreXPostRequest $request, XPost $xPost): RedirectResponse
    {
        // Only allow editing drafts and failed posts
        if (! $xPost->canEdit()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Only draft and failed posts can be edited.');
        }

        $xPost->update($request->validated());

        return redirect()->route('admin.x-posts.index')
            ->with('success', 'X post updated successfully.');
    }

    /**
     * Remove the specified X post from storage.
     */
    public function destroy(XPost $xPost): RedirectResponse
    {
        // Don't allow deleting published posts
        if ($xPost->isPublished()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Published posts cannot be deleted.');
        }

        $xPost->delete();

        return redirect()->route('admin.x-posts.index')
            ->with('success', 'X post deleted successfully.');
    }

    /**
     * Schedule an X post for future publishing.
     */
    public function schedule(ScheduleXPostRequest $request, XPost $xPost): RedirectResponse
    {
        if (! $xPost->canPublish()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'This post cannot be scheduled in its current state.');
        }

        $scheduledFor = new \DateTime($request->validated('scheduled_for'));
        $xPost->markAsScheduled($scheduledFor);

        return redirect()->route('admin.x-posts.index')
            ->with('success', 'X post scheduled for '.$xPost->scheduled_for->format('M j, Y g:i A'));
    }

    /**
     * Publish an X post immediately.
     */
    public function publish(XPost $xPost): RedirectResponse
    {
        if (! $xPost->canPublish()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'This post cannot be published in its current state.');
        }

        // Dispatch the job to publish the post
        PublishXPost::dispatch($xPost);

        return redirect()->route('admin.x-posts.index')
            ->with('success', 'X post queued for immediate publishing.');
    }

    /**
     * Cancel a scheduled X post.
     */
    public function cancel(XPost $xPost): RedirectResponse
    {
        if (! $xPost->isScheduled()) {
            return redirect()->route('admin.x-posts.index')
                ->with('error', 'Only scheduled posts can be cancelled.');
        }

        $xPost->cancel();

        return redirect()->route('admin.x-posts.index')
            ->with('success', 'Scheduled post cancelled.');
    }
}
