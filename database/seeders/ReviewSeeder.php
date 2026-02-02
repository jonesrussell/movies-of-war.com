<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::query()->published()->limit(10)->get();
        $users = User::query()->limit(10)->get();

        if ($movies->isEmpty() || $users->isEmpty()) {
            return;
        }

        foreach ($movies as $movie) {
            $reviewers = $users->random(min(4, $users->count()));
            foreach ($reviewers as $user) {
                if (Review::query()->where('user_id', $user->id)->where('movie_id', $movie->id)->exists()) {
                    continue;
                }

                $hasSpoilers = fake()->boolean(25);
                $review = Review::factory()
                    ->for($user)
                    ->for($movie)
                    ->create([
                        'rating' => (float) fake()->randomElement([1.0, 2.0, 3.0, 4.0]),
                        'content' => fake()->paragraphs(2, true),
                        'has_spoilers' => $hasSpoilers,
                    ]);

                if (fake()->boolean(40)) {
                    ReviewComment::factory()
                        ->count(fake()->numberBetween(1, 2))
                        ->for($review)
                        ->for($users->random())
                        ->create();
                }
            }
        }
    }
}
