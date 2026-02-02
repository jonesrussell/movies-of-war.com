<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Review;
use App\Models\ReviewComment;

class ReviewCommentObserver
{
    /**
     * Handle the ReviewComment "created" event.
     */
    public function created(ReviewComment $comment): void
    {
        $comment->review->increment('comments_count');
    }

    /**
     * Handle the ReviewComment "deleted" event.
     */
    public function deleted(ReviewComment $comment): void
    {
        $reviewId = $comment->review_id;
        Review::where('id', $reviewId)->decrement('comments_count');
    }
}
