<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = fake()->randomElement([0.0, 1.0, 2.0, 3.0, 4.0]);

        return [
            'user_id' => User::factory(),
            'movie_id' => Movie::factory(),
            'rating' => $rating,
            'title' => fake()->optional(0.5)->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'has_spoilers' => fake()->boolean(20),
            'is_published' => true,
            'helpful_count' => 0,
            'comments_count' => 0,
        ];
    }

    public function withSpoilers(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_spoilers' => true,
        ]);
    }

    public function withoutSpoilers(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_spoilers' => false,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
