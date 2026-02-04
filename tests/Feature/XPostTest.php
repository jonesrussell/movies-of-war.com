<?php

use App\Enums\XPostStatus;
use App\Jobs\PublishXPost;
use App\Models\User;
use App\Models\XPost;
use App\Services\XPostSpreadsheetReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// Authentication & Authorization Tests

test('guests cannot access x-posts index', function () {
    $response = $this->get(route('admin.x-posts.index'));
    $response->assertRedirect(route('login'));
});

test('non-admin users cannot access x-posts index', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $response = $this->get(route('admin.x-posts.index'));
    $response->assertForbidden();
});

test('admin users can access x-posts index', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->get(route('admin.x-posts.index'));
    $response->assertOk();
});

// CRUD Tests

test('admin can create a draft x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Test tweet content',
        'status' => 'draft',
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('success', 'X post draft created successfully.');

    $this->assertDatabaseHas('x_posts', [
        'content' => 'Test tweet content',
        'status' => 'draft',
        'user_id' => $admin->id,
    ]);
});

test('admin can create a scheduled x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $scheduledFor = now()->addHours(2);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Scheduled tweet',
        'status' => 'scheduled',
        'scheduled_for' => $scheduledFor->toDateTimeString(),
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $this->assertDatabaseHas('x_posts', [
        'content' => 'Scheduled tweet',
        'status' => 'scheduled',
    ]);
});

test('admin can create x-post with thread', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'First tweet',
        'thread_parts' => ['Second tweet', 'Third tweet'],
        'status' => 'draft',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('x_posts', [
        'content' => 'First tweet',
    ]);

    $xPost = XPost::where('content', 'First tweet')->first();
    expect($xPost->thread_parts)->toBe(['Second tweet', 'Third tweet']);
});

test('admin can update a draft x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->create(['status' => XPostStatus::Draft]);
    $this->actingAs($admin);

    $response = $this->put(route('admin.x-posts.update', $xPost), [
        'content' => 'Updated content',
        'status' => 'draft',
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $this->assertDatabaseHas('x_posts', [
        'id' => $xPost->id,
        'content' => 'Updated content',
    ]);
});

test('admin cannot update a published x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->published()->create();
    $this->actingAs($admin);

    $response = $this->put(route('admin.x-posts.update', $xPost), [
        'content' => 'Trying to update',
        'status' => 'draft',
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('error');
});

test('admin can delete a draft x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->create(['status' => XPostStatus::Draft]);
    $this->actingAs($admin);

    $response = $this->delete(route('admin.x-posts.destroy', $xPost));

    $response->assertRedirect(route('admin.x-posts.index'));
    $this->assertDatabaseMissing('x_posts', ['id' => $xPost->id]);
});

test('admin cannot delete a published x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->published()->create();
    $this->actingAs($admin);

    $response = $this->delete(route('admin.x-posts.destroy', $xPost));

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('error');
    $this->assertDatabaseHas('x_posts', ['id' => $xPost->id]);
});

// Validation Tests

test('x-post requires content or thread parts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('content');
});

test('x-post content cannot exceed 280 characters', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => str_repeat('a', 281),
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('content');
});

test('scheduled post requires future scheduled_for date', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Test',
        'status' => 'scheduled',
        'scheduled_for' => now()->subHour()->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('scheduled_for');
});

test('scheduled post requires scheduled_for at least 1 minute in future', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    // Test that 30 seconds in the future is rejected
    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Test',
        'status' => 'scheduled',
        'scheduled_for' => now()->addSeconds(30)->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('scheduled_for');

    // Test that 2 minutes in the future is accepted
    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Test',
        'status' => 'scheduled',
        'scheduled_for' => now()->addMinutes(2)->toDateTimeString(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('thread parts cannot exceed 25 tweets', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $threadParts = array_fill(0, 26, 'Tweet content');

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'First tweet',
        'thread_parts' => $threadParts,
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('thread_parts');
});

test('media urls cannot exceed 4 attachments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Test',
        'media_urls' => ['image1.jpg', 'image2.jpg', 'image3.jpg', 'image4.jpg', 'image5.jpg'],
        'status' => 'draft',
    ]);

    $response->assertSessionHasErrors('media_urls');
});

// Scheduling Tests

test('admin can schedule a draft x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->create(['status' => XPostStatus::Draft]);
    $this->actingAs($admin);

    $scheduledFor = now()->addMinutes(2);

    $response = $this->post(route('admin.x-posts.schedule', $xPost), [
        'scheduled_for' => $scheduledFor->toDateTimeString(),
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('success');

    $xPost->refresh();
    expect($xPost->status)->toBe(XPostStatus::Scheduled);
    expect($xPost->scheduled_for)->not->toBeNull();
});

test('admin can cancel a scheduled x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->scheduled()->create();
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.cancel', $xPost));

    $response->assertRedirect(route('admin.x-posts.index'));

    $xPost->refresh();
    expect($xPost->status)->toBe(XPostStatus::Cancelled);
});

test('admin cannot cancel a non-scheduled x-post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->create(['status' => XPostStatus::Draft]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.cancel', $xPost));

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('error');
});

// Publishing Tests

