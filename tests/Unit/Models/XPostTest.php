<?php

use App\Models\XPost;

// Status Check Methods

test('is scheduled returns true when status is scheduled', function () {
    $xPost = XPost::factory()->scheduled()->create();

    expect($xPost->isScheduled())->toBeTrue();
});

test('is scheduled returns false when status is not scheduled', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    expect($xPost->isScheduled())->toBeFalse();
});

test('is published returns true when status is published', function () {
    $xPost = XPost::factory()->published()->create();

    expect($xPost->isPublished())->toBeTrue();
});

test('is published returns false when status is not published', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    expect($xPost->isPublished())->toBeFalse();
});

test('is draft returns true when status is draft', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    expect($xPost->isDraft())->toBeTrue();
});

test('is draft returns false when status is not draft', function () {
    $xPost = XPost::factory()->published()->create();

    expect($xPost->isDraft())->toBeFalse();
});

test('has failed returns true when status is failed', function () {
    $xPost = XPost::factory()->failed()->create();

    expect($xPost->hasFailed())->toBeTrue();
});

test('has failed returns false when status is not failed', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    expect($xPost->hasFailed())->toBeFalse();
});

// Business Logic Methods

test('can publish returns true for draft posts', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    expect($xPost->canPublish())->toBeTrue();
});

test('can publish returns true for scheduled posts', function () {
    $xPost = XPost::factory()->scheduled()->create();

    expect($xPost->canPublish())->toBeTrue();
});

test('can publish returns true for failed posts', function () {
    $xPost = XPost::factory()->failed()->create();

    expect($xPost->canPublish())->toBeTrue();
});

test('can publish returns false for published posts', function () {
    $xPost = XPost::factory()->published()->create();

    expect($xPost->canPublish())->toBeFalse();
});

test('can publish returns false for cancelled posts', function () {
    $xPost = XPost::factory()->cancelled()->create();

    expect($xPost->canPublish())->toBeFalse();
});

test('has thread returns true when thread parts are not empty', function () {
    $xPost = XPost::factory()->withThread()->create();

    expect($xPost->hasThread())->toBeTrue();
});

test('has thread returns false when thread parts are empty', function () {
    $xPost = XPost::factory()->create(['thread_parts' => []]);

    expect($xPost->hasThread())->toBeFalse();
});

test('has thread returns false when thread parts are null', function () {
    $xPost = XPost::factory()->create(['thread_parts' => null]);

    expect($xPost->hasThread())->toBeFalse();
});

test('has media returns true when media urls are not empty', function () {
    $xPost = XPost::factory()->withMedia()->create();

    expect($xPost->hasMedia())->toBeTrue();
});

test('has media returns false when media urls are empty', function () {
    $xPost = XPost::factory()->create(['media_urls' => []]);

    expect($xPost->hasMedia())->toBeFalse();
});

test('has media returns false when media urls are null', function () {
    $xPost = XPost::factory()->create(['media_urls' => null]);

    expect($xPost->hasMedia())->toBeFalse();
});

test('get full thread content returns content only when no thread parts', function () {
    $xPost = XPost::factory()->create([
        'content' => 'First tweet',
        'thread_parts' => null,
    ]);

    $threadContent = $xPost->getFullThreadContent();

    expect($threadContent)->toBe(['First tweet']);
});

test('get full thread content combines content and thread parts', function () {
    $xPost = XPost::factory()->create([
        'content' => 'First tweet',
        'thread_parts' => ['Second tweet', 'Third tweet'],
    ]);

    $threadContent = $xPost->getFullThreadContent();

    expect($threadContent)->toBe(['First tweet', 'Second tweet', 'Third tweet']);
});

test('get full thread content filters out empty values', function () {
    $xPost = XPost::factory()->create([
        'content' => 'First tweet',
        'thread_parts' => ['Second tweet', '', 'Third tweet', null],
    ]);

    $threadContent = $xPost->getFullThreadContent();

    expect($threadContent)->toHaveCount(3);
    expect($threadContent)->toContain('First tweet', 'Second tweet', 'Third tweet');
});

// State Transition Methods

test('mark as scheduled updates status and scheduled for datetime', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);
    $scheduledFor = now()->addHours(2);

    $xPost->markAsScheduled($scheduledFor);

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_SCHEDULED);
    expect($xPost->scheduled_for->format('Y-m-d H:i:s'))->toBe($scheduledFor->format('Y-m-d H:i:s'));
});

test('mark as published updates status with x post id and published at', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);
    $xPostId = '1234567890123456789';

    $xPost->markAsPublished($xPostId);

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_PUBLISHED);
    expect($xPost->x_post_id)->toBe($xPostId);
    expect($xPost->published_at)->not->toBeNull();
    expect($xPost->error_message)->toBeNull();
});

test('mark as failed updates status with error message', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);
    $errorMessage = 'Failed to connect to X API';

    $xPost->markAsFailed($errorMessage);

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_FAILED);
    expect($xPost->error_message)->toBe($errorMessage);
});

test('cancel updates status to cancelled when currently scheduled', function () {
    $xPost = XPost::factory()->scheduled()->create();

    $xPost->cancel();

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_CANCELLED);
});

test('cancel does not update status when not scheduled', function () {
    $xPost = XPost::factory()->create(['status' => XPost::STATUS_DRAFT]);

    $xPost->cancel();

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_DRAFT);
});

test('cancel does not update status when already published', function () {
    $xPost = XPost::factory()->published()->create();

    $xPost->cancel();

    $xPost->refresh();
    expect($xPost->status)->toBe(XPost::STATUS_PUBLISHED);
});
