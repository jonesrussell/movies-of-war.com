<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

final class ReviewPolicy
{
    /**
     * Determine whether the user can view any reviews.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the review.
     */
    public function view(?User $user, Review $review): bool
    {
        return $review->is_published;
    }

    /**
     * Determine whether the user can update the review.
     */
    public function update(User $user, Review $review): bool
    {
        return $review->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the review.
     */
    public function delete(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || ($user->is_admin ?? false);
    }

    /**
     * Determine whether the user can view spoiler content (opt-in handled by request).
     */
    public function viewWithSpoilers(?User $user, Review $review): bool
    {
        return true;
    }
}
