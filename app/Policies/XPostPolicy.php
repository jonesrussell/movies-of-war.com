<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\XPost;

final class XPostPolicy
{
    /**
     * Determine if the user can view any X posts.
     * Only admins can access the X post management.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can view the X post.
     * Only admins can view X posts.
     */
    public function view(User $user, XPost $xPost): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can create X posts.
     * Only admins can create X posts.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can update the X post.
     * Only admins can update, and only if the post is editable.
     */
    public function update(User $user, XPost $xPost): bool
    {
        return $user->is_admin && $xPost->canEdit();
    }

    /**
     * Determine if the user can delete the X post.
     * Only admins can delete, and only if the post is editable.
     */
    public function delete(User $user, XPost $xPost): bool
    {
        return $user->is_admin && $xPost->canEdit();
    }

    /**
     * Determine if the user can publish the X post immediately.
     * Only admins can publish, and only if the post allows it.
     */
    public function publish(User $user, XPost $xPost): bool
    {
        return $user->is_admin && $xPost->canPublish();
    }

    /**
     * Determine if the user can schedule the X post.
     * Only admins can schedule, and only if the post allows it.
     */
    public function schedule(User $user, XPost $xPost): bool
    {
        return $user->is_admin && $xPost->canSchedule();
    }

    /**
     * Determine if the user can cancel a scheduled X post.
     * Only admins can cancel, and only scheduled posts.
     */
    public function cancel(User $user, XPost $xPost): bool
    {
        return $user->is_admin && $xPost->canCancel();
    }
}
