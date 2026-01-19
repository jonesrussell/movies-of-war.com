<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Movie;
use App\Models\User;

final class MoviePolicy
{
    /**
     * Determine if the user can view any movies.
     * Anyone can view the movie list (public route).
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the movie.
     * Published movies are public; drafts/archived require admin.
     */
    public function view(?User $user, Movie $movie): bool
    {
        if ($movie->isPublished()) {
            return true;
        }

        return $user?->is_admin ?? false;
    }

    /**
     * Determine if the user can create movies.
     * Only admins can create movies.
     */
    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can update the movie.
     * Only admins can update movies.
     */
    public function update(User $user, Movie $movie): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can delete the movie.
     * Only admins can delete movies.
     */
    public function delete(User $user, Movie $movie): bool
    {
        return $user->is_admin;
    }

    /**
     * Determine if the user can publish the movie.
     * Only admins can publish, and only draft movies.
     */
    public function publish(User $user, Movie $movie): bool
    {
        return $user->is_admin && $movie->isDraft();
    }

    /**
     * Determine if the user can unpublish the movie.
     * Only admins can unpublish, and only published movies.
     */
    public function unpublish(User $user, Movie $movie): bool
    {
        return $user->is_admin && $movie->isPublished();
    }

    /**
     * Determine if the user can archive the movie.
     * Only admins can archive, and only non-archived movies.
     */
    public function archive(User $user, Movie $movie): bool
    {
        return $user->is_admin && ! $movie->isArchived();
    }

    /**
     * Determine if the user can restore an archived movie.
     * Only admins can restore, and only archived movies.
     */
    public function restore(User $user, Movie $movie): bool
    {
        return $user->is_admin && $movie->isArchived();
    }

    /**
     * Determine if the user can manage featured slots for this movie.
     * Only admins can manage featured slots.
     */
    public function feature(User $user, Movie $movie): bool
    {
        return $user->is_admin && $movie->isPublished();
    }
}
