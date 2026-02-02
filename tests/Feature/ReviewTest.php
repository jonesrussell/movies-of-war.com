<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;

test('guest can view reviews index for a published movie', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);

    $response = $this->get(route('movies.reviews.index', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Reviews')
        ->has('movie')
        ->has('reviews')
    );
});

test('index excludes spoiler reviews when show_spoilers is not set', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);
    $user = User::factory()->create();
    Review::factory()->for($user)->for($movie)->withSpoilers()->create([
        'content' => str_repeat('Spoiler content here. ', 5),
    ]);

    $response = $this->get(route('movies.reviews.index', ['movie' => $movie->slug]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Reviews')
        ->where('reviews.data', [])
    );
});

test('index includes spoiler reviews when show_spoilers=1', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);
    $user = User::factory()->create();
    Review::factory()->for($user)->for($movie)->withSpoilers()->create([
        'content' => str_repeat('Spoiler content here. ', 5),
    ]);

    $response = $this->get(route('movies.reviews.index', [
        'movie' => $movie->slug,
        'show_spoilers' => 1,
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Reviews')
        ->has('reviews.data', 1)
    );
});

test('authenticated user can create a review for a movie', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);

    $response = $this->actingAs($user)->post(route('movies.reviews.store', $movie->slug), [
        'rating' => 3,
        'title' => 'Great film',
        'content' => str_repeat('This is a meaningful review content. ', 3),
        'has_spoilers' => false,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('reviews', [
        'user_id' => $user->id,
        'movie_id' => $movie->id,
        'rating' => 3,
        'title' => 'Great film',
    ]);
});

test('user cannot create a second review for the same movie', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);
    Review::factory()->for($user)->for($movie)->create([
        'content' => str_repeat('First review content. ', 3),
    ]);

    $response = $this->actingAs($user)->post(route('movies.reviews.store', $movie->slug), [
        'rating' => 2,
        'content' => str_repeat('Second review attempt. ', 3),
        'has_spoilers' => false,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('reviews', 1);
});

test('guest cannot create a review', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);

    $response = $this->post(route('movies.reviews.store', $movie->slug), [
        'rating' => 3,
        'content' => str_repeat('Guest review. ', 3),
    ]);

    $response->assertRedirect(route('login', [], false));
});

test('user can update their own review', function () {
    $user = User::factory()->create();
    $review = Review::factory()->for($user)->for(Movie::factory()->published())->create([
        'content' => str_repeat('Original content. ', 3),
    ]);

    $response = $this->actingAs($user)->patch(route('reviews.update', $review), [
        'rating' => 4,
        'title' => 'Updated title',
        'content' => str_repeat('Updated review content. ', 3),
        'has_spoilers' => false,
    ]);

    $response->assertRedirect();
    $review->refresh();
    expect((float) $review->rating)->toBe(4.0);
    expect($review->title)->toBe('Updated title');
});

test('user cannot update another users review', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $review = Review::factory()->for($owner)->for(Movie::factory()->published())->create();

    $response = $this->actingAs($other)->patch(route('reviews.update', $review), [
        'rating' => 1,
        'content' => str_repeat('Malicious update. ', 3),
    ]);

    $response->assertForbidden();
});

test('user can delete their own review', function () {
    $user = User::factory()->create();
    $review = Review::factory()->for($user)->for(Movie::factory()->published())->create();

    $response = $this->actingAs($user)->delete(route('reviews.destroy', $review));

    $response->assertRedirect();
    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});

test('admin can delete any review', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $review = Review::factory()->for($owner)->for(Movie::factory()->published())->create();

    $response = $this->actingAs($admin)->delete(route('reviews.destroy', $review));

    $response->assertRedirect();
    $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
});

test('guest can view single published review', function () {
    $review = Review::factory()
        ->for(User::factory())
        ->for(Movie::factory()->published())
        ->create();

    $response = $this->get(route('reviews.show', $review));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reviews/Show')
        ->has('review')
    );
});

test('validation requires rating and min content length', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);

    $response = $this->actingAs($user)->post(route('movies.reviews.store', $movie->slug), [
        'rating' => 5,
        'content' => 'Too short',
    ]);

    $response->assertSessionHasErrors(['rating', 'content']);
});

test('review with markdown stores raw content and show returns content_html', function () {
    $user = User::factory()->create();
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);
    $content = 'This is **bold** and [a link](https://example.com). '.str_repeat('More text. ', 5);

    $this->actingAs($user)->post(route('movies.reviews.store', $movie->slug), [
        'rating' => 3,
        'title' => 'Markdown review',
        'content' => $content,
        'has_spoilers' => false,
    ]);

    $review = Review::query()->where('movie_id', $movie->id)->where('user_id', $user->id)->first();
    expect($review)->not->toBeNull();
    expect($review->content)->toContain('**bold**');

    $response = $this->get(route('reviews.show', $review));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Reviews/Show')
        ->has('review')
    );
    $reviewProps = $response->original->getData()['page']['props']['review'];
    expect($reviewProps['content'])->toContain('**bold**');
    expect($reviewProps['content_html'])->toContain('<strong>bold</strong>')
        ->and($reviewProps['content_html'])->toContain('href="https://example.com"');
});

test('markdown preview endpoint returns rendered html', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('markdown.preview'), [
        'markdown' => '**Bold** and *italic*.',
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['html']);
    $html = $response->json('html');
    expect($html)->toContain('<strong>Bold</strong>');
});

test('guest cannot access markdown preview', function () {
    $response = $this->postJson(route('markdown.preview'), [
        'markdown' => 'Hello',
    ]);

    $response->assertUnauthorized();
});
