<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\XPost;
use App\Policies\XPostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('XPostPolicy', function () {
    beforeEach(function () {
        $this->policy = new XPostPolicy;
    });

    describe('viewAny', function () {
        it('prevents non-admin users from viewing any X posts', function () {
            $user = User::factory()->create();

            expect($this->policy->viewAny($user))->toBeFalse();
        });

        it('allows admin users to view any X posts', function () {
            $admin = User::factory()->admin()->create();

            expect($this->policy->viewAny($admin))->toBeTrue();
        });
    });

    describe('view', function () {
        it('prevents non-admin users from viewing X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->create();

            expect($this->policy->view($user, $xPost))->toBeFalse();
        });

        it('allows admin users to view X posts', function () {
            $admin = User::factory()->admin()->create();
            $xPost = XPost::factory()->create();

            expect($this->policy->view($admin, $xPost))->toBeTrue();
        });
    });

    describe('create', function () {
        it('prevents non-admin users from creating X posts', function () {
            $user = User::factory()->create();

            expect($this->policy->create($user))->toBeFalse();
        });

        it('allows admin users to create X posts', function () {
            $admin = User::factory()->admin()->create();

            expect($this->policy->create($admin))->toBeTrue();
        });
    });

    describe('update', function () {
        it('prevents non-admin users from updating X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->create();

            expect($this->policy->update($user, $xPost))->toBeFalse();
        });

        it('prevents admin users from updating non-editable X posts', function () {
            $admin = User::factory()->admin()->create();
            $publishedPost = XPost::factory()->published()->create();
            $cancelledPost = XPost::factory()->cancelled()->create();

            expect($this->policy->update($admin, $publishedPost))->toBeFalse();
            expect($this->policy->update($admin, $cancelledPost))->toBeFalse();
        });

        it('allows admin users to update editable X posts', function () {
            $admin = User::factory()->admin()->create();
            $draftPost = XPost::factory()->draft()->create();
            $scheduledPost = XPost::factory()->scheduled()->create();
            $failedPost = XPost::factory()->failed()->create();

            expect($this->policy->update($admin, $draftPost))->toBeTrue();
            expect($this->policy->update($admin, $scheduledPost))->toBeTrue();
            expect($this->policy->update($admin, $failedPost))->toBeTrue();
        });
    });

    describe('delete', function () {
        it('prevents non-admin users from deleting X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->create();

            expect($this->policy->delete($user, $xPost))->toBeFalse();
        });

        it('prevents admin users from deleting non-editable X posts', function () {
            $admin = User::factory()->admin()->create();
            $publishedPost = XPost::factory()->published()->create();
            $cancelledPost = XPost::factory()->cancelled()->create();

            expect($this->policy->delete($admin, $publishedPost))->toBeFalse();
            expect($this->policy->delete($admin, $cancelledPost))->toBeFalse();
        });

        it('allows admin users to delete editable X posts', function () {
            $admin = User::factory()->admin()->create();
            $draftPost = XPost::factory()->draft()->create();
            $scheduledPost = XPost::factory()->scheduled()->create();
            $failedPost = XPost::factory()->failed()->create();

            expect($this->policy->delete($admin, $draftPost))->toBeTrue();
            expect($this->policy->delete($admin, $scheduledPost))->toBeTrue();
            expect($this->policy->delete($admin, $failedPost))->toBeTrue();
        });
    });

    describe('publish', function () {
        it('prevents non-admin users from publishing X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->draft()->create();

            expect($this->policy->publish($user, $xPost))->toBeFalse();
        });

        it('prevents admin users from publishing non-publishable X posts', function () {
            $admin = User::factory()->admin()->create();
            $publishedPost = XPost::factory()->published()->create();
            $cancelledPost = XPost::factory()->cancelled()->create();

            expect($this->policy->publish($admin, $publishedPost))->toBeFalse();
            expect($this->policy->publish($admin, $cancelledPost))->toBeFalse();
        });

        it('allows admin users to publish publishable X posts', function () {
            $admin = User::factory()->admin()->create();
            $draftPost = XPost::factory()->draft()->create();
            $scheduledPost = XPost::factory()->scheduled()->create();
            $failedPost = XPost::factory()->failed()->create();

            expect($this->policy->publish($admin, $draftPost))->toBeTrue();
            expect($this->policy->publish($admin, $scheduledPost))->toBeTrue();
            expect($this->policy->publish($admin, $failedPost))->toBeTrue();
        });
    });

    describe('schedule', function () {
        it('prevents non-admin users from scheduling X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->draft()->create();

            expect($this->policy->schedule($user, $xPost))->toBeFalse();
        });

        it('prevents admin users from scheduling non-schedulable X posts', function () {
            $admin = User::factory()->admin()->create();
            $publishedPost = XPost::factory()->published()->create();
            $scheduledPost = XPost::factory()->scheduled()->create();
            $cancelledPost = XPost::factory()->cancelled()->create();

            expect($this->policy->schedule($admin, $publishedPost))->toBeFalse();
            expect($this->policy->schedule($admin, $scheduledPost))->toBeFalse();
            expect($this->policy->schedule($admin, $cancelledPost))->toBeFalse();
        });

        it('allows admin users to schedule schedulable X posts', function () {
            $admin = User::factory()->admin()->create();
            $draftPost = XPost::factory()->draft()->create();
            $failedPost = XPost::factory()->failed()->create();

            expect($this->policy->schedule($admin, $draftPost))->toBeTrue();
            expect($this->policy->schedule($admin, $failedPost))->toBeTrue();
        });
    });

    describe('cancel', function () {
        it('prevents non-admin users from cancelling X posts', function () {
            $user = User::factory()->create();
            $xPost = XPost::factory()->scheduled()->create();

            expect($this->policy->cancel($user, $xPost))->toBeFalse();
        });

        it('prevents admin users from cancelling non-scheduled X posts', function () {
            $admin = User::factory()->admin()->create();
            $draftPost = XPost::factory()->draft()->create();
            $publishedPost = XPost::factory()->published()->create();
            $failedPost = XPost::factory()->failed()->create();
            $cancelledPost = XPost::factory()->cancelled()->create();

            expect($this->policy->cancel($admin, $draftPost))->toBeFalse();
            expect($this->policy->cancel($admin, $publishedPost))->toBeFalse();
            expect($this->policy->cancel($admin, $failedPost))->toBeFalse();
            expect($this->policy->cancel($admin, $cancelledPost))->toBeFalse();
        });

        it('allows admin users to cancel scheduled X posts', function () {
            $admin = User::factory()->admin()->create();
            $xPost = XPost::factory()->scheduled()->create();

            expect($this->policy->cancel($admin, $xPost))->toBeTrue();
        });
    });
});