test('admin can publish a draft x-post immediately', function () {
    Queue::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $xPost = XPost::factory()->create(['status' => XPostStatus::Draft]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.publish', $xPost));

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('success');

    Queue::assertPushed(PublishXPost::class, function ($job) use ($xPost) {
        return $job->xPost->id === $xPost->id;
    });
});

test('admin can create x-post with publish immediately', function () {
    // Mock XApiService to avoid actual API calls
    $mockXApiService = \Mockery::mock(\App\Services\XApiService::class);
    $mockResponse = new \App\Data\X\XPostTweetResponse(
        id: '123456789',
        text: 'Publish immediately test'
    );
    $mockXApiService->shouldReceive('postTweetAsDto')
        ->andReturn($mockResponse);
    $this->app->instance(\App\Services\XApiService::class, $mockXApiService);

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $response = $this->post(route('admin.x-posts.store'), [
        'content' => 'Publish immediately test',
        'status' => 'draft',
        'publish_immediately' => true,
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('success', 'X post published successfully.');

    $xPost = XPost::where('content', 'Publish immediately test')->first();
    expect($xPost)->not->toBeNull();
    expect($xPost->status)->toBe(XPostStatus::Published);
    expect($xPost->x_post_id)->toBe('123456789');
});

test('scheduled posts ready for publishing are dispatched by scheduler', function () {
    Queue::fake();

    $readyPost1 = XPost::factory()->readyToPublish()->create();
    $readyPost2 = XPost::factory()->readyToPublish()->create();
    $futurePost = XPost::factory()->scheduled()->create();

    // Manually invoke the scheduled task logic
    XPost::query()
        ->readyToPublish()
        ->each(function (XPost $xPost): void {
            PublishXPost::dispatch($xPost);
        });

    // Verify only ready posts were dispatched
    Queue::assertPushed(PublishXPost::class, 2);
    Queue::assertPushed(PublishXPost::class, fn ($job) => $job->xPost->id === $readyPost1->id);
    Queue::assertPushed(PublishXPost::class, fn ($job) => $job->xPost->id === $readyPost2->id);
});

// Model Tests

test('x-post can check if it has a thread', function () {
    $xPostWithThread = XPost::factory()->withThread()->create();
    $xPostWithoutThread = XPost::factory()->create();

    expect($xPostWithThread->hasThread())->toBeTrue();
    expect($xPostWithoutThread->hasThread())->toBeFalse();
});

test('x-post can check if it has media', function () {
    $xPostWithMedia = XPost::factory()->withMedia()->create();
    $xPostWithoutMedia = XPost::factory()->create();

    expect($xPostWithMedia->hasMedia())->toBeTrue();
    expect($xPostWithoutMedia->hasMedia())->toBeFalse();
});

test('x-post can get full thread content', function () {
    $xPost = XPost::factory()->create([
        'content' => 'First tweet',
        'thread_parts' => ['Second tweet', 'Third tweet'],
    ]);

    $threadContent = $xPost->getFullThreadContent();

    expect($threadContent)->toHaveCount(3);
    expect($threadContent[0])->toBe('First tweet');
    expect($threadContent[1])->toBe('Second tweet');
    expect($threadContent[2])->toBe('Third tweet');
});

test('x-post scope ready to publish returns correct posts', function () {
    $ready1 = XPost::factory()->readyToPublish()->create();
    $ready2 = XPost::factory()->readyToPublish()->create();
    $future = XPost::factory()->scheduled()->create();
    $draft = XPost::factory()->create(['status' => XPostStatus::Draft]);

    $readyPosts = XPost::query()->readyToPublish()->get();

    expect($readyPosts)->toHaveCount(2);
    expect($readyPosts->pluck('id'))->toContain($ready1->id, $ready2->id);
});

// Spreadsheet import tests

test('guests cannot access x-posts import page', function () {
    $response = $this->get(route('admin.x-posts.import'));
    $response->assertRedirect(route('login'));
});

test('non-admin users cannot access x-posts import page', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user);

    $response = $this->get(route('admin.x-posts.import'));
    $response->assertForbidden();
});

test('import page redirects when spreadsheet is not found', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    config(['x_posts.spreadsheet_path' => '/nonexistent/path.xlsx']);

    $response = $this->get(route('admin.x-posts.import'));
    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('error');
});

test('admin can see import page when spreadsheet exists', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $path = resource_path('xslx/MoW_X_Posts_2026_CLEANED.xlsx');
    if (! is_readable($path)) {
        $this->markTestSkipped('Spreadsheet fixture not present');
    }
    config(['x_posts.spreadsheet_path' => $path]);

    $response = $this->get(route('admin.x-posts.import'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Admin/XPosts/Import')->has('preview'));
});

test('admin can import selected rows as drafts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $mockRows = [
        [
            'content' => 'Imported tweet one',
            'media_urls' => ['https://example.com/img.jpg'],
            'scheduled_for' => null,
            'theme' => 'Theme A',
            'post_type' => 'Image Post',
            'row_number' => 2,
            'raw' => [],
        ],
        [
            'content' => 'Imported tweet two',
            'media_urls' => [],
            'scheduled_for' => null,
            'theme' => null,
            'post_type' => null,
            'row_number' => 3,
            'raw' => [],
        ],
    ];

    $this->mock(XPostSpreadsheetReader::class, function ($mock) use ($mockRows) {
        $mock->shouldReceive('read')->once()->andReturn($mockRows);
    });

    config(['x_posts.spreadsheet_path' => resource_path('xslx/MoW_X_Posts_2026_CLEANED.xlsx')]);

    $response = $this->post(route('admin.x-posts.import.store'), [
        'row_numbers' => [2, 3],
    ]);

    $response->assertRedirect(route('admin.x-posts.index'));
    $response->assertSessionHas('success', '2 posts imported as drafts.');

    $this->assertDatabaseCount('x_posts', 2);
    $this->assertDatabaseHas('x_posts', [
        'content' => 'Imported tweet one',
        'status' => 'draft',
        'user_id' => $admin->id,
    ]);
    $this->assertDatabaseHas('x_posts', [
        'content' => 'Imported tweet two',
        'status' => 'draft',
    ]);
});
