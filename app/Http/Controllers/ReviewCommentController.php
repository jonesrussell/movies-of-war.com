<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewCommentRequest;
use App\Http\Requests\UpdateReviewCommentRequest;
use App\Models\Review;
use App\Models\ReviewComment;
use Illuminate\Http\RedirectResponse;

class ReviewCommentController extends Controller
{
    public function store(StoreReviewCommentRequest $request, Review $review): RedirectResponse
    {
        $review->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->validated('content'),
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function update(UpdateReviewCommentRequest $request, ReviewComment $comment): RedirectResponse
    {
        $comment->update([
            'content' => $request->validated('content'),
        ]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(ReviewComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
