<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\User;
use App\Policies\MoviePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MoviePolicy', function () {
    beforeEach(function () {
        $this->policy = new MoviePolicy;
    });

    describe('viewAny', function () {
        it('allows guests to view any movies', function () {
            expect($this->policy->viewAny(null))->toBeTrue();
        });

        it('allows authenticated users to view any movies', function () {
            $user = User::factory()->create();

            expect($this->policy->viewAny($user))->toBeTrue();
        });

        it('allows admin users to view any movies', function () {
            $admin = User::factory()->admin()->create();

            expect($this->policy->viewAny($admin))->toBeTrue();
        });
    });

    describe('view', function () {
        it('allows anyone to view published movies', function () {
            $movie = Movie::factory()->published()->create();

            expect($this->policy->view(null, $movie))->toBeTrue();
        });

        it('allows authenticated users to view published movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->published()->create();

            expect($this->policy->view($user, $movie))->toBeTrue();
        });

        it('prevents guests from viewing draft movies', function () {
            $movie = Movie::factory()->draft()->create();

            expect($this->policy->view(null, $movie))->toBeFalse();
        });

        it('prevents non-admin users from viewing draft movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->draft()->create();

            expect($this->policy->view($user, $movie))->toBeFalse();
        });

        it('allows admin users to view draft movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->draft()->create();

            expect($this->policy->view($admin, $movie))->toBeTrue();
        });

        it('prevents guests from viewing archived movies', function () {
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->view(null, $movie))->toBeFalse();
        });

        it('prevents non-admin users from viewing archived movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->view($user, $movie))->toBeFalse();
        });

        it('allows admin users to view archived movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->view($admin, $movie))->toBeTrue();
        });
    });

    describe('create', function () {
        it('prevents non-admin users from creating movies', function () {
            $user = User::factory()->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('allows admin users to create movies', function () {
            $admin = User::factory()->admin()->create();

            expect($this->policy->create($admin))->toBeTrue();
        });
    });

    describe('update', function () {
        it('prevents non-admin users from updating movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->create();

            expect($this->policy->update($user, $movie))->toBeFalse();
        });

        it('allows admin users to update movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->create();

            expect($this->policy->update($admin, $movie))->toBeTrue();
        });
    });

    describe('delete', function () {
        it('prevents non-admin users from deleting movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->create();

            expect($this->policy->delete($user, $movie))->toBeFalse();
        });

        it('allows admin users to delete movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->create();

            expect($this->policy->delete($admin, $movie))->toBeTrue();
        });
    });

    describe('publish', function () {
        it('prevents non-admin users from publishing movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->draft()->create();

            expect($this->policy->publish($user, $movie))->toBeFalse();
        });

        it('prevents admin users from publishing non-draft movies', function () {
            $admin = User::factory()->admin()->create();
            $publishedMovie = Movie::factory()->published()->create();
            $archivedMovie = Movie::factory()->archived()->create();

            expect($this->policy->publish($admin, $publishedMovie))->toBeFalse();
            expect($this->policy->publish($admin, $archivedMovie))->toBeFalse();
        });

        it('allows admin users to publish draft movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->draft()->create();

            expect($this->policy->publish($admin, $movie))->toBeTrue();
        });
    });

    describe('unpublish', function () {
        it('prevents non-admin users from unpublishing movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->published()->create();

            expect($this->policy->unpublish($user, $movie))->toBeFalse();
        });

        it('prevents admin users from unpublishing non-published movies', function () {
            $admin = User::factory()->admin()->create();
            $draftMovie = Movie::factory()->draft()->create();
            $archivedMovie = Movie::factory()->archived()->create();

            expect($this->policy->unpublish($admin, $draftMovie))->toBeFalse();
            expect($this->policy->unpublish($admin, $archivedMovie))->toBeFalse();
        });

        it('allows admin users to unpublish published movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->published()->create();

            expect($this->policy->unpublish($admin, $movie))->toBeTrue();
        });
    });

    describe('archive', function () {
        it('prevents non-admin users from archiving movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->create();

            expect($this->policy->archive($user, $movie))->toBeFalse();
        });

        it('prevents admin users from archiving already archived movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->archive($admin, $movie))->toBeFalse();
        });

        it('allows admin users to archive non-archived movies', function () {
            $admin = User::factory()->admin()->create();
            $draftMovie = Movie::factory()->draft()->create();
            $publishedMovie = Movie::factory()->published()->create();

            expect($this->policy->archive($admin, $draftMovie))->toBeTrue();
            expect($this->policy->archive($admin, $publishedMovie))->toBeTrue();
        });
    });

    describe('restore', function () {
        it('prevents non-admin users from restoring movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->restore($user, $movie))->toBeFalse();
        });

        it('prevents admin users from restoring non-archived movies', function () {
            $admin = User::factory()->admin()->create();
            $draftMovie = Movie::factory()->draft()->create();
            $publishedMovie = Movie::factory()->published()->create();

            expect($this->policy->restore($admin, $draftMovie))->toBeFalse();
            expect($this->policy->restore($admin, $publishedMovie))->toBeFalse();
        });

        it('allows admin users to restore archived movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->archived()->create();

            expect($this->policy->restore($admin, $movie))->toBeTrue();
        });
    });

    describe('feature', function () {
        it('prevents non-admin users from featuring movies', function () {
            $user = User::factory()->create();
            $movie = Movie::factory()->published()->create();

            expect($this->policy->feature($user, $movie))->toBeFalse();
        });

        it('prevents admin users from featuring non-published movies', function () {
            $admin = User::factory()->admin()->create();
            $draftMovie = Movie::factory()->draft()->create();
            $archivedMovie = Movie::factory()->archived()->create();

            expect($this->policy->feature($admin, $draftMovie))->toBeFalse();
            expect($this->policy->feature($admin, $archivedMovie))->toBeFalse();
        });

        it('allows admin users to feature published movies', function () {
            $admin = User::factory()->admin()->create();
            $movie = Movie::factory()->published()->create();

            expect($this->policy->feature($admin, $movie))->toBeTrue();
        });
    });
});
