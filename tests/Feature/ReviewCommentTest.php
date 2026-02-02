<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;

test('authenticated user can add a comment to a review', function () {
    $user = User::factory()->create();
    $review = Review::factory()
        ->for($user)
        ->for(Movie::factory()->published())
        ->create();

    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser)->post(route('reviews.comments.store', $review), [
        'content' => 'This is a thoughtful comment on the review.',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('review_comments', [
        'review_id' => $review->id,
        'user_id' => $otherUser->id,
        'content' => 'This is a thoughtful comment on the review.',
    ]);
    $review->refresh();
    expect($review->comments_count)->toBe(1);
});

test('guest cannot add a comment', function () {
    $review = Review::factory()
        ->for(User::factory())
        ->for(Movie::factory()->published())
        ->create();

    $response = $this->post(route('reviews.comments.store', $review), [
        'content' => 'Guest comment attempt.',
    ]);

    $response->assertRedirect(route('login', [], false));
});

test('user can update their own comment', function () {
    $user = User::factory()->create();
    $comment = ReviewComment::factory()
        ->for(Review::factory()->for($user)->for(Movie::factory()->published()))
        ->for($user)
        ->create(['content' => 'Original comment content here.']);

    $response = $this->actingAs($user)->patch(route('review-comments.update', $comment), [
        'content' => 'Updated comment content here.',
    ]);

    $response->assertRedirect();
    $comment->refresh();
    expect($comment->content)->toBe('Updated comment content here.');
});

test('user cannot update another users comment', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $comment = ReviewComment::factory()
        ->for(Review::factory()->for($owner)->for(Movie::factory()->published()))
        ->for($owner)
        ->create(['content' => 'Original comment.']);

    $response = $this->actingAs($other)->patch(route('review-comments.update', $comment), [
        'content' => 'Malicious update.',
    ]);

    $response->assertForbidden();
});

test('user can delete their own comment', function () {
    $user = User::factory()->create();
    $review = Review::factory()->for($user)->for(Movie::factory()->published())->create();
    $comment = ReviewComment::factory()->for($review)->for($user)->create();

    $response = $this->actingAs($user)->delete(route('review-comments.destroy', $comment));

    $response->assertRedirect();
    $this->assertDatabaseMissing('review_comments', ['id' => $comment->id]);
    $review->refresh();
    expect($review->comments_count)->toBe(0);
});

test('admin can delete any comment', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $comment = ReviewComment::factory()
        ->for(Review::factory()->for($owner)->for(Movie::factory()->published()))
        ->for($owner)
        ->create();

    $response = $this->actingAs($admin)->delete(route('review-comments.destroy', $comment));

    $response->assertRedirect();
    $this->assertDatabaseMissing('review_comments', ['id' => $comment->id]);
});

test('comment validation requires min length', function () {
    $user = User::factory()->create();
    $review = Review::factory()->for($user)->for(Movie::factory()->published())->create();

    $response = $this->actingAs($user)->post(route('reviews.comments.store', $review), [
        'content' => 'Short',
    ]);

    $response->assertSessionHasErrors(['content']);
});
