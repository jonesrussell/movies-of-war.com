<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReviewComment;
use App\Models\User;

final class ReviewCommentPolicy
{
    /**
     * Determine whether the user can view any comments.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the comment.
     */
    public function view(?User $user, ReviewComment $comment): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create comments.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the comment.
     */
    public function update(User $user, ReviewComment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the comment.
     */
    public function delete(User $user, ReviewComment $comment): bool
    {
        return $comment->user_id === $user->id || ($user->is_admin ?? false);
    }
}
