<?php

declare(strict_types=1);

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('guest can view reviews index for a published movie', function () {
    $movie = Movie::factory()->published()->create(['slug' => 'test-movie']);

    $response = $this->get(route('movies.reviews.index', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Reviews')
        ->has('movie')
        ->has('curator_review')
        ->has('reviews')
    );
});

test('reviews index includes curator_review and excludes it from paginated list when curator configured', function () {
    $curator = User::factory()->create(['name' => 'Curator']);
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'curator-movie']);
    $curatorReview = Review::factory()->for($curator)->for($movie)->create([
        'content' => str_repeat('Curator review. ', 10),
    ]);
    $otherUser = User::factory()->create();
    Review::factory()->for($otherUser)->for($movie)->create([
        'content' => str_repeat('Other review. ', 10),
        'has_spoilers' => false,
    ]);

    $response = $this->get(route('movies.reviews.index', $movie->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Movies/Reviews')
        ->has('curator_review')
        ->where('curator_review.id', $curatorReview->id)
        ->where('curator_review.is_curator', true)
    );
    $reviewsData = $response->original->getData()['page']['props']['reviews']['data'];
    $ids = array_column($reviewsData, 'id');
    expect($ids)->not->toContain($curatorReview->id);
    expect($reviewsData)->toHaveCount(1);
});

test('reviews index sort works when curator review is excluded from list', function () {
    $curator = User::factory()->create();
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'sort-movie']);
    Review::factory()->for($curator)->for($movie)->create([
        'rating' => 2,
        'content' => str_repeat('Curator. ', 10),
    ]);
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Review::factory()->for($userA)->for($movie)->withoutSpoilers()->create([
        'rating' => 4,
        'content' => str_repeat('High rating. ', 10),
    ]);
    Review::factory()->for($userB)->for($movie)->withoutSpoilers()->create([
        'rating' => 1,
        'content' => str_repeat('Low rating. ', 10),
    ]);

    $response = $this->get(route('movies.reviews.index', [
        'movie' => $movie->slug,
        'sort' => 'rating_high',
    ]));

    $response->assertOk();
    $reviewsData = $response->original->getData()['page']['props']['reviews']['data'];
    expect($reviewsData)->toHaveCount(2);
    expect((float) $reviewsData[0]['rating'])->toBe(4.0);
    expect((float) $reviewsData[1]['rating'])->toBe(1.0);
});

test('reviews index pagination counts exclude curator review', function () {
    $curator = User::factory()->create();
    Config::set('app.curator_user_id', $curator->id);

    $movie = Movie::factory()->published()->create(['slug' => 'pagination-movie']);
    Review::factory()->for($curator)->for($movie)->create([
        'content' => str_repeat('Curator. ', 10),
    ]);
    foreach (range(1, 10) as $i) {
        Review::factory()
            ->for(User::factory()->create())
            ->for($movie)
            ->withoutSpoilers()
            ->create(['content' => str_repeat("Review {$i}. ", 10)]);
    }

    $response = $this->get(route('movies.reviews.index', $movie->slug));

    $response->assertOk();
    $reviews = $response->original->getData()['page']['props']['reviews'];
    expect($reviews['data'])->toHaveCount(10);
    expect($reviews['meta']['total'])->toBe(10);
    expect($reviews['meta']['last_page'])->toBe(1);
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
